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
     * Test 14: Rules can be registered with weights
     */
    public function testRulesCanBeRegisteredWithWeights(): void
    {
        $this->engine->register(new RecencyRule(), 1.5);
        $this->engine->register(new AmountRangeRule(), 0.5);
        $this->engine->register(new TypeConsistencyRule()); // Default 1.0

        $this->assertEquals(3, $this->engine->getRuleCount());
        $this->assertEquals(1.5, $this->engine->getRuleWeight('RecencyRule'));
        $this->assertEquals(0.5, $this->engine->getRuleWeight('AmountRangeRule'));
        $this->assertEquals(1.0, $this->engine->getRuleWeight('TypeConsistencyRule'));
    }

    /**
     * Test 15: Invalid weight throws exception
     */
    public function testInvalidWeightThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->register(new RecencyRule(), 0.0); // Weight must be positive
    }

    /**
     * Test 16: Weighted scoring calculation
     */
    public function testWeightedScoringCalculation(): void
    {
        $this->engine
            ->register(new RecencyRule(), 2.0)      // 5.0 * 2.0 = 10.0
            ->register(new AmountRangeRule(), 0.5); // 3.0 * 0.5 = 1.5

        $transaction = [
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $adjustment = $this->engine->calculateAdjustment($transaction, $match);
        // Recency: 5 * 2.0 = 10, Amount: 3 * 0.5 = 1.5, Total = 11.5
        $this->assertEquals(11.5, $adjustment);
    }

    /**
     * Test 17: Get rule weights map
     */
    public function testGetRuleWeightsMap(): void
    {
        $this->engine
            ->register(new RecencyRule(), 1.5)
            ->register(new AmountRangeRule());

        $weights = $this->engine->getRuleWeights();

        $this->assertIsArray($weights);
        $this->assertEquals(1.5, $weights['RecencyRule']);
        $this->assertEquals(1.0, $weights['AmountRangeRule']);
    }

    /**
     * Test 18: Score details include raw and weighted scores
     */
    public function testScoreDetailsIncludeWeights(): void
    {
        $this->engine
            ->register(new RecencyRule(), 2.0)
            ->register(new AmountRangeRule());

        $transaction = [
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $this->engine->calculateAdjustment($transaction, $match);
        $details = $this->engine->getScoreDetails();

        $this->assertArrayHasKey('rules', $details);
        $this->assertArrayHasKey('RecencyRule', $details['rules']);
        
        $recencyData = $details['rules']['RecencyRule'];
        $this->assertEquals(5.0, $recencyData['raw_score']);
        $this->assertEquals(2.0, $recencyData['weight']);
        $this->assertEquals(10.0, $recencyData['weighted_score']);
    }

    /**
     * Test 19: Format score details as human-readable formula
     */
    public function testFormatScoreDetails(): void
    {
        $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule());

        $transaction = [
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $this->engine->calculateAdjustment($transaction, $match);
        $formula = $this->engine->formatScoreDetails();

        // Should contain rule names and final score
        $this->assertStringContainsString('RecencyRule', $formula);
        $this->assertStringContainsString('AmountRangeRule', $formula);
        $this->assertStringContainsString('=', $formula);
        $this->assertStringContainsString('8', $formula); // 5 + 3 = 8
    }

    /**
     * Test 20: Format score with negative values
     */
    public function testFormatScoreWithNegativeValues(): void
    {
        $this->engine->register(new AmountRangeRule()); // Will produce -5 for $2

        $transaction = ['amount' => 2.0]; // Very small amount
        $match = $this->createMockMatch(80);

        $this->engine->calculateAdjustment($transaction, $match);
        $formula = $this->engine->formatScoreDetails();

        // Should show negative adjustment
        $this->assertStringContainsString('-', $formula);
        $this->assertStringContainsString('5', $formula); // -5
    }

    /**
     * Test 21: Calculate adjustment with breakdown includes formula
     */
    public function testCalculateAdjustmentWithBreakdownIncludesFormula(): void
    {
        $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule());

        $transaction = [
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
        ];
        $match = $this->createMockMatch(80);

        $result = $this->engine->calculateAdjustmentWithBreakdown($transaction, $match);

        $this->assertArrayHasKey('score_formula', $result);
        $this->assertStringContainsString('RecencyRule', $result['score_formula']);
        $this->assertStringContainsString('AmountRangeRule', $result['score_formula']);
    }

    /**
     * Test 22: Max possible boost with weights
     */
    public function testMaxPossibleBoostWithWeights(): void
    {
        $this->engine
            ->register(new RecencyRule(), 2.0)     // Max 5 * 2.0 = 10
            ->register(new AmountRangeRule(), 0.5); // Max 3 * 0.5 = 1.5

        $maxBoost = $this->engine->getMaxPossibleBoost();
        $this->assertEquals(11.5, $maxBoost); // 10 + 1.5
    }

    /**
     * Test 23: Max possible reduction with weights
     */
    public function testMaxPossibleReductionWithWeights(): void
    {
        $this->engine
            ->register(new RecencyRule(), 1.0)     // Min -2 * 1.0 = -2
            ->register(new AmountRangeRule(), 2.0); // Min -5 * 2.0 = -10

        $maxReduction = $this->engine->getMaxPossibleReduction();
        $this->assertEquals(-12.0, $maxReduction); // -2 + -10
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
