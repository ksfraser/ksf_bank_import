<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionPartnerMatcher;
use Ksfraser\FaBankImport\Services\Matching\GenericMatcherAdapter;
use Ksfraser\FaBankImport\Services\PartnerMatcherFactory;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use ksfraser\FrontAccounting\Common\Matching\MatchingConfig;

/**
 * Tests for the GenericMatcherAdapter and its wiring into
 * TransactionPartnerMatcher as the shared-engine scoring backend.
 *
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
 */
class GenericMatcherAdapterTest extends TestCase
{
    private function makeConfig(): object
    {
        return new class {
            public function getMinimumConfidenceThreshold(): int
            {
                return 10;
            }
        };
    }

    /**
     * @test
     * Adapter scores partners by memo-name similarity on the 0-100 scale.
     */
    public function testAdapterScoresByNameSimilarity(): void
    {
        $adapter = new GenericMatcherAdapter();

        $transaction = [
            'memo' => 'TIM HORTONS STORE 1234',
            'amount' => 25.50,
            'trans_date' => '2026-11-15',
        ];
        $candidates = [
            ['partner_id' => 1, 'name' => 'Tim Hortons', 'account' => ''],
            ['partner_id' => 2, 'name' => 'Canadian Tire', 'account' => ''],
        ];

        $scored = $adapter->scoreCandidates($transaction, $candidates);

        $this->assertCount(2, $scored);
        // Sorted by confidence descending
        $this->assertGreaterThanOrEqual($scored[1]['score'], $scored[0]['score']);
        $this->assertSame(1, $scored[0]['id']);
        $this->assertSame('Tim Hortons', $scored[0]['name']);
        // Strong name similarity clears the shared review threshold
        $this->assertGreaterThanOrEqual(80.0, $scored[0]['score']);
        $this->assertLessThanOrEqual(100.0, $scored[0]['score']);
        // Weak similarity is far below
        $this->assertLessThan(50.0, $scored[1]['score']);
        // 0-1 confidence parallels the percentage score
        $this->assertEqualsWithDelta($scored[0]['score'] / 100.0, $scored[0]['confidence'], 0.0001);
    }

    /**
     * @test
     * Adapter normalizes bank-account candidate rows.
     */
    public function testAdapterHandlesBankAccountRows(): void
    {
        $adapter = new GenericMatcherAdapter();

        $transaction = ['memo' => 'INTERAC E-TRANSFER XYZ STORE'];
        $candidates = [
            ['bank_account_id' => 7, 'bank_account_name' => 'XYZ Store Chequing', 'account_number' => '123'],
            ['bank_account_id' => 8, 'bank_account_name' => 'Completely Different', 'account_number' => '456'],
        ];

        $scored = $adapter->scoreCandidates($transaction, $candidates);

        $this->assertCount(2, $scored);
        // Both normalized (ids survive), sorted by confidence descending
        $ids = [$scored[0]['id'], $scored[1]['id']];
        sort($ids);
        $this->assertSame([7, 8], $ids);
        $this->assertGreaterThanOrEqual($scored[1]['score'], $scored[0]['score']);
    }

    /**
     * @test
     * Empty candidate list yields an empty result.
     */
    public function testAdapterEmptyCandidates(): void
    {
        $adapter = new GenericMatcherAdapter();
        $this->assertSame([], $adapter->scoreCandidates(['memo' => 'X'], []));
    }

    /**
     * @test
     * Decision thresholds are honoured from the shared config.
     */
    public function testAdapterThresholds(): void
    {
        $adapter = new GenericMatcherAdapter();

        $this->assertTrue($adapter->shouldAutoApprove(0.96));
        $this->assertFalse($adapter->shouldAutoApprove(0.90));
        $this->assertTrue($adapter->needsReview(0.85));
        $this->assertFalse($adapter->needsReview(0.70));
    }

    /**
     * @test
     * Custom MatchingConfig thresholds are respected.
     */
    public function testAdapterCustomConfig(): void
    {
        $config = new MatchingConfig(['auto_approve' => 0.99, 'review' => 0.50]);
        $adapter = new GenericMatcherAdapter($config);

        $this->assertFalse($adapter->shouldAutoApprove(0.96));
        $this->assertTrue($adapter->needsReview(0.60));
        $this->assertFalse($adapter->needsReview(0.40));
    }

    /**
     * @test
     * Matcher with the adapter wired returns best_match on the name path.
     */
    public function testMatcherDelegatesToAdapter(): void
    {
        $engine = $this->createMock(ScoringRuleEngine::class);
        // Engine must NOT be consulted when the adapter is present
        $engine->expects($this->never())->method('calculateAdjustment');

        $matcher = new TransactionPartnerMatcher(
            $engine,
            $this->makeConfig(),
            new GenericMatcherAdapter()
        );

        $transaction = [
            'memo' => 'E-TRANSFER 010667466304;XYZ;Payment',
            'amount' => 250.00,
        ];
        $suppliers = [
            ['partner_id' => 1, 'name' => 'XYZ SUPPLIER', 'account' => '999'],
        ];

        $results = $matcher->matchTransaction($transaction, $suppliers, [], []);

        $this->assertNotNull($results['best_match']);
        $this->assertSame('supplier', $results['best_match']->getPartnerType());
        $this->assertSame(1, $results['best_match']->getPartnerId());
        $this->assertGreaterThan(0.0, $results['best_match']->getScore());
    }

    /**
     * @test
     * Shared-engine matcher still honours memo-consistency hints and sorting.
     */
    public function testSharedEngineMatcherHintAndRanking(): void
    {
        $engine = $this->createMock(ScoringRuleEngine::class);
        // Low threshold so adapter scores clear it
        $config = new class {
            public function getMinimumConfidenceThreshold(): int
            {
                return 10;
            }
        };
        $matcher = new TransactionPartnerMatcher(
            $engine,
            $config,
            new GenericMatcherAdapter()
        );

        $transaction = [
            'memo' => 'E-TRANSFER from ALPHA SERVICES;Rent;Payment',
            'memo_consistency_hint' => 'customer',
        ];
        $suppliers = [
            ['partner_id' => 1, 'name' => 'Alpha Services Ltd', 'account' => ''],
        ];
        $customers = [
            ['partner_id' => 201, 'name' => 'Alpha Services Inc', 'account' => ''],
        ];

        $results = $matcher->matchTransaction($transaction, $suppliers, $customers, []);

        // Both buckets rank their candidates by score descending
        $this->assertCount(1, $results['supplier']);
        $this->assertCount(1, $results['customer']);
        // Hint prefers the customer bucket (e-transfer memo biasing)
        $this->assertNotNull($results['best_match']);
        $this->assertSame('customer', $results['best_match']->getPartnerType());
        $this->assertSame(201, $results['best_match']->getPartnerId());
    }

    /**
     * @test
     * Factory produces the shared-engine matcher with adapter wiring.
     */
    public function testFactoryCreateSharedEngineMatcher(): void
    {
        $matcher = PartnerMatcherFactory::createSharedEngineMatcher();
        $this->assertInstanceOf(TransactionPartnerMatcher::class, $matcher);

        $results = $matcher->matchTransaction(
            ['memo' => 'SHELL OIL 4456', 'amount' => 88.10],
            [['partner_id' => 5, 'name' => 'Shell Oil Products', 'account' => '']],
            [],
            []
        );

        $this->assertNotNull($results['best_match']);
        $this->assertSame(5, $results['best_match']->getPartnerId());
    }
}
