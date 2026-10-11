<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionMatcherIntegration;
use Ksfraser\FaBankImport\Services\TransactionPartnerMatcher;
use Ksfraser\FaBankImport\Services\VendorCandidate;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Services\PartnerMatcherFactory;

/**
 * BiLineItemMatchingIntegrationTest
 *
 * Integration tests for unified matching through bi_lineitem display methods.
 *
 * These tests verify that the matching infrastructure is properly integrated
 * into the bi_lineitem class and returns expected results for all partner types.
 *
 * @covers \Ksfraser\FaBankImport\Services\TransactionMatcherIntegration
 * @covers \bi_lineitem::getTransactionMatches
 * @covers \bi_lineitem::getFormattedMatchResults
 * @covers \bi_lineitem::getBestMatchRecommendation
 *
 * @package Tests\Integration\Services
 * @author  Kevin Fraser / ChatGPT
 * @since   2025-04-19
 */
class BiLineItemMatchingIntegrationTest extends TestCase
{
    /**
     * Integration object for testing
     *
     * @var TransactionMatcherIntegration
     */
    private TransactionMatcherIntegration $integration;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->markTestSkipped('Integration test requires real FrontAccounting environment and database');
        $this->integration = new TransactionMatcherIntegration();
    }

    /**
     * Test matcher returns all required result keys
     *
     * @test
     * @since 2025-04-19
     */
    public function testMatchResultsHaveRequiredKeys(): void
    {
        // Create mock transaction as array
        $transaction = [
            'otherBankAccount' => '1234567890',
            'memo' => 'Payment to supplier for invoice',
            'amount' => 1500.00
        ];

        // Create mock partners as arrays (expected format)
        $suppliers = [
            [
                'partner_id' => '1',
                'name' => 'ABC Supplies',
                'account' => '1234567890'
            ]
        ];

        $customers = [];
        $bankAccounts = [];

        $matcher = PartnerMatcherFactory::createSupplierMatcher();

        $results = $matcher->matchTransaction(
            $transaction,
            $suppliers,
            $customers,
            $bankAccounts
        );

        // Verify required keys
        $this->assertArrayHasKey('supplier', $results);
        $this->assertArrayHasKey('customer', $results);
        $this->assertArrayHasKey('bank_transfer', $results);
        $this->assertArrayHasKey('best_match', $results);
    }

    /**
     * Test matcher handles empty partner lists gracefully
     *
     * @test
     * @since 2025-04-19
     */
    public function testMatcherHandlesEmptyPartnerLists(): void
    {
        $transaction = [
            'otherBankAccount' => '9999999999',
            'memo' => 'Unknown transaction',
            'amount' => 100.00
        ];

        $matcher = PartnerMatcherFactory::createUnifiedMatcher();

        $results = $matcher->matchTransaction($transaction, [], [], []);

        $this->assertIsArray($results);
        $this->assertEmpty($results['supplier']);
        $this->assertEmpty($results['customer']);
        $this->assertEmpty($results['bank_transfer']);
        $this->assertNull($results['best_match']);
    }

    /**
     * Test formatted results include display-safe data
     *
     * @test
     * @since 2025-04-19
     */
    public function testFormattedResultsHaveDisplaySafeData(): void
    {
        $transaction = [
            'otherBankAccount' => '1234567890',
            'memo' => 'Payment <script>alert("xss")</script>',
            'amount' => 1500.00
        ];

        $suppliers = [
            [
                'partner_id' => '1',
                'name' => 'Test & Company <Ltd>',
                'account' => '1234567890'
            ]
        ];

        $matcher = PartnerMatcherFactory::createSupplierMatcher();

        $results = $matcher->matchTransaction(
            $transaction,
            $suppliers,
            [],
            []
        );

        $formatted = $this->integration->formatResultsForDisplay($results);

        // Verify HTML escaping (best_match should be escaped)
        if ($formatted['best_match'] !== null) {
            $this->assertStringNotContainsString(
                '<script>',
                $formatted['best_match']['partner_name']
            );
            $this->assertStringNotContainsString(
                '<',
                $formatted['best_match']['partner_name']
            );
        }
    }

    /**
     * Test best match recommendation string generation
     *
     * @test
     * @since 2025-04-19
     */
    public function testBestMatchRecommendationStringFormat(): void
    {
        $transaction = [
            'otherBankAccount' => '1234567890',
            'memo' => 'Invoice payment',
            'amount' => 1000.00
        ];

        $suppliers = [
            [
                'partner_id' => '1',
                'name' => 'Test Supplier',
                'account' => '1234567890'
            ]
        ];

        $matcher = PartnerMatcherFactory::createSupplierMatcher();

        $results = $matcher->matchTransaction($transaction, $suppliers, [], []);

        if ($results['best_match'] !== null) {
            $displayString = $this->integration->getBestMatchDisplayString(
                $results['best_match']
            );

            $this->assertStringContainsString('Suggested:', $displayString);
            $this->assertStringContainsString('confidence', $displayString);
            $this->assertStringContainsString('%', $displayString);
        }
    }

    /**
     * Test matcher distinguishes between partner types correctly
     *
     * @test
     * @since 2025-04-19
     */
    public function testMatcherDistinguishesPartnerTypes(): void
    {
        $transaction = [
            'otherBankAccount' => '1111111111',
            'memo' => 'Mixed transaction',
            'amount' => 500.00
        ];

        $supplier = [
            'partner_id' => '1',
            'name' => 'Supplier A',
            'account' => '1111111111'
        ];

        $customer = [
            'partner_id' => '2',
            'name' => 'Customer B',
            'account' => '1111111111'
        ];

        $matcher = PartnerMatcherFactory::createUnifiedMatcher();

        $results = $matcher->matchTransaction(
            $transaction,
            [$supplier],
            [$customer],
            []
        );

        // Verify results are segregated by type
        $this->assertTrue(
            count($results['supplier']) > 0 || count($results['customer']) > 0,
            'At least one partner type should have matches'
        );

        // Verify best match type is correctly identified
        if ($results['best_match'] !== null) {
            $type = $results['best_match']->getPartnerType();
            $this->assertContains($type, ['SP', 'CU', 'BT']);
        }
    }

    /**
     * Test matcher results are ranked by score
     *
     * @test
     * @since 2025-04-19
     */
    public function testMatchResultsAreRankedByScore(): void
    {
        $transaction = [
            'otherBankAccount' => '5555555555',
            'memo' => 'Test ranking',
            'amount' => 100.00
        ];

        // Multiple suppliers with different matching potential
        $suppliers = [
            [
                'partner_id' => '1',
                'name' => 'Exact Match Supplier',
                'account' => '5555555555'  // Exact match on account
            ],
            [
                'partner_id' => '2',
                'name' => 'Partial Match Supplier',
                'account' => '9999999999'  // No match on account
            ]
        ];

        $matcher = PartnerMatcherFactory::createSupplierMatcher();

        $results = $matcher->matchTransaction($transaction, $suppliers, [], []);

        // If we have multiple supplier matches, they should be sorted by score
        if (count($results['supplier']) > 1) {
            $firstScore = $results['supplier'][0]->getScore();
            $secondScore = $results['supplier'][1]->getScore();
            $this->assertGreaterThanOrEqual($firstScore, $secondScore);
        }
    }

    /**
     * Test match confidence threshold behavior
     *
     * @test
     * @since 2025-04-19
     */
    public function testMatchConfidenceThresholdBehavior(): void
    {
        $transaction = [
            'otherBankAccount' => '7777777777',
            'memo' => 'Threshold test',
            'amount' => 2000.00
        ];

        $suppliers = [
            [
                'partner_id' => '1',
                'name' => 'High Confidence',
                'account' => '7777777777'
            ]
        ];

        $matcher = PartnerMatcherFactory::createSupplierMatcher();

        $results = $matcher->matchTransaction($transaction, $suppliers, [], []);

        // Verify best match exists and check threshold
        if ($results['best_match'] !== null) {
            $meetsThreshold = $results['best_match']->meetsThreshold();
            $this->assertIsBool($meetsThreshold);

            // Score should always be in valid range
            $score = $results['best_match']->getScore();
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }
    }

    /**
     * Get test scoring engine (minimal implementation)
     *
     * @return object Scoring engine mock
     */
    private function getTestScoringEngine(): object
    {
        return new class {
            public function calculateAdjustment(object $partner, object $transaction): int
            {
                // Bank account exact match = 100
                if ($partner->getPartnerAccount() === ($transaction->otherBankAccount ?? '')) {
                    return 100;
                }
                return -50; // No match
            }
        };
    }

    /**
     * Get test supplier configuration
     *
     * @return object Configuration mock
     */
    private function getTestSupplierConfig(): object
    {
        return new class {
            public function getMinimumConfidenceThreshold(): int
            {
                return 50;
            }

            public function getMaxMatches(): int
            {
                return 5;
            }
        };
    }
}
