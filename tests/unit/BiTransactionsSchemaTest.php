<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Schema\BiTransactionsSchema;

class BiTransactionsSchemaTest extends TestCase
{
    public function testDescriptorHasStableShapeAndIsMemoized(): void
    {
        $a = BiTransactionsSchema::descriptor();
        $this->assertIsArray($a);
        $this->assertSame('bi_transactions', $a['entity']);
        $this->assertSame('bi_transactions', $a['table']);
        $this->assertSame('id', $a['primaryKey']);

        $this->assertArrayHasKey('fields', $a);
        $this->assertIsArray($a['fields']);

        // Spot-check a few fields.
        $this->assertArrayHasKey('id', $a['fields']);
        $this->assertSame('int(11)', $a['fields']['id']['type']);
        $this->assertTrue($a['fields']['id']['auto_increment']);

        $this->assertArrayHasKey('updated_ts', $a['fields']);
        $this->assertSame('timestamp', $a['fields']['updated_ts']['type']);

        $this->assertArrayHasKey('transactionAmount', $a['fields']);
        $this->assertSame('double', $a['fields']['transactionAmount']['type']);

        $this->assertArrayHasKey('ui', $a);
        $this->assertSame('Bank Import Transactions', $a['ui']['title']);
        $this->assertSame(
            ['id', 'valueTimestamp', 'transactionTitle', 'transactionAmount', 'transactionDC', 'status', 'matched', 'created'],
            $a['ui']['listColumns']
        );

        // Memoization: second call should be identical.
        $b = BiTransactionsSchema::descriptor();
        $this->assertSame($a, $b);
    }

    public function testFieldNamesCoversAllCreateTableColumns(): void
    {
        $fields = BiTransactionsSchema::fieldNames();
        $this->assertIsArray($fields);
        $this->assertCount(30, $fields);
        $this->assertContains('smt_id', $fields);
        $this->assertContains('fa_trans_no', $fields);
        $this->assertContains('g_partner', $fields);
    }

    public function testTableNameUsesTbPrefIfAvailable(): void
    {
        if (!defined('TB_PREF')) {
            define('TB_PREF', '0_');
        }

        $this->assertSame('0_bi_transactions', BiTransactionsSchema::tableName());
        $this->assertSame('X_bi_transactions', BiTransactionsSchema::tableName('X_'));
    }
}
