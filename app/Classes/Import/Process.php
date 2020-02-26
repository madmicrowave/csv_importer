<?php

namespace App\Console\Classes\Import;

use App\Classes\Import\Exception\InstructionNotFoundOrFailed;
use App\Classes\Import\ImportInstruction;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Classes\ConsoleOutput;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Class DataToDataBase
 * @package App\Console\Classes
 */
class Process
{
    const META_FIELDS_START_WITH = ['-----', 'rows=', 'timestamp='];

    /** @var array */
    public $importResult = [
        'status' => null,
        'meta' => null,
    ];

    /** @var array */
    private $schema = [
        'columns' => null,
        'file_data' => [],
        'file_meta' => [],
        'delimiter' => ',',
    ];

    /** @var ConsoleOutput */
    private $output;

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string */
    private $rawFileContent;

    /** @var string */
    private $tableName;

    /** @var ImportInstruction */
    private $importInstruction;

    /**
     * TableCreator constructor.
     * @param string $filePath
     * @param string $fileContents
     *
     * @throws InstructionNotFoundOrFailed
     */
    public function __construct(string $filePath, string $fileContents)
    {
        $this->output = new ConsoleOutput();
        $this->filePath = $filePath;
        $this->fileName = basename($filePath);
        $this->rawFileContent = $fileContents;
        $this->tableName = $this->getTableName();

        $this
            ->registerInstruction()
            ->normalizeFileData()
            ->buildColumnsSchema();
    }

    /**
     * @return array
     */
    public function start(): array
    {
        $this
            ->createTableAndColumns()
            ->importDataToDatabase();

        $this->output->info('Finished! Records modified: '.(is_array($this->schema['file_data']) ? count($this->schema['file_data']) : 0));

        return $this->importResult;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        $fileNameParts = explode(
            '_',
            strtolower($this->fileName)
        );

        return sprintf('%s_%s', $fileNameParts[0], $fileNameParts[1]);
    }

    /**
     * @return $this
     */
    protected function prepareImportResult(): self
    {
        $this->importResult['status'] = empty($this->schema['file_meta']['query_exceptions']);
        $this->importResult['meta'] = $this->schema['file_meta'];

        return $this;
    }

    /**
     * @return $this
     */
    protected function createTableAndColumns(): self
    {
        if ($this->schema['columns']) {
            $this->output->writeln('Verifying table and columns...');

            if (Schema::hasTable($this->tableName)) {
                $schemaColumns = Schema::getColumnListing($this->tableName);

                $newColumnsSchema = [];
                foreach ($this->schema['columns'] as $columnName => $columnSchema) {
                    if (!in_array($columnName, $schemaColumns) && $this->schema['columns'] != 'id') {
                        $newColumnsSchema[$columnName] = $columnSchema;
                    }
                }

                if (!empty($newColumnsSchema)) {
                    $this->output->writeln('Alter table add new columns...');

                    try {
                        Schema::table($this->tableName, function (Blueprint $table) use ($newColumnsSchema) {
                            foreach ($newColumnsSchema as $columnName => $columnSchema) {
                                $this->addColumnBySchema($table, $columnName, $columnSchema);
                            }
                        });

                        // TODO: should verify and re-create instruction->uniqueIndexByColumns()

                        $this->output->writeln('Columns created: ' . implode(',', array_keys($newColumnsSchema)));
                    } catch (QueryException $e) {
                        $this->output->error('WARNING: '.$e->getMessage());
                        $this->schema['file_meta']['query_exceptions'][] = $e->getMessage();
                    }
                }

                return $this;
            }

            $this->output->writeln(
                sprintf('Creating new "%s" schema...', $this->tableName)
            );

            try {
                Schema::create($this->tableName, function (Blueprint $table) {
                    $table->bigIncrements('id');

                    foreach ($this->schema['columns'] as $columnName => $columnSchema) {
                        $this->addColumnBySchema($table, $columnName, $columnSchema);
                    }

                    $unique = $this->importInstruction->uniqueIndexByColumns();
                    if (!empty($unique)) {
                        $table->unique($unique);
                    }
                });

                $this->output->info('Table "'.$this->tableName.'" created with columns: "'.implode(',', array_keys($this->schema['columns'])).'" created');
            } catch (QueryException $e) {
                $this->output->error('WARNING: '.$e->getMessage());
                $this->schema['file_meta']['query_exceptions'][] = $e->getMessage();
            }

            return $this;
        }

        $this->output->error('Table is not created!');

        return $this;
    }

    /**
     * TODO: update if instruction is specified
     *
     * @return $this
     */
    protected function importDataToDatabase(): self
    {
        if (empty($this->schema['file_data'])) {
            $this->output->writeln('Nothing to import...');

            return $this
                ->prepareImportResult();
        }

        $this->output->info('Importing data...');

        try {
            $uniqueKeys = $this->importInstruction->uniqueIndexByColumns();

            foreach ($this->schema['file_data'] as $row) {
                $this->formatRow($row);
                $whereClosure = [];
                foreach ($uniqueKeys as $key) {
                    $whereClosure[] = [$key, '=', $row[$key]];
                }

                $query = DB::table($this->tableName);
                if (!empty($whereClosure)) {
                    $query->where($whereClosure);
                }

                if ($exists = $query->first()) {
                    $this->output->writeln('Record #'.$exists->id.' updated...');

                    DB::table($this->tableName)
                        ->where('id', $exists->id)
                        ->update($row);

                    continue;
                }

                $this->output->writeln('New record inserted...');

                DB::table($this->tableName)->insert($row);
            }

        } catch(QueryException $e){
            $this->schema['file_meta']['query_exceptions'][] = $e->getMessage();
            $this->output->error('Insert exception: '. $e->getMessage());
        }

        return $this
            ->prepareImportResult();
    }

