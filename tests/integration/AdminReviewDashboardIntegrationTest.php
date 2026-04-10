<?php
/**
 * Story 3: Admin Review Dashboard - Integration Tests
 *
 * Tests the complete workflow:
 * 1. List pending duplicates with filters
 * 2. Get duplicate details
 * 3. Make decision (approve/reject/investigate)
 * 4. Verify audit trail
 *
 * @package Ksfraser\FaBankImport\Tests\Integration
 */

namespace Ksfraser\FaBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Services\DTOs\DuplicateReviewDisplay;
use Ksfraser\FaBankImport\Import\Services\DTOs\QueryFilters;
use Ksfraser\FaBankImport\Import\Services\DTOs\ReviewDecisionRequest;
use Ksfraser\FaBankImport\Import\Services\Review\AdminReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\DuplicateReviewService;
use Ksfraser\FaBankImport\Import\Controllers\AdminReviewController;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Import\Exceptions\DuplicateReviewException;

class AdminReviewDashboardIntegrationTest extends TestCase
{
    private $adminReviewService;
    private $duplicateReviewService;
    private $controller;
    private $testRepository;
    private $testLogger;
    private $testResponseHandler;

    protected function setUp(): void
    {
        // Create test repository with sample data
        $this->testRepository = new TestDuplicateTransactionRepository();
        $this->testLogger = new TestLogger();
        $this->testResponseHandler = new TestResponseHandler();

        // Initialize service dependencies
        $this->duplicateReviewService = new TestDuplicateReviewService();
        $this->adminReviewService = new AdminReviewService(
            $this->testRepository,
            $this->duplicateReviewService,
            $this->testLogger
        );

        // Initialize controller
        $this->controller = new AdminReviewController(
            $this->adminReviewService,
            $this->testResponseHandler
        );

        // Populate repository with test data
        $this->populateTestData();
    }

