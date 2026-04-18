<?php

/**
 * MatchingAPI Test Suite
 *
 * Tests API endpoint functionality and service integration:
 * - Match transaction endpoint
 * - Report generation endpoints
 * - Analytics and statistics
 * - Error handling
 *
 * @covers \Ksfraser\FaBankImport\API\MatchingAPI
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\API;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ksfraser\FaBankImport\API\MatchingAPI;
use Ksfraser\FaBankImport\API\MatchTransactionRequest;
use Ksfraser\FaBankImport\API\MatchTransactionResponse;
use Ksfraser\FaBankImport\Reporting\MatchingReport;
use Ksfraser\FaBankImport\Reporting\ReportingService;

/**
 * MatchingAPI Tests
 */
class MatchingAPITest extends TestCase
{
    private MockObject $transactionMatching;
    private MockObject $keywordMatching;
    private MockObject $confidenceEnhancer;
    private ReportingService $reporting;
    private MatchingAPI $api;

    protected function setUp(): void
    {
        $this->transactionMatching = $this->createMock(\Ksfraser\FaBankImport\Services\TransactionMatchingService::class);
        $this->keywordMatching = $this->createMock(\Ksfraser\FaBankImport\Services\KeywordMatchingService::class);
        $this->confidenceEnhancer = $this->createMock(\Ksfraser\FaBankImport\Services\ConfidenceEnhancer::class);
        $this->reporting = new ReportingService();

        $this->api = new MatchingAPI(
            $this->transactionMatching,
            $this->keywordMatching,
            $this->confidenceEnhancer,
            $this->reporting
        );
    }

    /**
     * Test 1: Match transaction successfully
     */
    public function testMatchTransactionSuccess(): void
    {
        $request = new MatchTransactionRequest(
            'TXN-001',
            1500.00,
            'Payment to ABC Vendor',
            'payment'
        );

        $match = [
            'partner_id' => 123,
            'partner_name' => 'ABC Vendor',
            'confidence' => 85.5,
            'keywords' => ['VENDOR', 'ABC'],
            'score_breakdown' => ['Rule1' => 10, 'Rule2' => 3],
            'score_formula' => 'Rule1(10)+Rule2(3)=13',
        ];

        $this->keywordMatching
            ->expects($this->once())
            ->method('search')
            ->willReturn([$match]);

        $this->confidenceEnhancer
            ->expects($this->once())
            ->method('enhanceMultiple')
            ->willReturn([$match]);

        $this->confidenceEnhancer
            ->expects($this->once())
            ->method('getBestMatch')
            ->willReturn($match);

        $response = $this->api->matchTransaction($request);

        $this->assertInstanceOf(MatchTransactionResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(123, $response->getPartnerId());
        $this->assertSame(85.5, $response->getConfidence());
    }

    /**
     * Test 2: Match transaction fails - no keywords
     */
    public function testMatchTransactionFailsNoKeywords(): void
    {
        $request = new MatchTransactionRequest(
            'TXN-002',
            500.00,
            'xyz',
            'payment'
        );

        $this->keywordMatching
            ->expects($this->once())
            ->method('search')
            ->willReturn([]);

        $response = $this->api->matchTransaction($request);

        $this->assertFalse($response->isSuccess());
        $this->assertNull($response->getPartnerId());
        $this->assertSame(0.0, $response->getConfidence());
    }

    /**
     * Test 3: Match transaction fails - low confidence
     */
    public function testMatchTransactionFailsLowConfidence(): void
    {
        $request = new MatchTransactionRequest(
            'TXN-003',
            750.00,
            'Unknown vendor',
            'payment'
        );

        $match = new \stdClass();
        $match->partner_id = 456;
        $match->confidence = 35.0;

        $this->keywordMatching
            ->expects($this->once())
            ->method('search')
            ->willReturn([$match]);

        $this->confidenceEnhancer
            ->expects($this->once())
            ->method('enhanceMultiple')
            ->willReturn([]);

        $this->confidenceEnhancer
            ->expects($this->never())
            ->method('getBestMatch');

        $response = $this->api->matchTransaction($request);

        $this->assertFalse($response->isSuccess());
    }

    /**
     * Test 4: Get report summary
     */
    public function testGetReportSummary(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-002', 2, 90),
            MatchingReport::failed('TXN-003', 'No match'),
        ]);