    /**
     * TODO: apply this via formatter - Row
     *
     * @param array $row
     */
    private function formatRow(array &$row): void
    {
        foreach ($this->schema['columns'] as $columnName => $columnSchema) {
            switch ($columnSchema['type']) {
                case 'integer':
                    $row[$columnName] = intval($row[$columnName]);
                    break;
                case 'datetime':
                case 'date':
                    $row[$columnName] = Carbon::createFromFormat($columnSchema['format'], $row[$columnName]);
                    break;
            }
        }
    }

    /**
     * TODO: apply this via formatter - Header
     *
     * @param array $headers
     */
    private function formatHeaders(array &$headers): void
    {
        foreach ($headers as $key => $value) {
            $headers[$key] = str_replace(' ', '_', $value);
        }
    }

    /**
     * @return $this
     *
     * @throws InstructionNotFoundOrFailed
     */
    private function registerInstruction(): self
    {
        $instructionNamespace = 'App\\Classes\\Import\\Instruction\\'.Str::ucfirst(Str::camel($this->tableName)).'Table';

        if (!class_exists($instructionNamespace)) {
            throw new InstructionNotFoundOrFailed(
                sprintf('Instruction "%s" not found!', $instructionNamespace)
            );
        }

        $this->output->writeln('Instruction found: '.$instructionNamespace);
        $instruction = new $instructionNamespace($this->fileName, $this->filePath);

        if (!$instruction instanceof ImportInstruction) {
            throw new InstructionNotFoundOrFailed('Instruction not applied, should implement \'ImportInstruction\' interface');
        }

        $this->importInstruction = $instruction;

        return $this;
    }

    /**
     * @return $this
     */
    private function buildColumnsSchema(): self
    {
        if (empty($this->schema['file_data'][0])) {
            return $this;
        }

        $columnsLists = array_keys($this->schema['file_data'][0]);

        // normalize columns
        foreach ($columnsLists as $column) {
            $this->schema['columns'][$column] = [
                'type' => 'string',
                'nullable' => true,
            ];
        }

        $this->schema['columns'] = array_replace(
            $this->schema['columns'],
            $this->importInstruction->columnsSchema(),
            $this->importInstruction->commonColumnsSchema()
        );

        return $this;
    }

    /**
     * @return self
     */
    private function normalizeFileData(): self
    {
        $this->output->writeln('Normalize file data...');

        // explode csv lines
        $rows = explode(PHP_EOL, $this->rawFileContent);
        $this->setDelimiter($rows[0]);

        // get header
        $headers = str_getcsv(strtolower($rows[0]), $this->schema['delimiter']);
        $this->formatHeaders($headers);
        array_shift($rows); // remove header row

        // normalize import data
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }

            if (preg_match('('.implode('|', self::META_FIELDS_START_WITH).')', $row)) {
                $this->schema['file_meta'][] = $row;
                continue;
            }

            $rowCombined = array_combine($headers, str_getcsv($row, $this->schema['delimiter']));

            $this->schema['file_data'][] = array_replace(
                $rowCombined,
                $this->importInstruction->addRowColumns($rowCombined),
                $this->importInstruction->addCommonRowColumns()
            );
        }

        return $this;
    }

    /**
     * @param string $firstLine
     */
    private function setDelimiter(string $firstLine): void
    {
        if (substr_count($firstLine, ';') < substr_count($firstLine, ',')) {
            $this->schema['delimiter'] = ',';
            return;
        }

        $this->schema['delimiter'] = ';';
    }

    /**
     * TODO: make chain structure more pretty and dynamic
     *
     * Max chain size is 3, supported chain keys:
     *  - nullable
     *  - index
     *  - unique
     *
     * @param Blueprint $table
     * @param string $columnName
     * @param array $columnSchema
     */
    private function addColumnBySchema(Blueprint $table, string $columnName, array $columnSchema): void
    {
        $chain = [];
        foreach ($columnSchema as $key => $value) {
            if ($key == 'type') { // should be first all time
                array_unshift($chain, $value);
                continue;
            }
            if (in_array($key, ['nullable', 'index', 'unique']) && $value) {
                $chain[] = $key;
                continue;
            }
        }

        if ('decimal' == $columnSchema['type']) {
            $total = $columnSchema['total'] ?? 8;
            $places = $columnSchema['places'] ?? 6;

            switch (count($chain)) {
                case 1:
                    $table->{$chain[0]}($columnName, $total, $places);
                    break;
                case 2:
                    $table->{$chain[0]}($columnName, $total, $places)->{$chain[1]}();
                    break;
                case 3:
                    $table->{$chain[0]}($columnName, $total, $places)->{$chain[1]}()->{$chain[2]}();
                    break;
            }

            return;
        }

        $length = isset($columnSchema['length']) ? (int) $columnSchema['length'] : false;
        if ($length) {
            switch (count($chain)) {
                case 1:
                    $table->{$chain[0]}($columnName, $length);
                    break;
                case 2:
                    $table->{$chain[0]}($columnName, $length)->{$chain[1]}();
                    break;
                case 3:
                    $table->{$chain[0]}($columnName, $length)->{$chain[1]}()->{$chain[2]}();
                    break;
            }

            return;
        }

        switch (count($chain)) {
            case 1:
                $table->{$chain[0]}($columnName);
                break;
            case 2:
                $table->{$chain[0]}($columnName)->{$chain[1]}();
                break;
            case 3:
                $table->{$chain[0]}($columnName)->{$chain[1]}()->{$chain[2]}();
                break;
        }
    }
}
