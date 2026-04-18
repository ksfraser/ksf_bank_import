<?php

declare(strict_types=1);

namespace Tests\Services\Scoring;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Services\Scoring\RecencyRule;
use Ksfraser\FaBankImport\Services\Scoring\AmountRangeRule;
use Ksfraser\FaBankImport\Services\Scoring\TypeConsistencyRule;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Confidence;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;

/**
 * ScoringRuleEngine Tests
 *
 * Comprehensive tests for pluggable scoring system:
 * - Engine initialization and rule registration
 * - Score aggregation from multiple rules
 * - Result clamping to [-100, +30] range
 * - Detailed score breakdown tracking
 * - Fluent interface support
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class ScoringRuleEngineTest extends TestCase
{
    private ScoringRuleEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ScoringRuleEngine();
    }

    /**
     * Test 1: Engine initializes with no rules
     */
    public function testEngineInitializesWithNoRules(): void
    {
        $this->assertEquals(0, $this->engine->getRuleCount());
    }

    /**
     * Test 2: Rules can be registered via register()
     */
    public function testRulesCanBeRegistered(): void
    {
        $this->engine->register(new RecencyRule());
        $this->engine->register(new AmountRangeRule());
        $this->engine->register(new TypeConsistencyRule());

        $this->assertEquals(3, $this->engine->getRuleCount());
    }

    /**
     * Test 3: Fluent interface chaining
     */
    public function testFluentInterfaceChaining(): void
    {
        $result = $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule())
            ->register(new TypeConsistencyRule());

        $this->assertSame($this->engine, $result);
        $this->assertEquals(3, $this->engine->getRuleCount());
    }

    /**
     * Test 4: Adjustment calculation with no rules
     */
    public function testAdjustmentWithNoRulesIsZero(): void
    {
        $transaction = ['account' => 'Checking', 'amount' => 100.0, 'date' => date('Y-m-d')];
        $match = $this->createMockMatch(80);

        $adjustment = $this->engine->calculateAdjustment($transaction, $match);
        $this->assertEquals(0.0, $adjustment);
    }

    /**
     * Test 5: Single rule scoring
     */
    public function testSingleRuleScoring(): void
    {
        $this->engine->register(new RecencyRule());

        // Recent transaction (5 days ago)
        $transaction = [
            'account' => 'Checking',
            'amount' => 100.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $adjustment = $this->engine->calculateAdjustment($transaction, $match);
        $this->assertEquals(5.0, $adjustment); // Very recent boost
    }

    /**
     * Test 6: Multiple rule score aggregation
     */
    public function testMultipleRuleAggregation(): void
    {
        $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule())
            ->register(new TypeConsistencyRule());

        // Setup: Recent, normal amount, supplier type match
        $transaction = [
            'account' => 'Checking',
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400), // 5 days ago
            'type' => 'CHECK',
        ];
        $match = $this->createMockMatch(80, 1); // Partner type 1 = supplier

        $adjustment = $this->engine->calculateAdjustment($transaction, $match);
        // Recency: +5, Amount: +3, Type: +3 = +11
        $this->assertEquals(11.0, $adjustment);
    }

    /**
     * Test 7: Score clamping - positive ceiling at +30
     */
    public function testScoreClamppingPositiveCeiling(): void
    {
        // Create a mock rule that returns excessive boost
        $excessiveRule = new class implements \Ksfraser\FaBankImport\Services\Scoring\ScoringRule {
            public function calculateScore(array $transaction, KeywordMatch $match): float
            {
                return 100.0; // Way too high
            }
            public function getName(): string
            {
                return 'ExcessiveRule';
            }
            public function getMaxBoost(): float
            {
                return 100.0;
            }
            public function getMinReduction(): float
            {
                return 0.0;
            }
        };

        $this->engine->register($excessiveRule);

        $transaction = ['account' => 'Checking'];
        $match = $this->createMockMatch(80);

        $adjustment = $this->engine->calculateAdjustment($transaction, $match);
        $this->assertEquals(30.0, $adjustment); // Clamped at +30
    }

    /**
     * Test 8: Score clamping - negative floor at -100
     */
    public function testScoreClampingNegativeFloor(): void
    {
        // Create a mock rule that returns excessive reduction
        $excessiveRule = new class implements \Ksfraser\FaBankImport\Services\Scoring\ScoringRule {
            public function calculateScore(array $transaction, KeywordMatch $match): float
            {
                return -200.0; // Way too low
            }
            public function getName(): string
            {
                return 'ExcessiveRule';
            }
            public function getMaxBoost(): float
            {
                return 0.0;
            }
            public function getMinReduction(): float
            {
                return -200.0;
            }
        };

        $this->engine->register($excessiveRule);

        $transaction = ['account' => 'Checking'];
        $match = $this->createMockMatch(80);

        $adjustment = $this->engine->calculateAdjustment($transaction, $match);
        $this->assertEquals(-100.0, $adjustment); // Clamped at -100
    }

    /**
     * Test 9: Score breakdown tracking
     */
    public function testScoreBreakdownTracking(): void
    {
        $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule());

        $transaction = [
            'account' => 'Checking',
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $this->engine->calculateAdjustment($transaction, $match);
        $breakdown = $this->engine->getScoreBreakdown();

        $this->assertArrayHasKey('RecencyRule', $breakdown);
        $this->assertArrayHasKey('AmountRangeRule', $breakdown);
        $this->assertArrayHasKey('total', $breakdown);
        $this->assertArrayHasKey('clamped', $breakdown);
        $this->assertEquals(5.0, $breakdown['RecencyRule']);
        $this->assertEquals(3.0, $breakdown['AmountRangeRule']);
        $this->assertEquals(8.0, $breakdown['total']);
        $this->assertEquals(8.0, $breakdown['clamped']);
    }

    /**
     * Test 10: Calculate adjustment with full breakdown
     */
    public function testCalculateAdjustmentWithBreakdown(): void
    {
        $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule());

        $transaction = [
            'account' => 'Checking',
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $result = $this->engine->calculateAdjustmentWithBreakdown($transaction, $match);

        $this->assertArrayHasKey('adjustment', $result);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertEquals(8.0, $result['adjustment']);
        $this->assertIsArray($result['breakdown']);
    }

    /**
     * Test 11: Maximum possible boost calculation
     */
    public function testMaxPossibleBoostCalculation(): void
    {
        $this->engine
            ->register(new RecencyRule())     // Max +5
            ->register(new AmountRangeRule()) // Max +3
            ->register(new TypeConsistencyRule()); // Max +3

        $maxBoost = $this->engine->getMaxPossibleBoost();
        $this->assertEquals(11.0, $maxBoost); // 5 + 3 + 3
    }

    /**
     * Test 12: Maximum possible reduction calculation
     */
    public function testMaxPossibleReductionCalculation(): void
    {
        $this->engine
            ->register(new RecencyRule())     // Min -2
            ->register(new AmountRangeRule()) // Min -5
            ->register(new TypeConsistencyRule()); // Min 0

        $maxReduction = $this->engine->getMaxPossibleReduction();
        $this->assertEquals(-7.0, $maxReduction); // -2 + -5 + 0
    }

    /**
     * Test 13: Reset clears all rules and breakdown
     */
    public function testResetClearsRulesAndBreakdown(): void
    {
        $this->engine->register(new RecencyRule());
        $this->assertEquals(1, $this->engine->getRuleCount());

        $transaction = ['date' => date('Y-m-d')];
        $match = $this->createMockMatch(80);
        $this->engine->calculateAdjustment($transaction, $match);
        $this->assertNotEmpty($this->engine->getScoreBreakdown());

        $this->engine->reset();

        $this->assertEquals(0, $this->engine->getRuleCount());
        $this->assertEmpty($this->engine->getScoreBreakdown());
    }

    /**
     * Helper: Create mock KeywordMatch
     */
    private function createMockMatch(
        float $confidence,
        int $partnerType = 1,
        int $partnerId = 1,
        int $partnerDetailId = 1,
        string $partnerName = 'Test Partner'
    ): KeywordMatch {
        return new KeywordMatch(
            partnerId: $partnerId,
            partnerDetailId: $partnerDetailId,
            partnerType: $partnerType,
            partnerName: $partnerName,
            confidence: new Confidence($confidence),
            matchedKeywords: [new Keyword('test')],
            occurrenceCount: 1
        );
    }
}
