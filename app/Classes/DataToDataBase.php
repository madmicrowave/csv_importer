<?php

namespace App\Console\Classes;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use ErrorException;

/**
 * Class DataToDataBase
 * @package App\Console\Classes
 */
class DataToDataBase
{
    /** @var array */
    public $importResult = [
        'errors' => null,
        'status' => null,
        'meta' => null,
    ];

    /** @var array */
    private $schema;

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
    }

    /**
     * @return array
     */
    public function import(): array
    {
        $this
            ->normalizeFileSchema()
            ->modifyFileSchemaFields()
            ->modifyAdditionalSchemaFields()
            ->prepareTableAndColumns()
            ->saveDataToDatabase();

        $this->logOutput('Finished! Records imported: '.(is_array($this->schema['data']) ? count($this->schema['data']) : 0));

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
    protected function modifyFileSchemaFields(): self
    {
        foreach ($this->schema['data'] as &$data) {
            foreach ($data as $key => &$value) {
                if (strpos($key, '_data') !== false) {
                    $value = Carbon::parse($value);
                }
            }
        }

        return $this;
    }

    /**
     * @param Blueprint $table
     * @param string $column
     */
    protected function buildTableColumn(Blueprint $table, string $column): void
    {
        switch (true) {
            case $column == 'ci_field_created_at';
                $table->timestamp('ci_field_created_at', 0)->nullable();
                break;
            case $column == 'ci_field_updated_at':
                $table->timestamp('ci_field_updated_at', 0)->nullable();
                break;
            case strpos($column, '_id') !== false
                || in_array($column, ['ci_field_file_name', 'ci_field_client_name', 'ci_field_id', 'ci_field_file_date']):
                $table->string($column)->nullable()->index();
                break;
            case strpos($column, '_date'):
                $table->timestamp($column, 0)->nullable()->index();
                break;
            default:
                $table->string($column)->nullable();
        }
    }

    /**
     * @return $this
     */
    protected function modifyAdditionalSchemaFields(): self
    {
        $this->logOutput('Adding custom row data...');

        array_push(
            $this->schema['columns'],
            'ci_field_file_name', // index
            'ci_field_file_date', // index
            'ci_field_client_name', // index
            'ci_field_count',
            'ci_field_id', // index
            'ci_field_created_at',
            'ci_field_updated_at'
        );

        $fileNameParts = explode(
            '_',
            str_replace('.'.pathinfo($this->fileName, PATHINFO_EXTENSION), '', $this->fileName)
        );

        foreach ($this->schema['data'] as &$data) {
            $data['ci_field_file_name'] = $this->fileName;
            $data['ci_field_file_date'] = $fileNameParts[4]; // make timestamp if numeric
            $data['ci_field_client_name'] = $fileNameParts[2];
            $data['ci_field_count'] = $fileNameParts[5];
            $data['ci_field_id'] = $fileNameParts[3];
            $data['ci_field_created_at'] = Carbon::now();
            $data['ci_field_updated_at'] = null;
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function prepareImportResult(): self
    {
        $this->importResult['errors'] = $this->schema['errors'];
        $this->importResult['status'] = empty($this->schema['meta_data']['query_exceptions']);
        $this->importResult['meta'] = $this->schema['meta_data'];

        return $this;
    }

    /**
     * @return $this
     */
    private function prepareTableAndColumns(): self
    {
        $this->logOutput('Verifying table and columns...');

        if (Schema::hasTable($this->tableName)) {
            $schemaColumns = Schema::getColumnListing($this->tableName);

            $newColumns = [];
            foreach ($this->schema['columns'] as $column) {
                if (!in_array($column, $schemaColumns) && $this->schema['columns'] != 'id') {
                    $newColumns[] = $column;
                }
            }

            if (!empty($newColumns)) {
                $this->logOutput('Adding new columns...');

                try {
                    Schema::table($this->tableName, function (Blueprint $table) use ($newColumns) {
                        foreach ($newColumns as $column) {
                            $this->buildTableColumn($table, $column);
                        }
                    });

                    $this->logOutput('Columns created: ' . implode(',', $newColumns));
                } catch (QueryException $e) {
                    $this->logOutput('WARNING: '.$e->getMessage());
                }
            }

            return $this;
        }

        $this->logOutput('Creating new "'.$this->tableName.'" schema...');

        try {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->bigIncrements('id');

                foreach ($this->schema['columns'] as $column) {
                    $this->buildTableColumn($table, $column);
                }
            });

            $this->logOutput('Table "'.$this->tableName.'" and columns: "'.implode(',', $this->schema['columns']).'" created');
        } catch (QueryException $e) {
            $this->logOutput('WARNING: '.$e->getMessage());
        }

        return $this;
    }

    /**
     * TODO: update if exists
     *
     * @return $this
     */
    private function saveDataToDatabase(): self
    {
        if (empty($this->schema['data'])) {
            $this->logOutput('Nothing to import...');
            return $this
                ->prepareImportResult();
        }

        $this->logOutput('Importing data...');

        try {
            DB::table($this->tableName)
                ->insert($this->schema['data']);

            $this->logOutput('Import success...');
        } catch(QueryException $e){
            $this->schema['meta_data']['query_exceptions'][] = $e->getMessage();
            $this->logOutput('UpdateOrInsert exception:'. $e->getMessage());
        }

        return $this
            ->prepareImportResult();
    }

    /**
     * @return self
     */
    private function normalizeFileSchema(): self
    {
        $this->schema = [
            'columns' => [],
            'columns_size' => 0,
            'data' => [],
            'meta_data' => [],
            'errors' => [],
        ];

        $rows = explode(PHP_EOL, $this->rawFileContent);
        $delimiter = $this->getDelimiter($rows[0]);

        // get header
        $this->schema['columns'] = str_getcsv(strtolower($rows[0]), $delimiter);
        array_shift($rows); // remove headers row

        // normalize import data
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }

            if (preg_match('('.implode('|', self::META_FIELDS_START_WITH).')', $row)) {
                $this->schema['meta_data'][] = $row;
                continue;
            }

            $rowColumns = str_getcsv($row, $delimiter);
            $rowColumnsSize = count($rowColumns);
            $this->schema['data'][] = $rowColumns;

            if ($this->schema['columns_size'] < $rowColumnsSize) {
                $this->schema['columns_size'] = $rowColumnsSize;
            }
        }

        // normalize header
        $this->schema['columns'] = array_map(function ($value) {
            if (empty($value)) {
                return 'ci_empty_'.uniqid();
            }
            return $value;
        }, $this->schema['columns']);

        // add missing columns
        if (count($this->schema['columns']) < $this->schema['columns_size']) {
            $this->arrayAddElements(
                $this->schema['columns'],
                $this->schema['columns_size'] - count($this->schema['columns']),
                true
            );
        }

        // combine import data
        foreach ($this->schema['data'] as $index => &$data) {
            if (count($data) < $this->schema['columns_size']) {
                $this->arrayAddElements(
                    $data,
                    $this->schema['columns_size'] - count($data)
                );
            }

            $data = array_combine($this->schema['columns'], $data);
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
     * @return string
     */
    private function getDelimiter(string $firstLine): string
    {
        if (substr_count($firstLine, ';') < substr_count($firstLine, ',')) {
            return ',';
        }

        return ';';
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

    const META_FIELDS_START_WITH = ['-----', 'rows=', 'timestamp='];
}
