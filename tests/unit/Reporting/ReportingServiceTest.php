<?php

/**
 * ReportingService Test Suite
 *
 * Tests reporting service functionality:
 * - Recording and tracking match operations
 * - Querying and filtering reports
 * - Analytics and trend analysis
 * - Session tracking
 *
 * @covers \Ksfraser\FaBankImport\Reporting\ReportingService
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Reporting;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Reporting\MatchingReport;
use Ksfraser\FaBankImport\Reporting\ReportingService;
use Ksfraser\FaBankImport\Reporting\OperationReport;
use DateTime;

/**
 * ReportingService Tests
 */
class ReportingServiceTest extends TestCase
{
    /**
     * Test 1: Create service with no reports
     */
    public function testCreateEmptyService(): void
    {
        $service = new ReportingService();

        $this->assertSame(0, $service->getTotalRecorded());
    }

    /**
     * Test 2: Record single match
     */
    public function testRecordSingleMatch(): void
    {
        $service = new ReportingService();
        $report = MatchingReport::success('TXN-001', 1, 80);

        $service->recordMatch($report);

        $this->assertSame(1, $service->getTotalRecorded());
    }

    /**
     * Test 3: Record multiple matches
     */
    public function testRecordMultipleMatches(): void
    {
        $service = new ReportingService();
        $reports = [
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 85),
            MatchingReport::failed('TXN-003', 'No match'),
        ];

        $service->recordMatches($reports);

