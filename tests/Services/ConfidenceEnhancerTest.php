<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ConfidenceEnhancer;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Services\Scoring\RecencyRule;
use Ksfraser\FaBankImport\Services\Scoring\AmountRangeRule;
use Ksfraser\FaBankImport\Services\Scoring\TypeConsistencyRule;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Confidence;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;

/**
 * Confidence Enhancer Tests
 *
 * Tests for context-aware confidence enhancement service:
 * - Single and multiple match enhancement
 * - Auto-match flag determination (75%+ threshold)
 * - Confidence clamping (0-100)
 * - Best match selection
 * - Auto-match candidate filtering
 * - Score breakdown tracking
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class ConfidenceEnhancerTest extends TestCase
{
    private ConfidenceEnhancer $enhancer;
    private ScoringRuleEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ScoringRuleEngine();
        $this->engine
            ->register(new RecencyRule())
            ->register(new AmountRangeRule())
            ->register(new TypeConsistencyRule());

        $this->enhancer = new ConfidenceEnhancer($this->engine);
    }

    /**
     * Test 1: Basic single match enhancement
     */
    public function testBasicSingleMatchEnhancement(): void
    {
        $match = $this->createMatch(confidence: 80, partnerId: 1, partnerType: 1);
        $transaction = [
            'account' => 'Checking',
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400), // 5 days ago
            'type' => 'CHECK'
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        $this->assertIsArray($enhanced);
        $this->assertEquals(80, $enhanced['original_confidence']);
        $this->assertArrayHasKey('adjusted_confidence', $enhanced);
        $this->assertArrayHasKey('context_adjustment', $enhanced);
        $this->assertArrayHasKey('auto_match', $enhanced);
    }

    /**
     * Test 2: Confidence adjustment calculation
     */
    public function testConfidenceAdjustmentCalculation(): void
    {
        $match = $this->createMatch(confidence: 80, partnerId: 1, partnerType: 1);
        $transaction = [
            'account' => 'Checking',
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
            'type' => 'CHECK'
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        // Recency: +5, Amount: +3, Type: +3 = +11 adjustment
        $this->assertEquals(11.0, $enhanced['context_adjustment']);
        $this->assertEquals(91.0, $enhanced['adjusted_confidence']);
        $this->assertEquals(11.0, $enhanced['confidence_change']);
    }

    /**
     * Test 3: Auto-match flag - below threshold
     */
    public function testAutoMatchFlagBelowThreshold(): void
    {
        $match = $this->createMatch(confidence: 70, partnerId: 1, partnerType: 1);
        $transaction = [
            'account' => 'Checking',
            'amount' => 2.0, // Very small amount = -5 adjustment
            'date' => date('Y-m-d', time() - 120 * 86400) // Old = -2
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        // 70 + (-5 + -2) = 63, below 75% threshold
        $this->assertFalse($enhanced['auto_match']);
    }

    /**
     * Test 4: Auto-match flag - at threshold
     */
    public function testAutoMatchFlagAtThreshold(): void
    {
        $match = $this->createMatch(confidence: 75, partnerId: 1, partnerType: 1);
        $transaction = ['account' => 'Checking'];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        // 75 + 0 adjustment = 75, at threshold
        $this->assertTrue($enhanced['auto_match']);
    }

    /**
     * Test 5: Auto-match flag - above threshold
     */
    public function testAutoMatchFlagAboveThreshold(): void
    {
        $match = $this->createMatch(confidence: 80, partnerId: 1, partnerType: 1);
        $transaction = [
            'account' => 'Checking',
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
            'type' => 'CHECK'
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        // 80 + 11 = 91, well above 75
        $this->assertTrue($enhanced['auto_match']);
    }

    /**
     * Test 6: Confidence clamping - upper bound
     */
    public function testConfidenceClampingUpperBound(): void
    {
        $match = $this->createMatch(confidence: 98, partnerId: 1, partnerType: 1);
        $transaction = [
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
            'type' => 'CHECK'
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        // Even with boosts, max is 100
        $this->assertLessThanOrEqual(100.0, $enhanced['adjusted_confidence']);
    }

    /**
     * Test 7: Confidence clamping - lower bound
     */
    public function testConfidenceClampingLowerBound(): void
    {
        $match = $this->createMatch(confidence: 5, partnerId: 1, partnerType: 1);
        $transaction = [
            'amount' => 2.0, // -5
            'date' => date('Y-m-d', time() - 120 * 86400) // -2
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        // 5 - 7 = -2, but clamped at 0
        $this->assertGreaterThanOrEqual(0.0, $enhanced['adjusted_confidence']);
    }

    /**
     * Test 8: Enhance multiple matches
     */
    public function testEnhanceMultipleMatches(): void
    {
        $matches = [
            $this->createMatch(confidence: 70, partnerId: 1),
            $this->createMatch(confidence: 85, partnerId: 2),
            $this->createMatch(confidence: 60, partnerId: 3),
        ];

        $transaction = ['account' => 'Checking'];

        $enhanced = $this->enhancer->enhanceMultiple($matches, $transaction);

        $this->assertCount(3, $enhanced);
        // Should be sorted by confidence descending
        $this->assertGreaterThanOrEqual(
            $enhanced[1]['adjusted_confidence'],
            $enhanced[0]['adjusted_confidence']
        );
    }

    /**
     * Test 9: Get best match
     */
    public function testGetBestMatch(): void
    {
        $matches = [
            $this->createMatch(confidence: 70, partnerId: 1),
            $this->createMatch(confidence: 90, partnerId: 2),
            $this->createMatch(confidence: 60, partnerId: 3),
        ];

        $transaction = ['account' => 'Checking'];

        $best = $this->enhancer->getBestMatch($matches, $transaction);

        $this->assertNotNull($best);
        $this->assertEquals(2, $best['partner_id']);
        $this->assertEquals(90.0, $best['original_confidence']);
    }

    /**
     * Test 10: Get best match - empty list
     */
    public function testGetBestMatchEmpty(): void
    {
        $result = $this->enhancer->getBestMatch([], []);
        $this->assertNull($result);
    }

    /**
     * Test 11: Get auto-match candidates
     */
    public function testGetAutoMatchCandidates(): void
    {
        $matches = [
            $this->createMatch(confidence: 70, partnerId: 1), // Below threshold
            $this->createMatch(confidence: 78, partnerId: 2), // Above threshold
            $this->createMatch(confidence: 60, partnerId: 3), // Below threshold
            $this->createMatch(confidence: 81, partnerId: 4), // Above threshold
        ];

        $transaction = ['account' => 'Checking'];

        $autoMatch = $this->enhancer->getAutoMatchCandidates($matches, $transaction);

        // Should only include matches at/above 75% confidence
        $this->assertCount(2, $autoMatch);
        $this->assertEquals(2, $autoMatch[0]['partner_id']);
        $this->assertEquals(4, $autoMatch[1]['partner_id']);
    }

    /**
     * Test 12: Score breakdown included in results
     */
    public function testScoreBreakdownIncludedInResults(): void
    {
        $match = $this->createMatch(confidence: 80, partnerId: 1);
        $transaction = [
            'amount' => 500.0,
            'date' => date('Y-m-d', time() - 5 * 86400),
            'type' => 'CHECK'
        ];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        $this->assertArrayHasKey('breakdown', $enhanced);
        $this->assertIsArray($enhanced['breakdown']);
        $this->assertArrayHasKey('RecencyRule', $enhanced['breakdown']);
        $this->assertArrayHasKey('AmountRangeRule', $enhanced['breakdown']);
        $this->assertArrayHasKey('TypeConsistencyRule', $enhanced['breakdown']);
    }

    /**
     * Test 13: Match metadata preserved in enhancement
     */
    public function testMatchMetadataPreserved(): void
    {
        $match = $this->createMatch(
            confidence: 80,
            partnerId: 123,
            partnerType: 2,
            partnerName: 'Test Supplier Inc'
        );

        $transaction = ['account' => 'Checking'];

        $enhanced = $this->enhancer->enhance($match, $transaction);

        $this->assertEquals(123, $enhanced['partner_id']);
        $this->assertEquals(2, $enhanced['partner_type']);
        $this->assertEquals('Test Supplier Inc', $enhanced['partner_name']);
    }

    /**
     * Helper: Create mock KeywordMatch
     */
    private function createMatch(
        float $confidence = 80.0,
        int $partnerId = 1,
        int $partnerType = 1,
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
