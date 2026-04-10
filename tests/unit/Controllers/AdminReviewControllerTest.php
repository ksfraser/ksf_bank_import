<?php
namespace Ksfraser\FaBankImport\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Controllers\AdminReviewController;
use Ksfraser\FaBankImport\Import\Services\Review\AdminReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\DuplicateReviewDisplay;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;

/**
 * Test AdminReviewController: API endpoints for admin review dashboard.
 *
 * Responsibilities:
 * - Validate and parse HTTP requests
 * - Call AdminReviewService
 * - Return JSON responses
 */
class AdminReviewControllerTest extends TestCase
{
    private AdminReviewController $controller;
    private TestAdminReviewService $adminService;
    private TestResponseHandler $responseHandler;

    protected function setUp(): void
    {
        $this->adminService = new TestAdminReviewService();
        $this->responseHandler = new TestResponseHandler();
        $this->controller = new AdminReviewController($this->adminService, $this->responseHandler);
    }

    /**
     * @test
     * listDuplicates returns JSON array of pending duplicates
     */
    public function test_list_duplicates_returns_json_array(): void
    {
        // Setup: service has pending duplicates
        $display1 = $this->createDisplayDTO(1, 'TXN001', 1000);
        $display2 = $this->createDisplayDTO(2, 'TXN002', 2000);
        $this->adminService->setListResult([
            'items' => [$display1, $display2],
            'total' => 2,
            'page' => 1,
            'per_page' => 25,
        ]);

        // Mock request parameters
        $_GET = ['page' => '1', 'per_page' => '25'];

        // Execute: call listDuplicates
        $this->controller->listDuplicates();

        // Verify: response sent with JSON
        $this->assertTrue($this->responseHandler->jsonSent);
        $this->assertStringContainsString('items', $this->responseHandler->lastJson);
        $this->assertStringContainsString('total', $this->responseHandler->lastJson);
    }

    /**
     * @test
     * listDuplicates applies filters from query parameters
     */
    public function test_list_duplicates_applies_filters(): void
    {
        $display = $this->createDisplayDTO(1, 'TXN001', 1000);
        $this->adminService->setListResult([
            'items' => [$display],
            'total' => 1,
            'page' => 2,
            'per_page' => 50,
        ]);

        $_GET = [
            'page' => '2',
            'per_page' => '50',
            'status' => 'PENDING',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'min_amount' => '100',
            'max_amount' => '5000',
        ];

        $this->controller->listDuplicates();

        // Verify: QueryFilters was built with parameters
        $this->assertEquals(2, $this->adminService->lastFilters->page);
        $this->assertEquals(50, $this->adminService->lastFilters->perPage);
        $this->assertEquals('PENDING', $this->adminService->lastFilters->status);
    }

    /**
     * @test
     * getDuplicate returns single duplicate details
     */
    public function test_get_duplicate_returns_single_display(): void
    {
        $display = $this->createDisplayDTO(42, 'TXN999', 5000);
        $this->adminService->setDetailResult($display);

        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/api/duplicates/42';

        $this->controller->getDuplicate(42);

        $this->assertTrue($this->responseHandler->jsonSent);
        $this->assertStringContainsString('id', $this->responseHandler->lastJson);
    }

    /**
     * @test
     * getDuplicate returns 404 if not found
     */
    public function test_get_duplicate_returns_404_if_not_found(): void
    {
        $this->adminService->setShouldThrow(
            new EntityNotFoundException(sprintf('Duplicate %d not found', 999))
        );

        $this->controller->getDuplicate(999);

        $this->assertTrue($this->responseHandler->errorSent);
        $this->assertEquals(404, $this->responseHandler->lastErrorCode);
    }

    /**
     * @test
     * recordDecision accepts POST request with decision data
     */
    public function test_record_decision_accepts_post_request(): void
    {
        $_POST = [
            'duplicate_id' => '42',
            'decision' => 'APPROVED',
            'reason' => 'Matches our records',
        ];

        // Mock current user
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        // Verify: recordReviewDecision was called
        $this->assertTrue($this->adminService->recordDecisionWasCalled);
        $this->assertEquals(42, $this->adminService->lastDecisionRequest->duplicateId);
        $this->assertEquals('APPROVED', $this->adminService->lastDecisionRequest->decision);
    }

