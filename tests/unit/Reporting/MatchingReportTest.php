<?php

/**
 * MatchingReport Test Suite
 *
 * Tests single match operation reporting:
 * - Report creation (success, failed, uncertain)
 * - Confidence level classification
 * - Score breakdown tracking
 * - Data serialization
 *
 * @covers \Ksfraser\FaBankImport\Reporting\MatchingReport
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Reporting;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Reporting\MatchingReport;
use DateTime;

/**
 * MatchingReport Tests
 */
class MatchingReportTest extends TestCase
{
    /**
     * Test 1: Create successful match report
     */
    public function testSuccessReportCreation(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-001',
            partnerId: 123,
            confidence: 85.5
        );

        $this->assertTrue($report->isSuccess());
        $this->assertSame('TXN-001', $report->getTransactionId());
        $this->assertSame(123, $report->getPartnerId());
        $this->assertSame(85.5, $report->getConfidence());
    }

    /**
     * Test 2: Create failed match report
     */
    public function testFailedReportCreation(): void
    {
        $report = MatchingReport::failed(
            transactionId: 'TXN-002',
            reason: 'No matching partners found'
        );

        $this->assertFalse($report->isSuccess());
        $this->assertSame('TXN-002', $report->getTransactionId());
        $this->assertNull($report->getPartnerId());
        $this->assertSame(0.0, $report->getConfidence());
    }

    /**
     * Test 3: Create uncertain match report
     */
    public function testUncertainReportCreation(): void
    {
        $report = MatchingReport::uncertain(
            transactionId: 'TXN-003',
            suggestedPartnerId: 456,
            confidence: 35.0,
            reason: 'Low confidence - manual review required'
        );

        $this->assertFalse($report->isSuccess());
        $this->assertSame(456, $report->getPartnerId());
        $this->assertSame(35.0, $report->getConfidence());
    }

    /**
     * Test 4: Confidence level HIGH (≥ 70%)
     */
    public function testConfidenceLevelHigh(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-004',
            partnerId: 789,
            confidence: 75.0
        );

        $this->assertSame('HIGH', $report->getConfidenceLevel());
    }

    /**
     * Test 5: Confidence level MEDIUM (40-69%)
     */
    public function testConfidenceLevelMedium(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-005',
            partnerId: 789,
            confidence: 55.0
        );

        $this->assertSame('MEDIUM', $report->getConfidenceLevel());
    }

    /**
     * Test 6: Confidence level LOW (< 40%)
     */
    public function testConfidenceLevelLow(): void
    {
        $report = MatchingReport::uncertain(
            transactionId: 'TXN-006',
            suggestedPartnerId: 789,
            confidence: 25.0,
            reason: 'Low confidence'
        );

        $this->assertSame('LOW', $report->getConfidenceLevel());
    }

    /**
     * Test 7: Confidence clamped to 100 maximum
     */
    public function testConfidenceClampsAtMax(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-007',
            partnerId: 789,
            confidence: 150.0
        );

        $this->assertSame(100.0, $report->getConfidence());
    }

    /**
     * Test 8: Confidence clamped to 0 minimum
     */
    public function testConfidenceClampsAtMin(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-008',
            partnerId: 789,
            confidence: -50.0
        );

        $this->assertSame(0.0, $report->getConfidence());
    }

    /**
     * Test 9: Score breakdown tracking
     */
    public function testScoreBreakdownTracking(): void
    {
        $breakdown = [
            'RecencyRule' => 10.0,
            'AmountRangeRule' => 3.0,
            'TypeConsistencyRule' => 3.0,
        ];

        $report = MatchingReport::success(
            transactionId: 'TXN-009',
            partnerId: 789,
            confidence: 80.0,
            scoreBreakdown: $breakdown
        );

        $this->assertSame($breakdown, $report->getScoreBreakdown());
    }

    /**
     * Test 10: Keywords tracking
     */
    public function testKeywordsTracking(): void
    {
        $keywords = ['VENDOR', 'INC', 'LLC'];

        $report = MatchingReport::success(
            transactionId: 'TXN-010',
            partnerId: 789,
            confidence: 80.0,
            keywords: $keywords
        );

        $this->assertSame($keywords, $report->getKeywords());
    }

    /**
     * Test 11: Get total score from breakdown
     */
    public function testGetTotalScore(): void
    {
        $breakdown = [
            'RecencyRule' => 10.0,
            'AmountRangeRule' => 3.0,
            'TypeConsistencyRule' => 3.0,
        ];

        $report = MatchingReport::success(
            transactionId: 'TXN-011',
            partnerId: 789,
            confidence: 80.0,
            scoreBreakdown: $breakdown
        );

        $this->assertSame(16.0, $report->getTotalScore());
    }

    /**
     * Test 12: Get average rule contribution
     */
    public function testGetAverageRuleContribution(): void
    {
        $breakdown = [
            'RecencyRule' => 10.0,
            'AmountRangeRule' => 4.0,
            'TypeConsistencyRule' => 2.0,
        ];

        $report = MatchingReport::success(
            transactionId: 'TXN-012',
            partnerId: 789,
            confidence: 80.0,
            scoreBreakdown: $breakdown
        );

        // Average of [10, 4, 2] = 16 / 3 = 5.33...
        $this->assertEqualsWithDelta(5.33, $report->getAverageRuleContribution(), 0.01);
    }

    /**
     * Test 13: Success value as numeric (1.0 for success)
     */
    public function testSuccessValueOne(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-013',
            partnerId: 789,
            confidence: 80.0
        );

        $this->assertSame(1.0, $report->getSuccessValue());
    }

    /**
     * Test 14: Success value as numeric (0.0 for failure)
     */
    public function testSuccessValueZero(): void
    {
        $report = MatchingReport::failed(
            transactionId: 'TXN-014',
            reason: 'No match'
        );

        $this->assertSame(0.0, $report->getSuccessValue());
    }

    /**
     * Test 15: Convert to array for serialization
     */
    public function testToArray(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-015',
            partnerId: 789,
            confidence: 85.5,
            scoreBreakdown: ['Rule1' => 5, 'Rule2' => 3],
            keywords: ['KEY1', 'KEY2'],
            scoreFormula: 'Rule1(5)+Rule2(3)=8'
        );

        $array = $report->toArray();

        $this->assertIsArray($array);
        $this->assertSame('TXN-015', $array['transaction_id']);
        $this->assertSame(789, $array['partner_id']);
        $this->assertSame(85.5, $array['confidence']);
        $this->assertSame('HIGH', $array['confidence_level']);
        $this->assertTrue($array['success']);
        $this->assertSame('Rule1(5)+Rule2(3)=8', $array['score_formula']);
    }

    /**
     * Test 16: Score formula tracking
     */
    public function testScoreFormulaTracking(): void
    {
        $formula = 'RecencyRule(10)+AmountRule(3)=13';

        $report = MatchingReport::success(
            transactionId: 'TXN-016',
            partnerId: 789,
            confidence: 80.0,
            scoreFormula: $formula
        );

        $this->assertSame($formula, $report->getScoreFormula());
    }

    /**
     * Test 17: Candidates evaluated tracking
     */
    public function testCandidatesEvaluatedTracking(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-017',
            partnerId: 789,
            confidence: 80.0,
            candidatesEvaluated: 15
        );

        $this->assertSame(15, $report->getCandidatesEvaluated());
    }

    /**
     * Test 18: Timestamp automatically set
     */
    public function testTimestampAutomaticallySet(): void
    {
        $before = new DateTime();
        $report = MatchingReport::success(
            transactionId: 'TXN-018',
            partnerId: 789,
            confidence: 80.0
        );
        $after = new DateTime();

        $timestamp = $report->getTimestamp();

        $this->assertInstanceOf(DateTime::class, $timestamp);
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    /**
     * Test 19: Empty score breakdown
     */
    public function testEmptyScoreBreakdown(): void
    {
        $report = MatchingReport::success(
            transactionId: 'TXN-019',
            partnerId: 789,
            confidence: 80.0,
            scoreBreakdown: []
        );

        $this->assertSame(0.0, $report->getTotalScore());
        $this->assertSame(0.0, $report->getAverageRuleContribution());
    }

    /**
     * Test 20: Failed report captures candidates evaluated
     */
    public function testFailedReportCandidates(): void
    {
        $report = MatchingReport::failed(
            transactionId: 'TXN-020',
            reason: 'No suitable match',
            keywords: ['KEY1'],
            candidatesEvaluated: 25
        );

        $this->assertSame(25, $report->getCandidatesEvaluated());
        $this->assertFalse($report->isSuccess());
    }
}
