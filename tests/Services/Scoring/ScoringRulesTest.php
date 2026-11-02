<?php

declare(strict_types=1);

namespace Tests\Services\Scoring;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\Scoring\RecencyRule;
use Ksfraser\FaBankImport\Services\Scoring\AmountRangeRule;
use Ksfraser\FaBankImport\Services\Scoring\TypeConsistencyRule;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Confidence;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;

/**
 * Scoring Rules Tests
 *
 * Unit tests for individual scoring rules:
 * - RecencyRule: time-based confidence adjustment
 * - AmountRangeRule: amount-based reliability assessment
 * - TypeConsistencyRule: transaction type matching
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class ScoringRulesTest extends TestCase
{
    /**
     * TEST GROUP: RecencyRule
     */

    /**
     * Test: RecencyRule - very recent transaction (< 7 days)
     */
    public function testRecencyRuleVeryRecent(): void
    {
        $rule = new RecencyRule();
        $dateStr = date('Y-m-d', time() - 5 * 86400); // 5 days ago
        $transaction = ['date' => $dateStr];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(5.0, $score);
    }

    /**
     * Test: RecencyRule - recent transaction (8-30 days)
     */
    public function testRecencyRuleRecent(): void
    {
        $rule = new RecencyRule();
        $dateStr = date('Y-m-d', time() - 15 * 86400); // 15 days ago
        $transaction = ['date' => $dateStr];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Test: RecencyRule - somewhat recent (31-90 days)
     */
    public function testRecencyRuleSomewhatRecent(): void
    {
        $rule = new RecencyRule();
        $dateStr = date('Y-m-d', time() - 60 * 86400); // 60 days ago
        $transaction = ['date' => $dateStr];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(1.0, $score);
    }

    /**
     * Test: RecencyRule - old transaction (> 90 days)
     */
    public function testRecencyRuleOld(): void
    {
        $rule = new RecencyRule();
        $dateStr = date('Y-m-d', time() - 120 * 86400); // 120 days ago
        $transaction = ['date' => $dateStr];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(-2.0, $score);
    }

    /**
     * Test: RecencyRule - missing date returns zero
     */
    public function testRecencyRuleMissingDateReturnsZero(): void
    {
        $rule = new RecencyRule();
        $transaction = []; // No date
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(0.0, $score);
    }

    /**
     * TEST GROUP: AmountRangeRule
     */

    /**
     * Test: AmountRangeRule - very small amount (< $5)
     */
    public function testAmountRangeRuleVerySmall(): void
    {
        $rule = new AmountRangeRule();
        $transaction = ['amount' => 2.50];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(-5.0, $score);
    }

    /**
     * Test: AmountRangeRule - small amount ($5-$25)
     */
    public function testAmountRangeRuleSmall(): void
    {
        $rule = new AmountRangeRule();
        $transaction = ['amount' => 15.00];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(-2.0, $score);
    }

    /**
     * Test: AmountRangeRule - normal amount ($25-$1000)
     */
    public function testAmountRangeRuleNormal(): void
    {
        $rule = new AmountRangeRule();
        $transaction = ['amount' => 500.00];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Test: AmountRangeRule - large amount (> $1000)
     */
    public function testAmountRangeRuleLarge(): void
    {
        $rule = new AmountRangeRule();
        $transaction = ['amount' => 5000.00];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(2.0, $score);
    }

    /**
     * Test: AmountRangeRule - handles negative amounts
     */
    public function testAmountRangeRuleHandlesNegativeAmounts(): void
    {
        $rule = new AmountRangeRule();
        $transaction = ['amount' => -500.00];
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score); // Uses abs()
    }

    /**
     * Test: AmountRangeRule - missing amount returns zero
     */
    public function testAmountRangeRuleMissingAmountReturnsZero(): void
    {
        $rule = new AmountRangeRule();
        $transaction = []; // No amount
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(0.0, $score);
    }

    /**
     * TEST GROUP: TypeConsistencyRule
     */

    /**
     * Test: TypeConsistencyRule - supplier + check type
     */
    public function testTypeConsistencyRuleSupplierCheck(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = ['type' => 'check'];
        $match = $this->createMockMatch(partnerType: 1); // Supplier

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Test: TypeConsistencyRule - supplier + wire type
     */
    public function testTypeConsistencyRuleSupplierWire(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = ['type' => 'WIRE'];
        $match = $this->createMockMatch(partnerType: 1); // Supplier

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Test: TypeConsistencyRule - customer + invoice type
     */
    public function testTypeConsistencyRuleCustomerInvoice(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = ['type' => 'invoice'];
        $match = $this->createMockMatch(partnerType: 2); // Customer

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Test: TypeConsistencyRule - customer + deposit type
     */
    public function testTypeConsistencyRuleCustomerDeposit(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = ['type' => 'DEPOSIT'];
        $match = $this->createMockMatch(partnerType: 2); // Customer

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Test: TypeConsistencyRule - mismatched type returns zero
     */
    public function testTypeConsistencyRuleMismatchedTypeReturnsZero(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = ['type' => 'invoice'];
        $match = $this->createMockMatch(partnerType: 1); // Supplier with invoice

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(0.0, $score);
    }

    /**
     * Test: TypeConsistencyRule - missing type returns zero
     */
    public function testTypeConsistencyRuleMissingTypeReturnsZero(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = []; // No type
        $match = $this->createMockMatch();

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(0.0, $score);
    }

    /**
     * Test: TypeConsistencyRule - case insensitive
     */
    public function testTypeConsistencyRuleCaseInsensitive(): void
    {
        $rule = new TypeConsistencyRule();
        $transaction = ['type' => 'ChEcK']; // Mixed case
        $match = $this->createMockMatch(partnerType: 1); // Supplier

        $score = $rule->calculateScore($transaction, $match);
        $this->assertEquals(3.0, $score);
    }

    /**
     * Helper: Create mock match with configurable partner type
     */
    private function createMockMatch(
        float $confidence = 80.0,
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
