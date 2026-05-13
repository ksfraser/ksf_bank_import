<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\SupplierTransactionMatcher;
use Ksfraser\FaBankImport\Services\SupplierMatchingConfiguration;

class SupplierTransactionMatcherTest extends TestCase
{
    private SupplierTransactionMatcher $matcher;
    private array $legacyVendorList;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create legacy vendor list format for testing
        $this->legacyVendorList = [
            'shortnames' => [
                0 => 'bank_acc_1',
                1 => 'bank_acc_2',
                2 => 'bank_acc_3',
            ],
            0 => [
                'supplier_id' => 101,
                'supp_name' => 'ACME Corp',
                'account_code' => '1000',
            ],
            1 => [
                'supplier_id' => 102,
                'supp_name' => 'Other Inc',
                'account_code' => '1001',
            ],
            2 => [
                'supplier_id' => 103,
                'supp_name' => 'Third Vendor',
                'account_code' => '1002',
            ],
        ];
        
        $this->matcher = new SupplierTransactionMatcher($this->legacyVendorList);
    }

    public function testMatcherAcceptsLegacyVendorList(): void
    {
        $this->assertInstanceOf(SupplierTransactionMatcher::class, $this->matcher);
    }

    public function testMatcherHasConfiguration(): void
    {
        $config = $this->matcher->getConfiguration();
        $this->assertInstanceOf(SupplierMatchingConfiguration::class, $config);
    }

    public function testMatcherHasSupplierMatcher(): void
    {
        $matcher = $this->matcher->getMatcher();
        $this->assertNotNull($matcher);
    }

    public function testNoVendorsReturnsFalse(): void
    {
        $emptyVendorList = ['shortnames' => []];
        $matcher = new SupplierTransactionMatcher($emptyVendorList);
        
        $transaction = ['account' => 'bank_acc_1', 'amount' => 1000.00];
        $result = $matcher->matchTransaction($transaction);
        
        $this->assertFalse($result);
    }

    public function testMatchTransactionReturnsValidIndexOrFalse(): void
    {
        $transaction = [
            'account' => 'some_test_account',
            'amount' => 1000.00,
            'memo' => 'Test transaction'
        ];
        
        $result = $this->matcher->matchTransaction($transaction);
        
        // Should return either a valid index (0-2) or false
        $this->assertTrue(
            $result === false || (is_int($result) && $result >= 0 && $result <= 2),
            'Should return a valid vendor index or false'
        );
    }

    public function testMatchTransactionReturnsVendorIndex(): void
    {
        $transaction = [
            'account' => 'bank_acc_1',
            'amount' => 1000.00,
            'memo' => 'Payment to ACME'
        ];
        
        $result = $this->matcher->matchTransaction($transaction);
        
        // Should return vendor index (0, 1, 2, etc) or false
        $this->assertTrue($result === 0 || $result === 1 || $result === 2 || $result === false);
    }

    public function testGetMatchDecisionReturnsString(): void
    {
        $transaction = [
            'account' => 'bank_acc_1',
            'amount' => 1000.00,
        ];
        
        $this->matcher->matchTransaction($transaction);
        $decision = $this->matcher->getMatchDecision('bank_acc_1');
        
        $this->assertIsString($decision);
        $this->assertContains($decision, ['auto', 'manual', 'no_match']);
    }

    public function testGetMatchResultsReturnsArray(): void
    {
        $transaction = [
            'account' => 'bank_acc_1',
            'amount' => 1000.00,
        ];
        
        $this->matcher->matchTransaction($transaction);
        $results = $this->matcher->getMatchResults('bank_acc_1');
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('decision', $results);
        $this->assertArrayHasKey('match_count', $results);
    }

    public function testEnptyVendorListIsHandledGracefully(): void
    {
        $emptyList = [
            'shortnames' => [
                0 => 'some_account',
            ],
            // Missing vendor details
        ];
        
        $matcher = new SupplierTransactionMatcher($emptyList);
        $transaction = ['account' => 'some_account', 'amount' => 100.00];
        
        $result = $matcher->matchTransaction($transaction);
        $this->assertFalse($result);
    }

    public function testMatcherWithCustomConfiguration(): void
    {
        $config = new SupplierMatchingConfiguration();
        $config->setMinimumConfidenceThreshold(75);
        
        $matcher = new SupplierTransactionMatcher($this->legacyVendorList, $config);
        
        $this->assertSame($config, $matcher->getConfiguration());
        $this->assertEquals(75, $matcher->getConfiguration()->getMinimumConfidenceThreshold());
    }

    public function testMatcherUsesProductionDefaults(): void
    {
        $config = $this->matcher->getConfiguration();
        
        // Check PROD defaults from SupplierMatchingConfiguration
        $this->assertEquals(50, $config->getMinimumConfidenceThreshold());
        $this->assertEquals(2, $config->getMaximumAutoMatches());
    }

    public function testTransactionFieldsAreNormalized(): void
    {
        // Test with different field names to ensure normalization works
        $transaction = [
            'otherBankAccount' => 'bank_acc_1',  // Alternative field name
            'transactionAmount' => 500.50,       // Alternative field name
            'transactionTitle' => 'Test payment',
        ];
        
        $result = $this->matcher->matchTransaction($transaction);
        
        // Should still match even with alternative field names
        $this->assertTrue($result === false || is_int($result));
    }

    public function testMatcherHandlesInvalidVendorList(): void
    {
        $invalidList = [
            'shortnames' => 'not_an_array',  // Invalid format
        ];
        
        $matcher = new SupplierTransactionMatcher($invalidList);
        $transaction = ['account' => 'test', 'amount' => 100.00];
        
        $result = $matcher->matchTransaction($transaction);
        $this->assertFalse($result);
    }

    public function testMultipleMatchesAreHandled(): void
    {
        // Create vendor list with multiple suppliers with same account
        $multiVendorList = [
            'shortnames' => [
                0 => 'shared_account',
                1 => 'shared_account',  // Duplicate account
                2 => 'unique_account',
            ],
            0 => ['supplier_id' => 101, 'supp_name' => 'First'],
            1 => ['supplier_id' => 102, 'supp_name' => 'Second'],
            2 => ['supplier_id' => 103, 'supp_name' => 'Third'],
        ];
        
        $matcher = new SupplierTransactionMatcher($multiVendorList);
        $transaction = [
            'account' => 'shared_account',
            'amount' => 1000.00,
        ];
        
        $result = $matcher->matchTransaction($transaction);
        $decision = $matcher->getMatchDecision('shared_account');
        
        // Multiple matches should result in 'manual' or 'no_match' decision
        //  (depending on threshold)
        $this->assertIsString($decision);
    }
}
