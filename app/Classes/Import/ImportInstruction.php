<?php

namespace App\Classes\Import;

/**
 * Interface ImportInstruction
 * @package App\Classes\Import
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
     * @param array $row
     *
     * @return array
     */
    public function addRowFields(array $row): array;

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
    public function addTableColumns(): array;

    /**
     * Specify column names, by witch we will try to update fields if file is modified or status is failed
     *
     * @return array
     */
    public function updateByColumns(): array;
}
