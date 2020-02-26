<?php

namespace App\Classes\Import\Instruction;

use App\Classes\Import\Core\AbstractInstruction;

/**
 * TBD?: discuss moving migration schema to json file
 *
 * Class EcpIcppTable
 * @package App\Classes\Import\Instruction
 */
class EcpIcppTable extends AbstractInstruction
{
    /**
     * @return array
     */
    public function columnsSchema(): array
    {
        return [
            'filename' => [
                'type' => 'string',
                'index' => true,
            ],
            'report_date' => [
                'type' => 'date',
                'index' => true,
                'format' => 'd.m.Y',
            ],
            'scheme_fee_assist' => [
                'type' => 'string',
                'index' => true,
            ],
            'interchange_assist' => [
                'type' => 'string',
                'index' => true,
            ],
            'card_product_platform' => [
                'type' => 'string',
                'index' => true,
                'length' => 18,
            ],
            'security_level' => [
                'type' => 'string',
                'index' => true,
                'length' => 7,
            ],
            'region' => [
                'type' => 'string',
                'index' => true,
                'length' => 3,
            ],
            'payment_id' => [
                'type' => 'string',
                'index' => true,
            ],
            'card' => [
                'type' => 'string',
                'length' => 11,
            ],
            'merchant_name' => [
                'type' => 'string',
            ],
            'merchant_id' => [
                'type' => 'string',
                'index' => true,
                'length' => 8,
            ],
            'terminal_id' => [
                'type' => 'string',
                'index' => true,
                'length' => 18,
            ],
            'card_type_name' => [
                'type' => 'string',
                'index' => true,
                'length' => 5,
            ],
            'acq_ref_nr' => [
                'type' => 'string',
                'index' => true,
                'length' => 23,
            ],
            'tr_batch_id' => [
                'type' => 'string',
            ],
            'tr_batch_open_date' => [
                'type' => 'date',
                'format' => 'Y.m.d',
            ],
            'tr_date_time' => [
                'type' => 'datetime',
                'format' => 'Y.m.d H:i:s',
            ],
            'tr_type' => [
                'type' => 'string',
                'index' => true,
                'length' => 2,
            ],
            'tr_amount' => [
                'type' => 'integer',
            ],
            'tr_ccy' => [
                'type' => 'string',
                'index' => true,
                'length' => 3,
            ],
            'tr_ret_ref_nr' => [
                'type' => 'string',
            ],
            'tr_approval_id' => [
                'type' => 'string',
                'index' => true,
            ],
            'tr_processing_date' => [
                'type' => 'datetime',
                'format' => 'Y.m.d H:i:s',
            ],
            'proc_code' => [
                'type' => 'string',
                'index' => true,
                'length' => 2,
            ],
            'issuer_country' => [
                'type' => 'string',
                'index' => true,
                'length' => 3,
            ],
            'proc_region' => [
                'type' => 'string',
            ],
            'mcc' => [
                'type' => 'string',
                'index' => true,
                'length' => 4,
            ],
            'merchant_country' => [
                'type' => 'string',
                'index' => true,
                'length' => 3,
            ],
            'tran_region' => [
                'type' => 'string',
            ],
            'tr_date_time_utc' => [
                'type' => 'datetime',
                'index' => true,
                'format' => 'Y.m.d H:i:s',
            ],
            'original_payment_id' => [
                'type' => 'string',
                'nullable' => true,
            ],
            'eci' => [
                'type' => 'string',
                'length' => 3,
            ],
            'order_id' => [
                'type' => 'string',
                'index' => true,
            ],
            'product_code' => [
                'type' => 'string',
                'length' => 3,
            ],
            'product_name' => [
                'type' => 'string',
            ],
            'account_funding_source' => [
                'type' => 'string',
            ],
            'card_product_class' => [
                'type' => 'string',
                'index' => true,
                'length' => 10,
            ],
            'list_transaction_type' => [
                'type' => 'string',
            ],
            'central_processing_date' => [
                'type' => 'date',
                'index' => true,
                'format' => 'Y.m.d',
            ],
            'reconciliation_amount' => [
                'type' => 'integer',
                'index' => true,
            ],
            'reconciliation_ccy' => [
                'type' => 'string',
                'index' => true,
                'length' => 3,
            ],
            'interchange_fee_recon' => [
                'type' => 'decimal',
            ],
            'interchange_amount_fee_recon' => [
                'type' => 'decimal',
            ],
            'interchange_fee_sign' => [
                'type' => 'string',
                'index' => true,
                'length' => 1,
            ],
            'auth' => [
                'type' => 'decimal',
            ],
            'avs' => [
                'type' => 'decimal',
            ],
            'clearing' => [
                'type' => 'decimal',
            ],
            'clearing_discount' => [
                'type' => 'decimal',
            ],
            'oct' => [
                'type' => 'decimal',
            ],
            'rev' => [
                'type' => 'decimal',
            ],
            'ias' => [
                'type' => 'decimal',
            ],
            'cnp' => [
                'type' => 'decimal',
            ],
            'association' => [
                'type' => 'decimal',
            ],
            'ecom/mo\to' => [
                'type' => 'decimal',
            ],
            'cross_border' => [
                'type' => 'decimal',
            ],
            'settlement_non_regional_ccys' => [
                'type' => 'decimal',
            ],
            'settlement_regional_ccys' => [
                'type' => 'decimal',
            ],
            'market_development' => [
                'type' => 'decimal',
            ],
            'e-com_development' => [
                'type' => 'decimal',
            ],
            'quarterly_e-com' => [
                'type' => 'decimal',
            ],
            'quarterly_mo\to' => [
                'type' => 'decimal',
            ],
            'visa_direct' => [
                'type' => 'decimal',
            ],
            'authentication' => [
                'type' => 'decimal',
            ],
            'visa_direct_isa' => [
                'type' => 'decimal',
            ],
            'quarterly_minimum_volume' => [
                'type' => 'decimal',
            ],
            'quarterly_volume' => [
                'type' => 'decimal',
            ],
            'quarterly_quantity' => [
                'type' => 'decimal',
            ],
            'quarterly_authentication' => [
                'type' => 'decimal',
            ],
            'specific_gwt_1' => [
                'type' => 'decimal',
            ],
            'specific_gwt_2' => [
                'type' => 'decimal',
            ],
            'moneysend_b2c' => [
                'type' => 'decimal',
            ],
            'moneysend_b2c_switch' => [
                'type' => 'decimal',
            ],
            'authorisation_processor_fee' => [
                'type' => 'decimal',
            ],
            'transaction_processor_fee' => [
                'type' => 'decimal',
            ],
            'total_interchange' => [
                'type' => 'decimal',
            ],
            'total_scheme_fee' => [
                'type' => 'decimal',
            ],
            'rounding_difference_scheme_fee' => [
                'type' => 'decimal',
            ],
            'total_processor_fee' => [
                'type' => 'decimal',
            ],
            'rounding_difference_processor_fee' => [
                'type' => 'decimal',
            ],
        ];
    }

    /**
     * @return array
     */
    public function uniqueIndexByColumns(): array
    {
        return [
            'tr_type', 'proc_code', 'reconciliation_amount'
        ];
    }
}
