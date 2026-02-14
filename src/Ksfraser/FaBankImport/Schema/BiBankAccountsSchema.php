<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiBankAccountsSchema [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiBankAccountsSchema.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_bank_accounts` entity.
 */
final class BiBankAccountsSchema
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
            'entity' => 'bi_bank_accounts',
            'table' => 'bi_bank_accounts',
            'primaryKey' => 'id',
            'fields' => array(
                'id' => array(
                    'label' => 'ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'auto_increment' => true,
                ),
                'bank_account_id' => array(
                    'label' => 'FA Bank Account ID',
                    'type' => 'smallint(6)',
                    'null' => 'NOT NULL',
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
                'intu_bid' => array(
                    'label' => 'Intuit BID',
                    'type' => 'varchar(64)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => "''",
                ),
                'bankid' => array(
                    'label' => 'Bank ID',
                    'type' => 'varchar(64)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => "''",
                ),
                'acctid' => array(
                    'label' => 'Acct ID',
                    'type' => 'varchar(64)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => "''",
                ),
                'accttype' => array(
                    'label' => 'Acct Type',
                    'type' => 'varchar(32)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'curdef' => array(
                    'label' => 'Currency',
                    'type' => 'varchar(3)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
            ),
            'db' => array(
                'engine' => 'InnoDB',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'uniqueConstraints' => array(
                    array(
                        'name' => 'uniq_detected_identity',
                        'columns' => array('acctid', 'bankid', 'intu_bid'),
                    ),
                ),
                'indexes' => array(
                    array('name' => 'idx_bank_account_id', 'columns' => array('bank_account_id')),
                    array('name' => 'idx_acctid', 'columns' => array('acctid')),
                    array('name' => 'idx_bankid', 'columns' => array('bankid')),
                    array('name' => 'idx_intu_bid', 'columns' => array('intu_bid')),
                ),
            ),
            'ui' => array(
                'title' => 'Detected Bank Accounts',
                'pageSize' => 50,
                'listColumns' => array('id', 'bank_account_id', 'acctid', 'bankid', 'intu_bid', 'accttype', 'curdef', 'updated_ts'),
                'formFields' => array('bank_account_id', 'acctid', 'bankid', 'intu_bid', 'accttype', 'curdef'),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                    array('title' => 'Add', 'action' => 'add', 'form' => 'add_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
