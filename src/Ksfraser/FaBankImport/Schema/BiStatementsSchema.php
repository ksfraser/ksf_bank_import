<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_statements` entity.
 */
final class BiStatementsSchema
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
            'entity' => 'bi_statements',
            'table' => 'bi_statements',
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
                'bank' => array(
                    'label' => 'Bank',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'account' => array(
                    'label' => 'Account',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'currency' => array(
                    'label' => 'Currency',
                    'type' => 'varchar(3)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'startBalance' => array(
                    'label' => 'Start Balance',
                    'type' => 'double',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'endBalance' => array(
                    'label' => 'End Balance',
                    'type' => 'double',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'smtDate' => array(
                    'label' => 'Statement Date',
                    'type' => 'date',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'number' => array(
                    'label' => 'Number',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'seq' => array(
                    'label' => 'Sequence',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'statementId' => array(
                    'label' => 'Statement ID',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'acctid' => array(
                    'label' => 'Acct ID',
                    'type' => 'varchar(64)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'fitid' => array(
                    'label' => 'FITID',
                    'type' => 'varchar(64)',
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
            ),
            'ui' => array(
                'title' => 'Bank Import Statements',
                'pageSize' => 25,
                'listColumns' => array('id', 'smtDate', 'bank', 'account', 'currency', 'startBalance', 'endBalance', 'statementId'),
                'formFields' => array('bank', 'account', 'currency', 'startBalance', 'endBalance', 'smtDate', 'number', 'seq', 'statementId', 'acctid', 'fitid', 'bankid', 'intu_bid'),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                    array('title' => 'Edit', 'action' => 'edit', 'form' => 'edit_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
