<?php

/**
 * OperationReport Test Suite
 *
 * Tests aggregated matching results reporting:
 * - Success rate calculation
 * - Confidence statistics and distribution
 * - Rule effectiveness analysis
 * - Percentile calculations
 *
 * @covers \Ksfraser\FaBankImport\Reporting\OperationReport
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Reporting;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Reporting\MatchingReport;
use Ksfraser\FaBankImport\Reporting\OperationReport;

/**
 * OperationReport Tests
 */
class OperationReportTest extends TestCase
{
    /**
     * Test 1: Create operation with no reports
     */
    public function testCreateOperationWithNoReports(): void
    {
        $operation = new OperationReport();

        $this->assertSame(0, $operation->getTotalAttempted());
        $this->assertSame(0, $operation->getTotalSuccessful());
        $this->assertSame(0, $operation->getTotalFailed());
        $this->assertSame(0.0, $operation->getSuccessRate());
    }

    /**
     * Test 2: Calculate success rate (80% = 4 of 5)
     */
    public function testCalculateSuccessRate(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 85),
            MatchingReport::success('TXN-003', 3, 75),
            MatchingReport::success('TXN-004', 4, 90),
            MatchingReport::failed('TXN-005', 'No match'),
        ];

        $operation = new OperationReport($reports);

        $this->assertSame(5, $operation->getTotalAttempted());
        $this->assertSame(4, $operation->getTotalSuccessful());
        $this->assertSame(1, $operation->getTotalFailed());
        $this->assertSame(0.8, $operation->getSuccessRate());
        $this->assertSame(80.0, $operation->getSuccessRatePercentage());
    }

    /**
     * Test 3: Calculate average confidence
     */
    public function testCalculateAverageConfidence(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 90),
            MatchingReport::success('TXN-003', 3, 70),
            MatchingReport::failed('TXN-004', 'No match'),
        ];

        $operation = new OperationReport($reports);

        // Average: (80 + 90 + 70 + 0) / 4 = 240 / 4 = 60
        $this->assertSame(60.0, $operation->getAverageConfidence());
    }

    /**
     * Test 4: Calculate average success confidence (only successful)
     */
    public function testCalculateAverageSuccessConfidence(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 90),
            MatchingReport::success('TXN-003', 3, 70),
            MatchingReport::failed('TXN-004', 'No match'),
        ];

        $operation = new OperationReport($reports);

        // Average of successful: (80 + 90 + 70) / 3 = 240 / 3 = 80
        $this->assertSame(80.0, $operation->getAverageSuccessConfidence());
    }

    /**
     * Test 5: Calculate average failure confidence (only failed)
     */
    public function testCalculateAverageFailureConfidence(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::uncertain('TXN-002', 2, 45, 'Low confidence'),
            MatchingReport::failed('TXN-003', 'No match'),
        ];

        $operation = new OperationReport($reports);

        // Average of non-successful: (45 + 0) / 2 = 22.5
        $this->assertSame(22.5, $operation->getAverageFailureConfidence());
    }

    /**
     * Test 6: Confidence distribution HIGH/MEDIUM/LOW
     */
    public function testConfidenceDistribution(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 85),  // HIGH
            MatchingReport::success('TXN-002', 2, 75),  // HIGH
            MatchingReport::success('TXN-003', 3, 50),  // MEDIUM
            MatchingReport::uncertain('TXN-004', 4, 35, 'Low'),  // LOW
            MatchingReport::failed('TXN-005', 'No match'),  // LOW (0%)
        ];

        $operation = new OperationReport($reports);
        $dist = $operation->getConfidenceDistribution();

        $this->assertSame(2, $dist['HIGH']);
        $this->assertSame(1, $dist['MEDIUM']);
        $this->assertSame(2, $dist['LOW']);
    }

    /**
     * Test 7: Percentile calculations
     */
    public function testPercentileCalculations(): void
    {
        $reports = [];
        for ($i = 1; $i <= 100; $i++) {
            $reports[] = MatchingReport::success("TXN-$i", 1, (float)$i);
        }

        $operation = new OperationReport($reports);
        $percentiles = $operation->getConfidencePercentiles();

        $this->assertEquals(50.5, $percentiles['p50']);  // Median
        $this->assertEquals(75.25, $percentiles['p75']);
        $this->assertEqualsWithDelta(90.1, $percentiles['p90'], 0.0001);
        $this->assertEquals(95.05, $percentiles['p95']);
    }

    /**
     * Test 8: Rule effectiveness analysis
     */
    public function testRuleEffectivenessAnalysis(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80, scoreBreakdown: ['RecencyRule' => 10, 'AmountRule' => 3]),
            MatchingReport::success('TXN-002', 2, 85, scoreBreakdown: ['RecencyRule' => 12, 'AmountRule' => 5]),
            MatchingReport::failed('TXN-003', 'No match', candidatesEvaluated: 5),
        ];

        $operation = new OperationReport($reports);
        $effectiveness = $operation->getRuleEffectiveness();

        // RecencyRule: avg = (10 + 12) / 2 = 11
        $this->assertEquals(11.0, $effectiveness['RecencyRule']['avg_contribution']);
        $this->assertSame(2, $effectiveness['RecencyRule']['firing_count']);

        // AmountRule: avg = (3 + 5) / 2 = 4
        $this->assertEquals(4.0, $effectiveness['AmountRule']['avg_contribution']);
    }

    /**
     * Test 9: Most impactful rule
     */
    public function testMostImpactfulRule(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80, scoreBreakdown: ['RecencyRule' => 20, 'AmountRule' => 1]),
            MatchingReport::success('TXN-002', 2, 85, scoreBreakdown: ['RecencyRule' => 15, 'AmountRule' => 2]),
        ];

        $operation = new OperationReport($reports);
        $mostImpactful = $operation->getMostImpactfulRule();

        // RecencyRule: avg = (20 + 15) / 2 = 17.5
        // AmountRule: avg = (1 + 2) / 2 = 1.5
        $this->assertSame('RecencyRule', $mostImpactful);
    }

    /**
     * Test 10: Average keywords per transaction
     */
    public function testAverageKeywordsPerTransaction(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80, keywords: ['KEY1', 'KEY2']),
            MatchingReport::success('TXN-002', 2, 85, keywords: ['KEY1', 'KEY2', 'KEY3']),
            MatchingReport::failed('TXN-003', 'No match', keywords: ['KEY1']),
        ];

        $operation = new OperationReport($reports);

        // Total keywords: 2 + 3 + 1 = 6
        // Average: 6 / 3 = 2.0
        $this->assertSame(2.0, $operation->getAverageKeywordsPerTransaction());
    }

    /**
     * Test 11: Average candidates evaluated
     */
    public function testAverageCandidatesEvaluated(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80, candidatesEvaluated: 10),
            MatchingReport::success('TXN-002', 2, 85, candidatesEvaluated: 20),
            MatchingReport::failed('TXN-003', 'No match', candidatesEvaluated: 5),
        ];

        $operation = new OperationReport($reports);

        // Total: 10 + 20 + 5 = 35
        // Average: 35 / 3 = 11.666...
        $this->assertEqualsWithDelta(11.67, $operation->getAverageCandidatesEvaluated(), 0.01);
    }

    /**
     * Test 12: Add report to operation (fluent interface)
     */
    public function testAddReportFluent(): void
    {
        $operation = new OperationReport();
        $operation
            ->addReport(MatchingReport::success('TXN-001', 1, 80))
            ->addReport(MatchingReport::success('TXN-002', 2, 85))
            ->addReport(MatchingReport::failed('TXN-003', 'No match'));

        $this->assertSame(3, $operation->getTotalAttempted());
        $this->assertSame(2, $operation->getTotalSuccessful());
    }

    /**
     * Test 13: Get summary includes all key metrics
     */
    public function testGetSummary(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 85),
            MatchingReport::failed('TXN-003', 'No match'),
        ];

        $operation = new OperationReport($reports);
        $summary = $operation->getSummary();

        $this->assertArrayHasKey('total_attempted', $summary);
        $this->assertArrayHasKey('total_successful', $summary);
        $this->assertArrayHasKey('total_failed', $summary);
        $this->assertArrayHasKey('success_rate', $summary);
        $this->assertArrayHasKey('success_rate_percentage', $summary);
        $this->assertArrayHasKey('average_confidence', $summary);
        $this->assertArrayHasKey('confidence_distribution', $summary);
        $this->assertArrayHasKey('confidence_percentiles', $summary);

        $this->assertSame(3, $summary['total_attempted']);
        $this->assertSame(2, $summary['total_successful']);
        $this->assertSame(1, $summary['total_failed']);
    }

    /**
     * Test 14: Empty operation percentiles
     */
    public function testEmptyOperationPercentiles(): void
    {
        $operation = new OperationReport();
        $percentiles = $operation->getConfidencePercentiles();

        $this->assertEquals(0.0, $percentiles['p50']);
        $this->assertEquals(0.0, $percentiles['p75']);
        $this->assertEquals(0.0, $percentiles['p90']);
        $this->assertEquals(0.0, $percentiles['p95']);
    }

    /**
     * Test 15: Rule effectiveness with success/failure split
     */
    public function testRuleEffectivenessSuccessFailureSplit(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80, scoreBreakdown: ['Rule1' => 10]),
            MatchingReport::failed('TXN-002', 'No match', candidatesEvaluated: 5),
        ];

        $operation = new OperationReport($reports);
        $effectiveness = $operation->getRuleEffectiveness();

        // Rule1 fired only on success
        $this->assertEquals(10.0, $effectiveness['Rule1']['avg_when_successful']);
        $this->assertEquals(0.0, $effectiveness['Rule1']['avg_when_failed']);
    }

    /**
     * Test 16: Get reports (unchanged list)
     */
    public function testGetReports(): void
    {
        $r1 = MatchingReport::success('TXN-001', 1, 80);
        $r2 = MatchingReport::success('TXN-002', 2, 85);

        $operation = new OperationReport([$r1, $r2]);
        $reports = $operation->getReports();

        $this->assertCount(2, $reports);
        $this->assertSame($r1, $reports[0]);
        $this->assertSame($r2, $reports[1]);
    }

    /**
     * Test 17: Success rate with single attempt
     */
    public function testSuccessRateSingleAttempt(): void
    {
        $operation = new OperationReport([
            MatchingReport::success('TXN-001', 1, 85),
        ]);

        $this->assertSame(1.0, $operation->getSuccessRate());
        $this->assertSame(100.0, $operation->getSuccessRatePercentage());
    }

    /**
     * Test 18: Rule with no firings excluded
     */
    public function testRuleWithNoFiringsExcluded(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 80, scoreBreakdown: ['Rule1' => 5]),
            MatchingReport::success('TXN-002', 2, 85, scoreBreakdown: ['Rule1' => 3]),
        ];

        $operation = new OperationReport($reports);
        $effectiveness = $operation->getRuleEffectiveness();

        $this->assertArrayHasKey('Rule1', $effectiveness);
        $this->assertArrayNotHasKey('Rule2', $effectiveness);
    }

    /**
     * Test 19: Percentile with odd number of values
     */
    public function testPercentileWithOddCount(): void
    {
        $reports = [
            MatchingReport::success('TXN-001', 1, 10),
            MatchingReport::success('TXN-002', 2, 20),
            MatchingReport::success('TXN-003', 3, 30),
            MatchingReport::success('TXN-004', 4, 40),
            MatchingReport::success('TXN-005', 5, 50),
        ];

        $operation = new OperationReport($reports);
        $percentiles = $operation->getConfidencePercentiles();

        $this->assertSame(30.0, $percentiles['p50']);  // Median is 30
    }

    /**
     * Test 20: Most impactful rule from empty operation
     */
    public function testMostImpactfulRuleEmpty(): void
    {
        $operation = new OperationReport();
        $mostImpactful = $operation->getMostImpactfulRule();

        $this->assertNull($mostImpactful);
    }
}
