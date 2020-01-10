<?php

namespace App\Console\Classes;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//use Illuminate\Support\Facades\DB;

/**
 * Class TableCreator
 * @package App\Console\Classes
 *
 * $columns = [
 *    ...
 *    'columnName' => [
 *        'type' => 'string|integer|text',
 *        'length' => 11,
 *    ],
 *    ...
 * ];
 */
class TableCreator
{
    /** @var string */
    private $tableName;

    /** @var array */
    private $tableColumns;

    /**
     * TableCreator constructor.
     * @param string $fileName
     * @param array $columns
     */
    public function __construct(string $fileName, array $columns)
    {
        $this->tableName = $this->getName($fileName);
        $this->tableColumns = $columns;
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getName(string $fileName): string
    {
        $parts = explode('_', strtolower($fileName));

        return printf('%s_%s', $parts[0], $parts[1]);
    }

    /**
     * @return void
     */
    public function createTable(): void
    {
        $columns = $this->tableColumns;
        if (!Schema::hasTable($this->tableName) && $columns) {
            Schema::create($this->tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $columnName => $columnData) {
                    $table->$columnData['type']($columnName, $columnData['length'])->nullable();
                }
            });

            return;
        }

        print_r(Schema::getColumnListing($this->tableName));
    }

    public function addColumns()
    {

    }
}