        $response = $this->api->getReportSummary();

        $this->assertSame(3, $response->getTotalAttempted());
        $this->assertEqualsWithDelta(0.667, $response->getSuccessRate(), 0.01);
        $this->assertGreaterThan(50.0, $response->getAverageConfidence());
    }

    /**
     * Test 5: Get partner statistics
     */
    public function testGetPartnerStats(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-002', 1, 90),
            MatchingReport::failed('TXN-003', 'No match'),
        ]);

        $response = $this->api->getPartnerStats(1);

        $this->assertSame(1, $response->getPartnerId());
        // Partner 1 has 2 matches (one successful implicit from the second call)
        // This test depends on how failures are assigned
    }

    /**
     * Test 6: Get lowest confidence transactions
     */
    public function testGetLowestConfidenceTransactions(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 50),
            MatchingReport::success('TXN-002', 2, 85),
            MatchingReport::success('TXN-003', 3, 25),
        ]);

        $lowest = $this->api->getLowestConfidenceTransactions(2);

        $this->assertCount(2, $lowest);
        $this->assertSame('TXN-003', $lowest[0]['transaction_id']);
        $this->assertSame(25.0, $lowest[0]['confidence']);
    }

    /**
     * Test 7: Get highest confidence transactions
     */
    public function testGetHighestConfidenceTransactions(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 50),
            MatchingReport::success('TXN-002', 2, 95),
            MatchingReport::success('TXN-003', 3, 75),
        ]);

        $highest = $this->api->getHighestConfidenceTransactions(2);

        $this->assertCount(2, $highest);
        $this->assertSame('TXN-002', $highest[0]['transaction_id']);
        $this->assertSame(95.0, $highest[0]['confidence']);
    }

    /**
     * Test 8: Get top partners by success rate
     */
    public function testGetTopPartnersBySuccessRate(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 80),
            MatchingReport::success('TXN-002', 1, 85),
            MatchingReport::failed('TXN-003', 'No match'),
            MatchingReport::success('TXN-004', 2, 90),
            MatchingReport::success('TXN-005', 2, 88),
            MatchingReport::success('TXN-006', 2, 86),
        ]);

        $topPartners = $this->api->getTopPartnersBySuccessRate(2);

        $this->assertIsArray($topPartners);
        $this->assertGreaterThan(0, count($topPartners));
    }

    /**
     * Test 9: Get failure reasons summary
     */
    public function testGetFailureReasonsSummary(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::failed('TXN-001', 'No keywords'),
            MatchingReport::failed('TXN-002', 'No keywords'),
            MatchingReport::failed('TXN-003', 'Low confidence'),
            MatchingReport::success('TXN-004', 1, 85),
        ]);

        $reasons = $this->api->getFailureReasonsSummary();

        $this->assertCount(2, $reasons);
        $this->assertSame('No keywords', $reasons[0]['reason']);
        $this->assertSame(2, $reasons[0]['count']);
    }

    /**
     * Test 10: Get keyword frequency
     */
    public function testGetKeywordFrequency(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85, keywords: ['VENDOR', 'INC']),
            MatchingReport::success('TXN-002', 2, 90, keywords: ['VENDOR', 'LLC']),
            MatchingReport::success('TXN-003', 3, 75, keywords: ['VENDOR']),
        ]);

        $keywords = $this->api->getKeywordFrequency(10);

        $this->assertGreaterThan(0, count($keywords));
        // VENDOR should be most frequent
        $vendorEntry = array_filter($keywords, fn($k) => $k['keyword'] === 'VENDOR');
        $this->assertNotEmpty($vendorEntry);
    }

    /**
     * Test 11: Get transaction reports
     */
    public function testGetTransactionReports(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-001', 2, 80),  // Same txn, different partner
        ]);

        $reports = $this->api->getTransactionReports('TXN-001');

        $this->assertCount(2, $reports);
        $this->assertSame('TXN-001', $reports[0]['transaction_id']);
    }

    /**
     * Test 12: Clear reports (admin operation)
     */
    public function testClearReports(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::success('TXN-002', 2, 90),
        ]);

        $this->assertSame(2, $this->reporting->getTotalRecorded());

        $this->api->clearReports();

        $this->assertSame(0, $this->reporting->getTotalRecorded());
    }

    /**
     * Test 13: Get session metrics
     */
    public function testGetSessionMetrics(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
            MatchingReport::failed('TXN-002', 'No match'),
        ]);

        $metrics = $this->api->getSessionMetrics();

        $this->assertArrayHasKey('session_start', $metrics);
        $this->assertArrayHasKey('session_duration_seconds', $metrics);
        $this->assertArrayHasKey('total_recorded', $metrics);
        $this->assertSame(2, $metrics['total_recorded']);
        $this->assertSame(1, $metrics['total_successful']);
        $this->assertSame(1, $metrics['total_failed']);
    }

    /**
     * Test 14: Match transaction records to reporting service
     */
    public function testMatchTransactionRecordsToReporting(): void
    {
        $request = new MatchTransactionRequest(
            'TXN-001',
            1000.00,
            'Test',
            'payment'
        );

        $this->keywordMatching
            ->method('search')
            ->willReturn([]);

        $this->api->matchTransaction($request);

        $summary = $this->api->getReportSummary();

        $this->assertSame(1, $summary->getTotalAttempted());
    }

    /**
     * Test 15: Empty reporting state
     */
    public function testEmptyReportingState(): void
    {
        $response = $this->api->getReportSummary();

        $this->assertSame(0, $response->getTotalAttempted());
        $this->assertSame(0.0, $response->getSuccessRate());
    }

    /**
     * Test 16: Confidence level classification HIGH
     */
    public function testConfidenceLevelHigh(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 75),
        ]);

        $highest = $this->api->getHighestConfidenceTransactions(1);

        $this->assertSame('HIGH', $highest[0]['confidence_level']);
    }

    /**
     * Test 17: Confidence level classification MEDIUM
     */
    public function testConfidenceLevelMedium(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 55),
        ]);

        $lowest = $this->api->getLowestConfidenceTransactions(1);

        $this->assertSame('MEDIUM', $lowest[0]['confidence_level']);
    }

    /**
     * Test 18: Confidence level classification LOW
     */
    public function testConfidenceLevelLow(): void
    {
        $this->reporting->recordMatches([
            MatchingReport::uncertain('TXN-001', 1, 25, 'Low confidence'),
        ]);

        $lowest = $this->api->getLowestConfidenceTransactions(1);

        $this->assertSame('LOW', $lowest[0]['confidence_level']);
    }

    /**
     * Test 19: Get top partners with minimum matches filter
     */
    public function testGetTopPartnersMinimumMatches(): void
    {
        // Add single match for partner 1 (won't meet minimum of 5)
        $this->reporting->recordMatches([
            MatchingReport::success('TXN-001', 1, 85),
        ]);

        // Add many matches for partner 2 (will meet minimum)
        for ($i = 2; $i <= 6; $i++) {
            $this->reporting->recordMatch(
                MatchingReport::success("TXN-{$i}", 2, 80 + $i)
            );
        }

        $topPartners = $this->api->getTopPartnersBySuccessRate(5, 10);

        // Partner 2 should be included, Partner 1 should be excluded
        $partnerIds = array_map(fn($p) => $p['partner_id'], $topPartners);
        $this->assertNotContains(1, $partnerIds);
        $this->assertContains(2, $partnerIds);
    }

    /**
     * Test 20: Get keyword frequency with limit
     */
    public function testGetKeywordFrequencyWithLimit(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $this->reporting->recordMatch(
                MatchingReport::success(
                    "TXN-{$i}",
                    1,
                    85,
                    keywords: ["KEYWORD-{$i}"]
                )
            );
        }

        $keywords = $this->api->getKeywordFrequency(10);

        $this->assertCount(10, $keywords);
    }
}
