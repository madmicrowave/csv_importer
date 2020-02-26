<?php

namespace App\Classes\Import\Core;

/**
 * Interface ImportInstruction
 * @package App\Classes\Import\Core
 */
interface ImportInstruction
{
    /**
     * Used to add custom column to each row of data
     * Return format:
     *  [
     *    'column_name' => 'value',
     *    'column_name2' => 'value2',
     *  ]
     * @param array $currentRow
     *
     * @return array
     */
    public function addRowColumns(array $currentRow): array;

    /**
     * If added addRowFields(), you should specify schema for this field
     * Return format:
     *  [
     *     'column_name' => [
     *        'type' => 'string',
     *        'index' => true,
     *        'unique' => false
     *     ]
     *  ]
     *
     * @return array
     */
    public function columnsSchema(): array;

    /**
     * Specify column names, by witch we will try to update fields by this columns
     *
     * @return array
     */
    public function uniqueIndexByColumns(): array;

    /**
     * Common fields will be applied ot each row
     *
     * @return array
     */
    public function addCommonRowColumns(): array;

    /**
     * Common columns scheme will be applied to columns
     *
     * @return array
     */
    public function commonColumnsSchema(): array;
}