    /**
     * @test
     * recordDecision returns 200 on success
     */
    public function test_record_decision_returns_success(): void
    {
        $_POST = [
            'duplicate_id' => '42',
            'decision' => 'APPROVED',
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        $this->assertTrue($this->responseHandler->successSent);
    }

    /**
     * @test
     * recordDecision returns error on invalid request
     */
    public function test_record_decision_returns_error_on_invalid_request(): void
    {
        $_POST = [
            'duplicate_id' => '42',
            // Missing 'decision' field
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->controller->recordDecision();

        $this->assertTrue($this->responseHandler->errorSent);
    }

    /**
     * @test
     * recordDecision returns 404 if duplicate not found
     */
    public function test_record_decision_returns_404_if_duplicate_not_found(): void
    {
        $_POST = [
            'duplicate_id' => '999',
            'decision' => 'APPROVED',
        ];
        $GLOBALS['current_user'] = 'admin@example.com';

        $this->adminService->setShouldThrow(
            new EntityNotFoundException(sprintf('Duplicate %d not found', 999))
        );

        $this->controller->recordDecision();

        $this->assertTrue($this->responseHandler->errorSent);
        $this->assertEquals(404, $this->responseHandler->lastErrorCode);
    }

    // Helper methods

    private function createDisplayDTO($id, $code, $amount)
    {
        // Create mock object with toArray() method for testing
        return new class($id, $code, $amount) {
            public $id;
            public $transactionCode;
            public $amount;
            public $transDate = '2026-04-08';
            public $decisionStatus = 'PENDING';
            public $decidedBy = null;
            public $decidedAt = null;
            public $reason = null;
            public $confidenceScore = 85.0;
            public $matchedTransactionCount = 1;
            public $createdAt = '2026-04-08';
            
            public function __construct($id, $code, $amount)
            {
                $this->id = $id;
                $this->transactionCode = $code;
                $this->amount = $amount;
            }
            
            public function toArray()
            {
                return [
                    'id' => $this->id,
                    'transactionCode' => $this->transactionCode,
                    'amount' => $this->amount,
                    'transDate' => $this->transDate,
                    'decisionStatus' => $this->decisionStatus,
                    'decidedBy' => $this->decidedBy,
                    'decidedAt' => $this->decidedAt,
                    'reason' => $this->reason,
                    'confidenceScore' => $this->confidenceScore,
                    'matchedTransactionCount' => $this->matchedTransactionCount,
                    'createdAt' => $this->createdAt,
                ];
            }
        };
    }
}

// Test Doubles

class TestAdminReviewService
{
    private $listResult = null;
    private $detailResult = null;
    private $shouldThrow = null;
    public $recordDecisionWasCalled = false;
    public $lastDecisionRequest = null;
    public $lastFilters = null;

    public function setListResult($result)
    {
        $this->listResult = $result;
    }

    public function setDetailResult($result)
    {
        $this->detailResult = $result;
    }

    public function setShouldThrow($exception)
    {
        $this->shouldThrow = $exception;
    }

    public function queryPendingDuplicates($filters)
    {
        if ($this->shouldThrow) {
            throw $this->shouldThrow;
        }
        $this->lastFilters = $filters;
        return $this->listResult;
    }

    public function getDuplicateDetails($id)
    {
        if ($this->shouldThrow) {
            throw $this->shouldThrow;
        }
        return $this->detailResult;
    }

    public function recordReviewDecision($request, $decidedBy)
    {
        if ($this->shouldThrow) {
            throw $this->shouldThrow;
        }
        $this->recordDecisionWasCalled = true;
        $this->lastDecisionRequest = $request;
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

    public function jsonResponse($data, $statusCode = 200)
    {
        $this->jsonSent = true;
        $this->lastJson = json_encode($data);
    }

    public function successResponse($message = 'Success', $data = null)
    {
        $this->successSent = true;
    }

    public function errorResponse($message, $statusCode = 400)
    {
        $this->errorSent = true;
        $this->lastErrorCode = $statusCode;
        $this->lastErrorMessage = $message;
    }
}
