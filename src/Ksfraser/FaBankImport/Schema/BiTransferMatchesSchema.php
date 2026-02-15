<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for `bi_transfer_matches`.
 */
final class BiTransferMatchesSchema
{
    use \Ksfraser\ModulesDAO\Schema\SchemaDescriptorHelpersTrait;

    /** @var array|null */
    private static $descriptor;

    public static function descriptor(): array
    {
        if (self::$descriptor !== null) {
            return self::$descriptor;
        }

        self::$descriptor = array(
            'entity' => 'bi_transfer_matches',
            'table' => 'bi_transfer_matches',
            'primaryKey' => 'id',
            'fields' => array(
                'id' => array(
                    'label' => 'ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'auto_increment' => true,
                ),
                'debit_transaction_id' => array(
                    'label' => 'Debit Transaction',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'credit_transaction_id' => array(
                    'label' => 'Credit Transaction',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'from_transaction_id' => array(
                    'label' => 'From Transaction',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'to_transaction_id' => array(
                    'label' => 'To Transaction',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'match_status' => array(
                    'label' => 'Status',
                    'type' => 'varchar(16)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => "'candidate'",
                ),
                'match_confidence' => array(
                    'label' => 'Confidence',
                    'type' => 'decimal(5,2)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'match_group' => array(
                    'label' => 'Match Group',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'requires_review' => array(
                    'label' => 'Requires Review',
                    'type' => 'int(1)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'source' => array(
                    'label' => 'Source',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => "'auto'",
                ),
                'reason_code' => array(
                    'label' => 'Reason Code',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'notes' => array(
                    'label' => 'Notes',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'suggested_at' => array(
                    'label' => 'Suggested At',
                    'type' => 'datetime',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => 'CURRENT_TIMESTAMP',
                ),
                'confirmed_at' => array(
                    'label' => 'Confirmed At',
                    'type' => 'datetime',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'confirmed_by' => array(
                    'label' => 'Confirmed By',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'updated_ts' => array(
                    'label' => 'Updated',
                    'type' => 'timestamp',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'default' => 'CURRENT_TIMESTAMP',
                    'on_update' => 'CURRENT_TIMESTAMP',
                ),
            ),
            'db' => array(
                'engine' => 'InnoDB',
                'charset' => 'utf8',
                'uniqueConstraints' => array(
                    array(
                        'name' => 'uniq_debit_credit',
                        'columns' => array('debit_transaction_id', 'credit_transaction_id'),
                    ),
                ),
                'indexes' => array(
                    array('name' => 'idx_status', 'columns' => array('match_status')),
                    array('name' => 'idx_review', 'columns' => array('requires_review')),
                    array('name' => 'idx_debit', 'columns' => array('debit_transaction_id')),
                    array('name' => 'idx_credit', 'columns' => array('credit_transaction_id')),
                ),
            ),
            'ui' => array(
                'title' => 'Transfer Matches',
            ),
        );

        return self::$descriptor;
    }
}
