<?php

/**
 * API DTOs Test Suite
 *
 * Tests request/response validation and serialization:
 * - Request validation (required fields, types)
 * - Response generation and array conversion
 * - Error handling
 *
 * @covers \Ksfraser\FaBankImport\API\MatchTransactionRequest
 * @covers \Ksfraser\FaBankImport\API\MatchTransactionResponse
 * @covers \Ksfraser\FaBankImport\API\ReportSummaryResponse
 * @covers \Ksfraser\FaBankImport\API\PartnerStatsResponse
 * @covers \Ksfraser\FaBankImport\API\APIErrorResponse
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\API;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\API\MatchTransactionRequest;
use Ksfraser\FaBankImport\API\MatchTransactionResponse;
use Ksfraser\FaBankImport\API\ReportSummaryResponse;
use Ksfraser\FaBankImport\API\PartnerStatsResponse;
use Ksfraser\FaBankImport\API\APIErrorResponse;

/**
 * API DTOs Tests
 */
class APIDTOsTest extends TestCase
{
    /**
     * Test 1: Create valid MatchTransactionRequest
     */
    public function testValidMatchTransactionRequest(): void
    {
        $request = new MatchTransactionRequest(
            transactionId: 'TXN-001',
            amount: 1500.00,
            description: 'Payment to Vendor',
            transactionType: 'payment'
        );

        $this->assertSame('TXN-001', $request->getTransactionId());
        $this->assertSame(1500.00, $request->getAmount());
        $this->assertSame('Payment to Vendor', $request->getDescription());
        $this->assertSame('payment', $request->getTransactionType());
    }