    private function populateTestData(): void
    {
        // Add pending duplicate transactions
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 1,
            transactionCode: 'TXN-2026-001',
            transDate: '2026-04-01',
            amount: 1000.00,
            counterpartyName: 'Vendor A',
            matchReason: 'Code + Date match',
            confidenceScore: 95,
            decisionStatus: 'PENDING',
            createdAt: '2026-04-08',
            decidedBy: null,
            decidedAt: null,
            reason: null
        ));

        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 2,
            transactionCode: 'TXN-2026-002',
            transDate: '2026-04-02',
            amount: 2500.50,
            counterpartyName: 'Customer B',
            matchReason: 'Amount + Date partial match (98% similar)',
            confidenceScore: 78,
            decisionStatus: 'PENDING',
            createdAt: '2026-04-08',
            decidedBy: null,
            decidedAt: null,
            reason: null
        ));

        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 3,
            transactionCode: 'TXN-2026-003',
            transDate: '2026-03-15',
            amount: 500.00,
            counterpartyName: 'Service Provider C',
            matchReason: 'Code exact match but date differs by 1 day',
            confidenceScore: 65,
            decisionStatus: 'PENDING',
            createdAt: '2026-04-08',
            decidedBy: null,
            decidedAt: null,
            reason: null
        ));

        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 4,
            transactionCode: 'TXN-2026-004',
            transDate: '2026-04-05',
            amount: 1500.00,
            counterpartyName: 'Vendor A',
            matchReason: 'Full match (code + date + amount)',
            confidenceScore: 100,
            decisionStatus: 'PENDING',
            createdAt: '2026-04-08',
            decidedBy: null,
            decidedAt: null,
            reason: null
        ));
    }

    /**
     * @test
     * List duplicates returns all pending transactions
     */
    public function test_list_duplicates_returns_pending_without_filter(): void
    {
        $filters = new QueryFilters(page: 1, perPage: 10);
        $result = $this->adminReviewService->queryPendingDuplicates($filters);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);

        $this->assertCount(4, $result['items']);
        $this->assertEquals(4, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['per_page']);

        // Verify items are DuplicateReviewDisplay DTOs
        foreach ($result['items'] as $item) {
            $this->assertInstanceOf(DuplicateReviewDisplay::class, $item);
        }
    }

    /**
     * @test
     * List duplicates applies date range filter
     */
    public function test_list_duplicates_filters_by_date_range(): void
    {
        $filters = new QueryFilters(
            page: 1,
            perPage: 10,
            status: 'PENDING',
            startDate: '2026-04-01',
            endDate: '2026-04-02'
        );

        $result = $this->adminReviewService->queryPendingDuplicates($filters);

        // Should return only TXN-001 and TXN-002
        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['total']);

        $codes = array_map(function($item) { return $item->transactionCode; }, $result['items']);
        $this->assertContains('TXN-2026-001', $codes);
        $this->assertContains('TXN-2026-002', $codes);
    }

    /**
     * @test
     * List duplicates filters by confidence threshold
     */
    public function test_list_duplicates_filters_by_confidence(): void
    {
        $filters = new QueryFilters(
            page: 1,
            perPage: 10,
            status: 'PENDING',
            minConfidence: 80
        );

        $result = $this->adminReviewService->queryPendingDuplicates($filters);

        // Should return TXN-001 (95%), TXN-004 (100%)
        $this->assertCount(2, $result['items']);

        foreach ($result['items'] as $item) {
            $this->assertGreaterThanOrEqual(80, $item->confidenceScore);
        }
    }

    /**
     * @test
     * List duplicates supports pagination
     */
    public function test_list_duplicates_pagination(): void
    {
        // Page 1 with 2 per page
        $filters = new QueryFilters(page: 1, perPage: 2);
        $result1 = $this->adminReviewService->queryPendingDuplicates($filters);

        $this->assertCount(2, $result1['items']);
        $this->assertEquals(4, $result1['total']);
        $this->assertEquals(1, $result1['page']);

        // Page 2 with 2 per page
        $filters = new QueryFilters(page: 2, perPage: 2);
        $result2 = $this->adminReviewService->queryPendingDuplicates($filters);

        $this->assertCount(2, $result2['items']);
        $this->assertEquals(2, $result2['page']);

        // Verify different items
        $ids1 = array_map(function($item) { return $item->id; }, $result1['items']);
        $ids2 = array_map(function($item) { return $item->id; }, $result2['items']);
        $this->assertNotEqual($ids1, $ids2);
    }

    /**
     * @test
     * Get duplicate details returns single transaction
     */
    public function test_get_duplicate_details_returns_single_transaction(): void
    {
        $display = $this->adminReviewService->getDuplicateDetails(1);

        $this->assertInstanceOf(DuplicateReviewDisplay::class, $display);
        $this->assertEquals(1, $display->id);
        $this->assertEquals('TXN-2026-001', $display->transactionCode);
        $this->assertEquals('2026-04-01', $display->transDate);
        $this->assertEquals(1000.00, $display->amount);
        $this->assertEquals('Vendor A', $display->counterpartyName);
        $this->assertEquals(95, $display->confidenceScore);
        $this->assertTrue($display->isPending());
        $this->assertFalse($display->isReviewed());
    }

    /**
     * @test
     * Get duplicate details throws exception for non-existent transaction
     */
    public function test_get_duplicate_details_throws_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->adminReviewService->getDuplicateDetails(999);
    }

    /**
     * @test
     * Record decision approve flow
     */
    public function test_record_decision_approve_flow(): void
    {
        $request = new ReviewDecisionRequest(
            duplicateId: 1,
            decision: 'APPROVED',
            reason: 'Clear duplicate match'
        );

        // Record decision
        $this->adminReviewService->recordReviewDecision($request, 'admin@example.com');

        // Verify decision was recorded
        $this->assertTrue($this->duplicateReviewService->approveCalled);
        $this->assertEquals(1, $this->duplicateReviewService->lastApprovedId);

        // Verify logging
        $this->assertCount(1, $this->testLogger->infoCalls);
        $this->assertStringContainsString('Decision recorded', $this->testLogger->infoCalls[0]);
    }

    /**
     * @test
     * Record decision reject flow
     */
    public function test_record_decision_reject_flow(): void
    {
        $request = new ReviewDecisionRequest(
            duplicateId: 2,
            decision: 'REJECTED',
            reason: 'False positive - different amounts'
        );

        $this->adminReviewService->recordReviewDecision($request, 'admin@example.com');

        $this->assertTrue($this->duplicateReviewService->rejectCalled);
        $this->assertEquals(2, $this->duplicateReviewService->lastRejectedId);
    }

    /**
     * @test
     * Record decision investigate flow
     */
    public function test_record_decision_investigate_flow(): void
    {
        $request = new ReviewDecisionRequest(
            duplicateId: 3,
            decision: 'INVESTIGATE',
            reason: 'Need to verify date discrepancy'
        );

        $this->adminReviewService->recordReviewDecision($request, 'admin@example.com');

        $this->assertTrue($this->duplicateReviewService->investigateCalled);
        $this->assertEquals(3, $this->duplicateReviewService->lastInvestigatedId);
    }

    /**
     * @test
     * Record decision fails for already-reviewed transaction
     */
    public function test_record_decision_fails_for_reviewed_transaction(): void
    {
        // Manually mark transaction as already reviewed
        $this->testRepository->markAsReviewed(1);

        $request = new ReviewDecisionRequest(
            duplicateId: 1,
            decision: 'APPROVED'
        );

        $this->expectException(DuplicateReviewException::class);
        $this->adminReviewService->recordReviewDecision($request, 'admin@example.com');
    }

    /**
     * @test
     * Controller list endpoint returns JSON array
     */
    public function test_controller_list_endpoint_returns_json(): void
    {
        $_GET = ['page' => '1', 'per_page' => '25'];

        $this->controller->listDuplicates();

        $this->assertTrue($this->testResponseHandler->jsonSent);
        $this->assertStringContainsString('items', $this->testResponseHandler->lastJson);
        $this->assertStringContainsString('total', $this->testResponseHandler->lastJson);
    }

    /**
     * @test
     * Controller get endpoint returns duplicate details
     */
    public function test_controller_get_endpoint_returns_details(): void
    {
        $this->controller->getDuplicate(1);

        $this->assertTrue($this->testResponseHandler->jsonSent);
        $this->assertStringContainsString('TXN-2026-001', $this->testResponseHandler->lastJson);
    }

    /**
     * @test
     * Controller get endpoint returns 404 for missing transaction
     */
    public function test_controller_get_endpoint_returns_404(): void
    {
        $this->controller->getDuplicate(999);

        $this->assertTrue($this->testResponseHandler->errorSent);
        $this->assertEquals(404, $this->testResponseHandler->lastErrorCode);
    }

    /**
     * @test
     * Controller record decision endpoint processes decision
     */
    public function test_controller_record_decision_endpoint(): void
    {
        $_POST = [
            'duplicate_id' => '1',
            'decision' => 'APPROVED',
            'reason' => 'Clear match'
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        $this->assertTrue($this->testResponseHandler->successSent);
    }

    /**
     * @test
     * Full workflow: list -> detail -> decide
     */
    public function test_full_workflow_list_detail_decide(): void
    {
        // Step 1: List pending duplicates
        $_GET = ['page' => '1', 'per_page' => '25'];
        $this->controller->listDuplicates();

        $this->assertTrue($this->testResponseHandler->jsonSent);
        $data = json_decode($this->testResponseHandler->lastJson, true);
        $this->assertNotEmpty($data['items']);

        $firstDuplicateId = $data['items'][0]['id'];

        // Step 2: Get details of first duplicate
        $this->controller->getDuplicate($firstDuplicateId);

        $this->assertTrue($this->testResponseHandler->jsonSent);
        $detailData = json_decode($this->testResponseHandler->lastJson, true);
        $this->assertEquals($firstDuplicateId, $detailData['id']);

        // Step 3: Make decision
        $_POST = [
            'duplicate_id' => (string)$firstDuplicateId,
            'decision' => 'APPROVED',
            'reason' => 'Verified as duplicate'
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        $this->assertTrue($this->testResponseHandler->successSent);
        $this->assertTrue($this->duplicateReviewService->approveCalled);
        $this->assertEquals($firstDuplicateId, $this->duplicateReviewService->lastApprovedId);
    }

    /**
     * @test
     * Multiple decisions on different transactions
     */
    public function test_multiple_decisions_on_different_transactions(): void
    {
        // Decision 1: Approve TXN-001
        $_POST = ['duplicate_id' => '1', 'decision' => 'APPROVED'];
        $GLOBALS['current_user'] = 'user1@example.com';
        $this->controller->recordDecision();

        $this->assertTrue($this->duplicateReviewService->approveCalled);
        $this->assertEquals(1, $this->duplicateReviewService->lastApprovedId);

        // Reset handler
        $this->testResponseHandler->successSent = false;

        // Decision 2: Reject TXN-002
        $_POST = ['duplicate_id' => '2', 'decision' => 'REJECTED', 'reason' => 'False positive'];
        $GLOBALS['current_user'] = 'user2@example.com';
        $this->controller->recordDecision();

        $this->assertTrue($this->duplicateReviewService->rejectCalled);
        $this->assertEquals(2, $this->duplicateReviewService->lastRejectedId);

        // Reset handler
        $this->testResponseHandler->successSent = false;

        // Decision 3: Investigate TXN-003
        $_POST = ['duplicate_id' => '3', 'decision' => 'INVESTIGATE', 'reason' => 'Need verification'];
        $GLOBALS['current_user'] = 'user1@example.com';
        $this->controller->recordDecision();

        $this->assertTrue($this->duplicateReviewService->investigateCalled);
        $this->assertEquals(3, $this->duplicateReviewService->lastInvestigatedId);
    }

    /**
     * @test
     * Filter and pagination combination
     */
    public function test_filter_with_pagination(): void
    {
        $filters = new QueryFilters(
            page: 1,
            perPage: 2,
            status: 'PENDING',
            minConfidence: 75
        );

        $result = $this->adminReviewService->queryPendingDuplicates($filters);

        // Should return high-confidence duplicates only, paginated
        $this->assertLessThanOrEqual(2, count($result['items']));
        foreach ($result['items'] as $item) {
            $this->assertGreaterThanOrEqual(75, $item->confidenceScore);
        }
    }

    /**
     * @test
     * Error handling: invalid decision type
     */
    public function test_error_handling_invalid_decision(): void
    {
        $_POST = [
            'duplicate_id' => '1',
            'decision' => 'INVALID_DECISION'
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        // Should return error response
        $this->assertTrue($this->testResponseHandler->errorSent);
        $this->assertEquals(400, $this->testResponseHandler->lastErrorCode);
    }

    /**
     * @test
     * Error handling: missing required fields
     */
    public function test_error_handling_missing_fields(): void
    {
        $_POST = [
            'duplicate_id' => '1'
            // Missing 'decision' field
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        $this->assertTrue($this->testResponseHandler->errorSent);
        $this->assertEquals(400, $this->testResponseHandler->lastErrorCode);
    }

    /**
     * @test
     * Audit trail: verify logging of all decisions
     */
    public function test_audit_trail_all_decisions_logged(): void
    {
        $decisions = [
            (new ReviewDecisionRequest(1, 'APPROVED', 'Clear match')),
            (new ReviewDecisionRequest(2, 'REJECTED', 'False positive')),
            (new ReviewDecisionRequest(3, 'INVESTIGATE', 'Needs review'))
        ];

        foreach ($decisions as $decision) {
            $this->adminReviewService->recordReviewDecision($decision, 'admin@example.com');
        }

        // Verify all decisions logged
        $this->assertCount(3, $this->testLogger->infoCalls);
        foreach ($this->testLogger->infoCalls as $call) {
            $this->assertStringContainsString('Decision recorded', $call);
        }
    }
}

// ============================================
// Test Doubles
// ============================================

class TestDuplicateTransactionRepository
{
    private $transactions = [];
    private $nextId = 1;

    public function addTransaction(DuplicateTransaction $transaction): void
    {
        $this->transactions[$transaction->id] = $transaction;
    }

    public function findPendingWithFilters(QueryFilters $filters): array
    {
        $results = array_filter($this->transactions, function($tx) use ($filters) {
            if ($tx->decisionStatus !== 'PENDING') {
                return false;
            }

            if ($filters->startDate && $tx->transDate < $filters->startDate) {
                return false;
            }

            if ($filters->endDate && $tx->transDate > $filters->endDate) {
                return false;
            }

            if ($filters->minConfidence && $tx->confidenceScore < $filters->minConfidence) {
                return false;
            }

            if ($filters->searchTerm) {
                $search = strtolower($filters->searchTerm);
                $found = false;
                if (stripos($tx->transactionCode, $search) !== false) $found = true;
                if (stripos($tx->counterpartyName, $search) !== false) $found = true;
                if (!$found) return false;
            }

            return true;
        });

        return array_values($results);
    }

    public function countPendingWithFilters(QueryFilters $filters): int
    {
        return count($this->findPendingWithFilters($filters));
    }

    public function findById(int $id): ?DuplicateTransaction
    {
        return $this->transactions[$id] ?? null;
    }

    public function markAsReviewed(int $id): void
    {
        if (isset($this->transactions[$id])) {
            $tx = $this->transactions[$id];
            $this->transactions[$id] = new DuplicateTransaction(
                id: $tx->id,
                transactionCode: $tx->transactionCode,
                transDate: $tx->transDate,
                amount: $tx->amount,
                counterpartyName: $tx->counterpartyName,
                matchReason: $tx->matchReason,
                confidenceScore: $tx->confidenceScore,
                decisionStatus: 'APPROVED',
                createdAt: $tx->createdAt,
                decidedBy: 'admin',
                decidedAt: date('Y-m-d H:i:s'),
                reason: 'Processed'
            );
        }
    }
}

class TestDuplicateReviewService
{
    public $approveCalled = false;
    public $rejectCalled = false;
    public $investigateCalled = false;
    public $lastApprovedId = null;
    public $lastRejectedId = null;
    public $lastInvestigatedId = null;

    public function approve($duplicateId, $decidedBy): void
    {
        $this->approveCalled = true;
        $this->lastApprovedId = $duplicateId;
    }

    public function reject($duplicateId, $reason, $decidedBy): void
    {
        $this->rejectCalled = true;
        $this->lastRejectedId = $duplicateId;
    }

    public function investigate($duplicateId, $notes, $decidedBy): void
    {
        $this->investigateCalled = true;
        $this->lastInvestigatedId = $duplicateId;
    }
}

class TestResponseHandler
{
    public $jsonSent = false;
    public $successSent = false;
    public $errorSent = false;
    public $lastJson = null;
    public $lastErrorCode = null;
    public $lastErrorMessage = null;

    public function jsonResponse($data, $statusCode = 200): void
    {
        $this->jsonSent = true;
        $this->lastJson = json_encode($data);
    }

    public function successResponse($message = 'Success', $data = null): void
    {
        $this->successSent = true;
    }

    public function errorResponse($message, $statusCode = 400): void
    {
        $this->errorSent = true;
        $this->lastErrorCode = $statusCode;
        $this->lastErrorMessage = $message;
    }
}

class TestLogger
{
    public $infoCalls = [];

    public function info($message, array $context = []): void
    {
        $this->infoCalls[] = $message;
    }

    public function error($message, array $context = []): void
    {
    }

    public function warning($message, array $context = []): void
    {
    }

    public function debug($message, array $context = []): void
    {
    }
}
