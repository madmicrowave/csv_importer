<?php

namespace App\Console\Classes\Import;

use App\Classes\Import\ImportInstruction;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use ErrorException;

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
        'columns_size' => 0,
        'file_data' => [],
        'file_meta' => [],
        'delimiter' => ',',
    ];

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string */
    private $rawFileContent;

    /** @var string */
    private $tableName;

    /** @var bool */
    private $showOutput = true;

    /** @var ImportInstruction */
    private $importInstruction;

    /**
     * TableCreator constructor.
     * @param string $filePath
     * @param string $fileContents
     */
    public function __construct(string $filePath, string $fileContents)
    {
        $this->filePath = $filePath;
        $this->fileName = basename($filePath);
        $this->rawFileContent = $fileContents;
        $this->tableName = $this->getTableName();

        $this
            ->normalizeFileData()
            ->addCommonFileData()
            ->registerInstruction();
    }

    /**
     * @return array
     */
    public function start(): array
    {
        $this
            ->createTableAndColumns()
            ->importDataToDatabase();

        $this->logOutput('Finished! Records imported: '.(is_array($this->schema['file_data']) ? count($this->schema['file_data']) : 0));

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
        $this->logOutput('Verifying table and columns...');
        $this->buildTableSchema();

        if ($this->schema['columns']) {
            if (Schema::hasTable($this->tableName)) {
                $schemaColumns = Schema::getColumnListing($this->tableName);

                $newColumns = [];
                foreach (array_keys($this->schema['columns']) as $columnName) {
                    if (!in_array($columnName, $schemaColumns) && $this->schema['columns'] != 'id') {
                        $newColumns[] = $columnName;
                    }
                }

                if (!empty($newColumns)) {
                    $this->logOutput('Alter table add new columns...');

                    try {
                        Schema::table($this->tableName, function (Blueprint $table) use ($newColumns) {
                            foreach ($newColumns as $columnName) {
                                $this->addColumnBySchema($table, $columnName);
                            }
                        });

                        $this->logOutput('Columns created: ' . implode(',', $newColumns));
                    } catch (QueryException $e) {
                        $this->logOutput('WARNING: '.$e->getMessage());
                        $this->schema['file_meta']['query_exceptions'][] = $e->getMessage();
                    }
                }

                return $this;
            }

            $this->logOutput('Creating new "'.$this->tableName.'" schema...');

            try {
                Schema::create($this->tableName, function (Blueprint $table) {
                    $table->bigIncrements('id');

                    foreach (array_keys($this->schema['columns']) as $columnName) {
                        $this->addColumnBySchema($table, $columnName);
                    }
                });

                $this->logOutput('Table "'.$this->tableName.'" and columns: "'.implode(',', array_keys($this->schema['columns'])).'" created');
            } catch (QueryException $e) {
                $this->logOutput('WARNING: '.$e->getMessage());
                $this->schema['file_meta']['query_exceptions'][] = $e->getMessage();
            }

            return $this;
        }

        $this->logOutput('Do nothing. Table schema is empty...');

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
            $this->logOutput('Nothing to import...');
            return $this
                ->prepareImportResult();
        }

        $this->logOutput('Importing data...');

        try {
            DB::table($this->tableName)
                ->insert($this->schema['file_data']);

            $this->logOutput('Import success...');
        } catch(QueryException $e){
            $this->schema['file_meta']['query_exceptions'][] = $e->getMessage();
            $this->logOutput('Insert exception:'. $e->getMessage());
        }

        return $this
            ->prepareImportResult();
    }

    private function addCommonFileData(): self
    {
        $this->logOutput('Adding common file data...');

        $fileNameParts = explode(
            '_',
            str_replace('.'.pathinfo($this->fileName, PATHINFO_EXTENSION), '', $this->fileName)
        );

        foreach ($this->schema['file_data'] as &$data) {
            // transform date fields
            foreach ($data as $key => &$value) {
                if (strpos($key, '_date') !== false || strpos($key, '_time') !== false) {
                    try {
                        $value = Carbon::parse($value);
                    } catch (\Exception $e) {
                        if (strlen($value) <= 10) {
                            $value = Carbon::createFromFormat('Y.m.d', '2019.10.09');
                        }
                    }
                }
            }

            // set default fields
            $data['ci_field_file_name'] = $this->fileName;
            $data['ci_field_file_date'] = $fileNameParts[4]; // make timestamp if numeric
            $data['ci_field_client_name'] = $fileNameParts[2];
            $data['ci_field_count'] = $fileNameParts[5];
            $data['ci_field_id'] = $fileNameParts[3];
            $data['ci_field_created_at'] = Carbon::now();
            $data['ci_field_updated_at'] = null;

            // apply import instruction
            if ($this->importInstruction) {
                $data = array_replace(
                    $data,
                    $this->importInstruction->addRowFields($data)
                );
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function registerInstruction(): self
    {
        $instructionNamespace = 'App\\Classes\\Import\\Instructions\\'.Str::ucfirst(Str::camel($this->tableName)).'Table';

        if (class_exists($instructionNamespace)) {
            $this->logOutput('Instruction found: '.$instructionNamespace);
            $instruction = new $instructionNamespace($this->schema['file_data'], $this->filePath);

            if (!$instruction instanceof ImportInstruction) {
                $this->logOutput('Instruction not applied, should implement \'ImportInstruction\'');
                return $this;
            }

            if ($instruction->isInstructionValid()) {
                $this->importInstruction = $instruction;
                return $this;
            }

            $this->logOutput('Instruction is not valid, check interface \'ImportInstruction\' documentation!');
        }

        return $this;
    }

    /**
     * @param Blueprint $table
     * @param string $columnName
     */
    private function addColumnBySchema(Blueprint $table, string $columnName): void
    {
        $defaultSchema = [
            'type' => 'string',
            'nullable' => true,
        ];
        $columnSchema = array_replace($defaultSchema, $this->schema['columns'][$columnName]);

        $chain = [];
        foreach ($columnSchema as $key => $value) {
            if ($key == 'type') {
                array_unshift($chain, $value); // should be first all time
                continue;
            }
            if (in_array($key, ['nullable', 'index', 'unique']) && $value) {
                $chain[] = $key;
                continue;
            }
        }

        // max chain for the moment is 3 elements - type, nullable, index|unique
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

    /**
     * @return $this
     */
    private function buildTableSchema(): self
    {
        if (empty($this->schema['file_data'][0])) {
            return $this;
        }

        $getColumnsList = array_keys($this->schema['file_data'][0]);

        array_push(
            $getColumnsList,
            'ci_field_file_name', // index
            'ci_field_file_date', // index
            'ci_field_client_name', // index
            'ci_field_count',
            'ci_field_id', // index
            'ci_field_created_at',
            'ci_field_updated_at'
        );

        foreach ($getColumnsList as $column) {
            switch (true) {
                case strpos($column, '_id') !== false
                    || in_array($column, ['ci_field_file_name', 'ci_field_file_date', 'ci_field_client_name']):
                    $this->schema['columns'][$column] = [
                        'type' => 'string',
                        'index' => true,
                        'nullable' => true,
                    ];
                    break;
                case in_array($column, ['ci_field_created_at', 'ci_field_updated_at']):
                    $this->schema['columns'][$column] = [
                        'type' => 'timestamp',
                        'nullable' => true,
                    ];
                    break;
                case strpos($column, '_date') !== false:
                    $this->schema['columns'][$column] = [
                        'type' => 'timestamp',
                        'nullable' => true,
                        'index' => true,
                    ];
                    break;
                default:
                    $this->schema['columns'][$column] = [
                        'type' => 'string',
                        'nullable' => true,
                    ];
            }
        }

        if ($this->importInstruction) {
            $this->schema['columns'] = array_replace(
                $this->schema['columns'],
                $this->importInstruction->addTableColumns()
            );
        }

        return $this;
    }

    /**
     * @return self
     */
    private function normalizeFileData(): self
    {
        $rows = explode(PHP_EOL, $this->rawFileContent);
        $this->setDelimiter($rows[0]);

        // get header
        $headers = str_getcsv(strtolower($rows[0]), $this->schema['delimiter']);
        array_shift($rows); // remove header row

        // normalize header
        $headers = array_map(function ($value) {
            if (empty($value)) {
                return 'ci_empty_'.uniqid();
            }
            return $value;
        }, $headers);

        // normalize import data
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }

            if (preg_match('('.implode('|', self::META_FIELDS_START_WITH).')', $row)) {
                $this->schema['file_meta'][] = $row;
                continue;
            }

            // parse row
            $rowColumns = str_getcsv($row, $this->schema['delimiter']);
            $rowColumnsSize = count($rowColumns);

            $this->schema['file_data'][] = $rowColumns;

            if ($this->schema['columns_size'] < $rowColumnsSize) {
                $this->schema['columns_size'] = $rowColumnsSize;
            }
        }

        // add missing columns
        if (count($headers) < $this->schema['columns_size']) {
            $this->arrayAddElements(
                $headers,
                $this->schema['columns_size'] - count($headers),
                true
            );
        }

        // combine import data
        foreach ($this->schema['file_data'] as $index => &$data) {
            if (count($data) < $this->schema['columns_size']) {
                $this->arrayAddElements(
                    $data,
                    $this->schema['columns_size'] - count($data)
                );
            }

            $data = array_combine($headers, $data);
        }

        return $this;
    }

    /**
     * @param array $toArray
     * @param int $countToAdd
     * @param bool $header
     */
    private function arrayAddElements(array &$toArray, int $countToAdd, bool $header = false): void
    {
        for ($i=0;$i<$countToAdd;$i++) {
            array_push($toArray, $header ? 'ci_add_'.uniqid() : '');
        }
    }

    /**
     * @param string $firstLine
     */
    private function setDelimiter(string $firstLine): void
    {
        if (substr_count($firstLine, ';') < substr_count($firstLine, ',')) {
            $this->schema['delimiter'] = ',';
        }

        $this->schema['delimiter'] = ';';
    }

    /**
     * @param string $output
     */
    private function logOutput(string $output): void
    {
        if ($this->showOutput) {
            echo $output.PHP_EOL;
        }
    }
}