    /**
     * Test 2: Request rejects empty transaction ID
     */
    public function testRequestRejectsEmptyTransactionId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MatchTransactionRequest(
            transactionId: '',
            amount: 100,
            description: 'Test',
            transactionType: 'payment'
        );
    }

    /**
     * Test 3: Request rejects negative amount
     */
    public function testRequestRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MatchTransactionRequest(
            transactionId: 'TXN-001',
            amount: -100.0,
            description: 'Test',
            transactionType: 'payment'
        );
    }

    /**
     * Test 4: Request rejects empty description
     */
    public function testRequestRejectsEmptyDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MatchTransactionRequest(
            transactionId: 'TXN-001',
            amount: 100,
            description: '',
            transactionType: 'payment'
        );
    }

    /**
     * Test 5: Create request from array
     */
    public function testCreateRequestFromArray(): void
    {
        $data = [
            'transaction_id' => 'TXN-001',
            'amount' => 1500.50,
            'description' => 'Vendor Payment',
            'type' => 'payment',
            'reference_number' => 'REF-001',
        ];

        $request = MatchTransactionRequest::fromArray($data);

        $this->assertSame('TXN-001', $request->getTransactionId());
        $this->assertSame(1500.50, $request->getAmount());
        $this->assertSame('REF-001', $request->getReferenceNumber());
    }

    /**
     * Test 6: Request requires all fields from array
     */
    public function testRequestRequiresAllFieldsFromArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be empty/');

        MatchTransactionRequest::fromArray([
            'transaction_id' => 'TXN-001',
            'amount' => 100,
            // Missing description and type
        ]);
    }

    /**
     * Test 7: Create MatchTransactionResponse
     */
    public function testCreateMatchTransactionResponse(): void
    {
        $response = new MatchTransactionResponse(
            transactionId: 'TXN-001',
            success: true,
            partnerId: 123,
            partnerName: 'ABC Vendor',
            confidence: 85.5,
            confidenceLevel: 'HIGH',
            scoreFormula: 'Rule1(10)+Rule2(3)=13',
            scoreBreakdown: ['Rule1' => 10, 'Rule2' => 3],
            keywords: ['VENDOR', 'ABC'],
            reason: 'Successfully matched'
        );

        $this->assertTrue($response->isSuccess());
        $this->assertSame(123, $response->getPartnerId());
        $this->assertSame(85.5, $response->getConfidence());
    }

    /**
     * Test 8: Response converts to array
     */
    public function testResponseConvertsToArray(): void
    {
        $response = new MatchTransactionResponse(
            transactionId: 'TXN-001',
            success: true,
            partnerId: 123,
            partnerName: 'ABC Vendor',
            confidence: 85.5,
            confidenceLevel: 'HIGH',
            scoreFormula: 'Rule1(10)=10',
            scoreBreakdown: ['Rule1' => 10],
            keywords: ['VENDOR'],
            reason: 'Success'
        );

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertSame('TXN-001', $array['transaction_id']);
        $this->assertTrue($array['success']);
        $this->assertSame(123, $array['partner']['id']);
        $this->assertSame(85.5, $array['confidence']['score']);
    }

    /**
     * Test 9: Create ReportSummaryResponse
     */
    public function testCreateReportSummaryResponse(): void
    {
        $response = new ReportSummaryResponse(
            totalAttempted: 100,
            totalSuccessful: 85,
            totalFailed: 15,
            successRate: 0.85,
            averageConfidence: 75.5,
            confidenceDistribution: ['HIGH' => 70, 'MEDIUM' => 20, 'LOW' => 10],
            confidencePercentiles: ['p50' => 75, 'p75' => 85, 'p90' => 92, 'p95' => 95],
            mostImpactfulRule: 'RecencyRule',
            averageKeywords: 3.5,
            averageCandidatesEvaluated: 12.0
        );

        $this->assertSame(0.85, $response->getSuccessRate());
        $this->assertSame(75.5, $response->getAverageConfidence());
        $this->assertSame(100, $response->getTotalAttempted());
    }

    /**
     * Test 10: ReportSummaryResponse converts to array
     */
    public function testReportSummaryConvertsToArray(): void
    {
        $response = new ReportSummaryResponse(
            totalAttempted: 100,
            totalSuccessful: 85,
            totalFailed: 15,
            successRate: 0.85,
            averageConfidence: 75.5,
            confidenceDistribution: ['HIGH' => 70, 'MEDIUM' => 20, 'LOW' => 10],
            confidencePercentiles: ['p50' => 75, 'p75' => 85, 'p90' => 92, 'p95' => 95],
            mostImpactfulRule: 'RecencyRule',
            averageKeywords: 3.5,
            averageCandidatesEvaluated: 12.0
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('matching_summary', $array);
        $this->assertArrayHasKey('success_metrics', $array);
        $this->assertArrayHasKey('confidence_metrics', $array);
        $this->assertSame(100, $array['matching_summary']['total_attempted']);
        $this->assertSame(85, $array['matching_summary']['total_successful']);
    }

    /**
     * Test 11: Create PartnerStatsResponse
     */
    public function testCreatePartnerStatsResponse(): void
    {
        $response = new PartnerStatsResponse(
            partnerId: 123,
            partnerName: 'ABC Vendor',
            totalMatches: 50,
            successfulMatches: 45,
            successRate: 0.9,
            averageConfidence: 80.0,
            confidenceDistribution: ['HIGH' => 40, 'MEDIUM' => 8, 'LOW' => 2],
            mostRecentMatch: 1642345800
        );

        $this->assertSame(123, $response->getPartnerId());
        $this->assertSame(50, $response->getTotalMatches());
        $this->assertSame(0.9, $response->getSuccessRate());
    }

    /**
     * Test 12: PartnerStatsResponse converts to array
     */
    public function testPartnerStatsConvertsToArray(): void
    {
        $response = new PartnerStatsResponse(
            partnerId: 123,
            partnerName: 'ABC Vendor',
            totalMatches: 50,
            successfulMatches: 45,
            successRate: 0.9,
            averageConfidence: 80.0,
            confidenceDistribution: ['HIGH' => 40, 'MEDIUM' => 8, 'LOW' => 2],
            mostRecentMatch: 1642345800
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('partner', $array);
        $this->assertArrayHasKey('matching_stats', $array);
        $this->assertSame(123, $array['partner']['id']);
        $this->assertSame(50, $array['matching_stats']['total_matches']);
        $this->assertSame(90.0, $array['matching_stats']['success_rate_percentage']);
    }

    /**
     * Test 13: Create APIErrorResponse
     */
    public function testCreateAPIErrorResponse(): void
    {
        $response = new APIErrorResponse(
            statusCode: 400,
            message: 'Invalid request',
            code: 'INVALID_REQUEST',
            details: ['field' => 'amount', 'error' => 'must be positive']
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid request', $response->getMessage());
        $this->assertSame('INVALID_REQUEST', $response->getCode());
    }

    /**
     * Test 14: APIErrorResponse converts to array
     */
    public function testAPIErrorConvertsToArray(): void
    {
        $response = new APIErrorResponse(
            statusCode: 404,
            message: 'Not found',
            code: 'NOT_FOUND',
            details: ['resource' => 'Partner', 'id' => 999]
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('error', $array);
        $this->assertSame(404, $array['error']['status_code']);
        $this->assertSame('Not found', $array['error']['message']);
    }

    /**
     * Test 15: Request with zero amount allowed
     */
    public function testRequestAllowsZeroAmount(): void
    {
        $request = new MatchTransactionRequest(
            transactionId: 'TXN-001',
            amount: 0.0,
            description: 'Zero transaction',
            transactionType: 'adjustment'
        );

        $this->assertSame(0.0, $request->getAmount());
    }

    /**
     * Test 16: Request with optional reference number
     */
    public function testRequestOptionalReferenceNumber(): void
    {
        $request = new MatchTransactionRequest(
            transactionId: 'TXN-001',
            amount: 100,
            description: 'Test',
            transactionType: 'payment',
            referenceNumber: null
        );

        $this->assertNull($request->getReferenceNumber());
    }

    /**
     * Test 17: Response failure case
     */
    public function testResponseFailureCase(): void
    {
        $response = new MatchTransactionResponse(
            transactionId: 'TXN-999',
            success: false,
            partnerId: null,
            partnerName: null,
            confidence: 0.0,
            confidenceLevel: 'LOW',
            scoreFormula: null,
            scoreBreakdown: [],
            keywords: [],
            reason: 'No matching partners found'
        );

        $this->assertFalse($response->isSuccess());
        $this->assertNull($response->getPartnerId());
        $this->assertSame('No matching partners found', $response->getReason());
    }

    /**
     * Test 18: ReportSummary with zero attempts
     */
    public function testReportSummaryZeroAttempts(): void
    {
        $response = new ReportSummaryResponse(
            totalAttempted: 0,
            totalSuccessful: 0,
            totalFailed: 0,
            successRate: 0.0,
            averageConfidence: 0.0,
            confidenceDistribution: ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0],
            confidencePercentiles: ['p50' => 0, 'p75' => 0, 'p90' => 0, 'p95' => 0],
            mostImpactfulRule: 'Unknown',
            averageKeywords: 0.0,
            averageCandidatesEvaluated: 0.0
        );

        $this->assertSame(0.0, $response->getSuccessRate());
        $this->assertSame(0, $response->getTotalAttempted());
    }

    /**
     * Test 19: PartnerStats empty
     */
    public function testPartnerStatsEmpty(): void
    {
        $response = new PartnerStatsResponse(
            partnerId: 999,
            partnerName: 'Unknown',
            totalMatches: 0,
            successfulMatches: 0,
            successRate: 0.0,
            averageConfidence: 0.0,
            confidenceDistribution: ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0],
            mostRecentMatch: 0
        );

        $this->assertSame(0, $response->getTotalMatches());
        $this->assertSame(0.0, $response->getSuccessRate());
    }

    /**
     * Test 20: Error response with minimal details
     */
    public function testErrorResponseMinimal(): void
    {
        $response = new APIErrorResponse(
            statusCode: 500,
            message: 'Internal server error'
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertNull($response->getCode());

        $array = $response->toArray();
        $this->assertSame(500, $array['error']['status_code']);
    }
}