        $this->assertSame(3, $service->getTotalRecorded());
    }

    /**
     * Test 4: Generate operation report for all matches
     */
    public function testGenerateOperationReport(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 85),
            MatchingReport::failed('TXN-003', 'No match'),
        ]);

        $operation = $service->generateOperationReport();

        $this->assertInstanceOf(OperationReport::class, $operation);
        $this->assertSame(3, $operation->getTotalAttempted());
        $this->assertSame(2, $operation->getTotalSuccessful());
    }

    /**
     * Test 5: Generate success-only report
     */
    public function testGenerateSuccessReport(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 85),
            MatchingReport::failed('TXN-003', 'No match'),
            MatchingReport::failed('TXN-004', 'No match'),
        ]);

        $operation = $service->generateSuccessReport();

        $this->assertSame(2, $operation->getTotalAttempted());
        $this->assertSame(2, $operation->getTotalSuccessful());
        $this->assertSame(0, $operation->getTotalFailed());
    }

    /**
     * Test 6: Generate failure-only report
     */
    public function testGenerateFailureReport(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::failed('TXN-002', 'No match'),
            MatchingReport::failed('TXN-003', 'No match'),
        ]);

        $operation = $service->generateFailureReport();

        $this->assertSame(2, $operation->getTotalAttempted());
        $this->assertSame(0, $operation->getTotalSuccessful());
        $this->assertSame(2, $operation->getTotalFailed());
    }

    /**
     * Test 7: Get reports by transaction ID
     */
    public function testGetReportsByTransaction(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-001', 2, 85),  // Same txn, different partner
            MatchingReport::success('TXN-002', 3, 75),
        ]);

        $txn1Reports = $service->getReportsByTransaction('TXN-001');

        $this->assertCount(2, $txn1Reports);
    }

    /**
     * Test 8: Get reports by partner ID
     */
    public function testGetReportsByPartner(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 1, 85),  // Different txn, same partner
            MatchingReport::success('TXN-003', 2, 75),
        ]);

        $partnerReports = $service->getReportsByPartner(1);

        $this->assertCount(2, $partnerReports);
    }

    /**
     * Test 9: Get reports by confidence range
     */
    public function testGetReportsByConfidenceRange(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 50),
            MatchingReport::success('TXN-002', 2, 75),
            MatchingReport::success('TXN-003', 3, 90),
        ]);

        $inRange = $service->getReportsByConfidenceRange(70, 95);

        $this->assertCount(2, $inRange);  // 75 and 90
    }

    /**
     * Test 10: Get reports by confidence level
     */
    public function testGetReportsByConfidenceLevel(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),  // HIGH
            MatchingReport::success('TXN-002', 2, 50),  // MEDIUM
            MatchingReport::uncertain('TXN-003', 3, 35, 'Low'),  // LOW
        ]);

        $highConfidence = $service->getReportsByConfidenceLevel('HIGH');

        $this->assertCount(1, $highConfidence);
    }

    /**
     * Test 11: Get reports before datetime
     */
    public function testGetReportsBefore(): void
    {
        $service = new ReportingService();
        $before = new DateTime('2025-01-15');
        $after = new DateTime('2025-01-16');

        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
        ]);

        // Reports recorded "now", should be after the before date
        $reportsBefore = $service->getReportsBefore($before);

        $this->assertCount(0, $reportsBefore);
    }

    /**
     * Test 12: Get lowest confidence transactions
     */
    public function testGetLowestConfidenceTransactions(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-002', 2, 50),
            MatchingReport::uncertain('TXN-003', 3, 25, 'Low'),
            MatchingReport::uncertain('TXN-004', 4, 40, 'Low'),
        ]);

        $lowest = $service->getLowestConfidenceTransactions(2);

        $this->assertCount(2, $lowest);
        // TXN-003 should have lowest (25)
        $this->assertArrayHasKey('TXN-003', $lowest);
    }

    /**
     * Test 13: Get highest confidence transactions
     */
    public function testGetHighestConfidenceTransactions(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-002', 2, 95),
            MatchingReport::success('TXN-003', 3, 75),
        ]);

        $highest = $service->getHighestConfidenceTransactions(2);

        $this->assertCount(2, $highest);
        // TXN-002 should be first (95)
        $firstKey = key($highest);
        $this->assertSame('TXN-002', $firstKey);
    }

    /**
     * Test 14: Get top partners by success rate
     */
    public function testGetTopPartnersBySuccessRate(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 1, 85),
            MatchingReport::failed('TXN-003', 'No match'),
            MatchingReport::success('TXN-004', 2, 90),
            MatchingReport::success('TXN-005', 2, 88),
        ]);

        $topPartners = $service->getTopPartnersBySuccessRate(2);

        // Partner 1: 2 successful out of 3 = 0.667
        // Partner 2: 2 successful out of 2 = 1.0
        $this->assertArrayHasKey(2, $topPartners);
        $this->assertSame(1.0, $topPartners[2]);
    }

    /**
     * Test 15: Get failure reasons summary
     */
    public function testGetFailureReasonsSummary(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::failed('TXN-001', 'No matching keywords'),
            MatchingReport::failed('TXN-002', 'No matching keywords'),
            MatchingReport::failed('TXN-003', 'Low confidence'),
            MatchingReport::success('TXN-004', 1, 85),
        ]);

        $reasons = $service->getFailureReasonsSummary();

        $this->assertArrayHasKey('No matching keywords', $reasons);
        $this->assertSame(2, $reasons['No matching keywords']);
        $this->assertArrayHasKey('Low confidence', $reasons);
        $this->assertSame(1, $reasons['Low confidence']);
    }

    /**
     * Test 16: Get all keywords
     */
    public function testGetAllKeywords(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80, keywords: ['VENDOR', 'INC']),
            MatchingReport::success('TXN-002', 2, 85, keywords: ['VENDOR', 'LLC']),
            MatchingReport::failed('TXN-003', 'No match', keywords: ['CORP']),
        ]);

        $keywords = $service->getAllKeywords();

        $this->assertCount(3, $keywords);
        $this->assertContains('VENDOR', $keywords);
        $this->assertContains('INC', $keywords);
        $this->assertContains('CORP', $keywords);
    }

    /**
     * Test 17: Get keyword frequency
     */
    public function testGetKeywordFrequency(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80, keywords: ['VENDOR', 'INC']),
            MatchingReport::success('TXN-002', 2, 85, keywords: ['VENDOR', 'LLC']),
            MatchingReport::success('TXN-003', 3, 75, keywords: ['VENDOR']),
        ]);

        $frequency = $service->getKeywordFrequency();

        $this->assertSame(3, $frequency['VENDOR']);
        $this->assertSame(1, $frequency['INC']);
        $this->assertSame(1, $frequency['LLC']);
    }

    /**
     * Test 18: Reset service
     */
    public function testResetService(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 2, 85),
        ]);

        $this->assertSame(2, $service->getTotalRecorded());

        $service->reset();

        $this->assertSame(0, $service->getTotalRecorded());
    }

    /**
     * Test 19: Session start time
     */
    public function testSessionStartTime(): void
    {
        $before = new DateTime();
        $service = new ReportingService();
        $after = new DateTime();

        $sessionStart = $service->getSessionStart();

        $this->assertGreaterThanOrEqual($before, $sessionStart);
        $this->assertLessThanOrEqual($after, $sessionStart);
    }

    /**
     * Test 20: Session duration increases  
     */
    public function testSessionDuration(): void
    {
        $service = new ReportingService();
        $duration1 = $service->getSessionDuration();

        sleep(1);

        $duration2 = $service->getSessionDuration();

        $this->assertGreaterThanOrEqual($duration1, $duration2);
    }

    /**
     * Test 21: Get all raw reports
     */
    public function testGetAllReports(): void
    {
        $service = new ReportingService();
        $r1 = MatchingReport::success('TXN-001', 1, 80);
        $r2 = MatchingReport::success('TXN-002', 2, 85);

        $service->recordMatch($r1);
        $service->recordMatch($r2);

        $all = $service->getAllReports();

        $this->assertCount(2, $all);
    }

    /**
     * Test 22: Multiple filters can be chained conceptually
     */
    public function testMultipleQueries(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-002', 1, 75),
            MatchingReport::success('TXN-003', 2, 90),
            MatchingReport::failed('TXN-004', 'No match'),
        ]);

        // Query: Partner 1 reports
        $partner1 = $service->getReportsByPartner(1);
        $this->assertCount(2, $partner1);

        // Query: High confidence
        $highConf = $service->getReportsByConfidenceLevel('HIGH');
        $this->assertCount(2, $highConf);  // TXN-001 and TXN-003

        // Query: Success only
        $success = $service->generateSuccessReport();
        $this->assertSame(3, $success->getTotalAttempted());
    }

    /**
     * Test 23: Lowest confidence with limit
     */
    public function testLowestConfidenceWithLimit(): void
    {
        $service = new ReportingService();
        $service->recordMatches([
            MatchingReport::success('TXN-001', 1, 10),
            MatchingReport::success('TXN-002', 2, 20),
            MatchingReport::success('TXN-003', 3, 30),
            MatchingReport::success('TXN-004', 4, 40),
            MatchingReport::success('TXN-005', 5, 50),
        ]);

        $lowest3 = $service->getLowestConfidenceTransactions(3);

        $this->assertCount(3, $lowest3);
    }

    /**
     * Test 24: Empty service operations
     */
    public function testEmptyServiceOperations(): void
    {
        $service = new ReportingService();

        $successReport = $service->generateSuccessReport();
        $failureReport = $service->generateFailureReport();
        $keywords = $service->getAllKeywords();
        $reasons = $service->getFailureReasonsSummary();

        $this->assertSame(0, $successReport->getTotalAttempted());
        $this->assertSame(0, $failureReport->getTotalAttempted());
        $this->assertEmpty($keywords);
        $this->assertEmpty($reasons);
    }
}
