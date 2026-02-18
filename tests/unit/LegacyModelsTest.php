<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verification test for modernized legacy models.
 * Ensures all class.bi_*.php files are correctly included in the autoloader
 * and can be instantiated without manual require_once.
 */
class LegacyModelsTest extends TestCase
{
    /**
     * @dataProvider legacyModelProvider
     */
    public function testLegacyModelInstantiation(string $className): void
    {
        $this->assertTrue(class_exists($className), "Class $className should be discoverable via autoloader");
        $instance = new $className();
        $this->assertInstanceOf($className, $instance);
    }

    public function legacyModelProvider(): array
    {
        return [
            ['bi_bank_accounts_model'],
            ['bi_counterparty_model'],
            ['bi_lineitem'],
            ['bi_partners_data'],
            ['bi_statements_model'],
            ['bi_transaction'],
            ['bi_transactionTitle_model'],
            ['bi_transactions_model'],
            ['bi_transfer_matches_model'],
        ];
    }
}
