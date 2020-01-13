<?php

namespace App\Classes\Import\Instructions;

use App\Classes\Import\AbstractInstruction;

/**
 * Class EcpIcppTable
 * @package App\Classes\Import\Instructions
 */
class EcpIcppTable extends AbstractInstruction
{
    /**
     * @param array $row
     * @return array
     */
    public function addRowFields(array $row): array
    {
        return [
            'column_test' => $row['ci_field_id'].'zzz',
        ];
    }

    /**
     * @return array
     */
    public function addTableColumns(): array
    {
        return [
            'column_test' => [
                'type' => 'string',
                'nullable' => true,
                'index' => true,
                'unique' => false,
            ]
        ];
    }
}
