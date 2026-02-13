<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_transactions` entity.
 *
 * This is intentionally:
 * - static + memoized
 * - environment-agnostic (does not require TB_PREF to exist)
 *
 * Consumers can apply an FA prefix at runtime.
 */
final class BiTransactionsSchema
{
    use \Ksfraser\ModulesDAO\Schema\SchemaDescriptorHelpersTrait;

    /** @var array|null */
    private static $descriptor;

    /**
     * Return a memoized descriptor array.
     *
     * Shape (stable contract):
     * - entity: string
     * - table: string (unprefixed)
     * - primaryKey: string
     * - fields: array<string,array>
     * - ui: array (optional)
     *
     * @return array
     */
    public static function descriptor(): array
    {
        if (self::$descriptor !== null) {
            return self::$descriptor;
        }

        // Source of truth for columns: sql/update.sql (module activation, non-destructive).
        self::$descriptor = array(
            'entity' => 'bi_transactions',
            'table' => 'bi_transactions',
            'primaryKey' => 'id',
            'fields' => array(
                'id' => array(
                    'label' => 'ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'auto_increment' => true,
                ),
                'updated_ts' => array(
                    'label' => 'Updated',
                    'type' => 'timestamp',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'default' => 'CURRENT_TIMESTAMP',
                ),
                'smt_id' => array(
                    'label' => 'Statement ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'valueTimestamp' => array(
                    'label' => 'Value Date',
                    'type' => 'date',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'entryTimestamp' => array(
                    'label' => 'Entry Date',
                    'type' => 'date',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'account' => array(
                    'label' => 'Account',
                    'type' => 'varchar(24)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'accountName' => array(
                    'label' => 'Account Name',
                    'type' => 'varchar(60)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'transactionType' => array(
                    'label' => 'Transaction Type',
                    'type' => 'varchar(3)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'transactionCode' => array(
                    'label' => 'Transaction Code',
                    'type' => 'varchar(255)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'transactionCodeDesc' => array(
                    'label' => 'Transaction Code Desc',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'transactionDC' => array(
                    'label' => 'D/C',
                    'type' => 'varchar(2)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'transactionAmount' => array(
                    'label' => 'Amount',
                    'type' => 'double',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'transactionTitle' => array(
                    'label' => 'Title',
                    'type' => 'varchar(256)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'status' => array(
                    'label' => 'Status',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'matchinfo' => array(
                    'label' => 'Match Info',
                    'type' => 'varchar(256)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'fa_trans_type' => array(
                    'label' => 'FA Trans Type',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'fa_trans_no' => array(
                    'label' => 'FA Trans No',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'fitid' => array(
                    'label' => 'FITID',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'acctid' => array(
                    'label' => 'Acct ID',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'bankid' => array(
                    'label' => 'Bank ID',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'intu_bid' => array(
                    'label' => 'Intuit BID',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'merchant' => array(
                    'label' => 'Merchant',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'category' => array(
                    'label' => 'Category',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'sic' => array(
                    'label' => 'SIC',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'memo' => array(
                    'label' => 'Memo',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'checknumber' => array(
                    'label' => 'Check No',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'matched' => array(
                    'label' => 'Matched',
                    'type' => 'int(1)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'created' => array(
                    'label' => 'Created',
                    'type' => 'int(1)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'g_partner' => array(
                    'label' => 'Partner Type',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'g_option' => array(
                    'label' => 'Partner Option',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
            ),
            'ui' => array(
                'title' => 'Bank Import Transactions',
                'pageSize' => 50,
                'listColumns' => array(
                    'id',
                    'valueTimestamp',
                    'transactionTitle',
                    'transactionAmount',
                    'transactionDC',
                    'status',
                    'matched',
                    'created',
                ),
                'formFields' => array(
                    'smt_id',
                    'valueTimestamp',
                    'entryTimestamp',
                    'transactionTitle',
                    'transactionAmount',
                    'transactionDC',
                    'memo',
                    'status',
                    'matchinfo',
                    'fa_trans_type',
                    'fa_trans_no',
                    'matched',
                    'created',
                    'g_partner',
                    'g_option',
                ),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                    array('title' => 'Edit', 'action' => 'edit', 'form' => 'edit_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
