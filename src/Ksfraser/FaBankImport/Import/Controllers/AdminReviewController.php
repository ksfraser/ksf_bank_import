<?php
namespace Ksfraser\FaBankImport\Import\Controllers;

use Ksfraser\FaBankImport\Import\Services\Review\AdminReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\QueryFilters;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\ReviewDecisionRequest;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Import\Exceptions\DuplicateReviewException;

/**
 * AdminReviewController: API endpoints for admin review dashboard.
 *
 * Endpoints:
 * - GET /api/duplicates - List pending duplicates with filtering
 * - GET /api/duplicates/{id} - Get single duplicate details
 * - POST /api/duplicates/{id}/decide - Record review decision
 *
 * @package Ksfraser\FaBankImport\Import\Controllers
 */
class AdminReviewController
{
    public function __construct(
        private $adminService,
        private $responseHandler,
    ) {
    }

    /**
     * List pending duplicates with filtering and pagination
     *
     * GET /api/duplicates?page=1&per_page=25&status=PENDING&start_date=...&end_date=...&min_amount=...&max_amount=...
     */
    public function listDuplicates()
    {
        try {
            // Parse filter parameters from query string
            $filters = QueryFilters::fromArray($_GET);

            // Query service
            $result = $this->adminService->queryPendingDuplicates($filters);

            // Return JSON response
            $this->responseHandler->jsonResponse($result, 200);
        } catch (\InvalidArgumentException $e) {
            $this->responseHandler->errorResponse(
                sprintf('Invalid filter parameters: %s', $e->getMessage()),
                400
            );
        } catch (\Exception $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get single duplicate details
     *
     * GET /api/duplicates/{id}
     */
    public function getDuplicate($duplicateId)
    {
        try {
            $duplicate = $this->adminService->getDuplicateDetails($duplicateId);
            $this->responseHandler->jsonResponse($duplicate->toArray(), 200);
        } catch (EntityNotFoundException $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Record review decision for a duplicate
     *
     * POST /api/duplicates/{id}/decide
     * Body: { "decision": "APPROVED|REJECTED|INVESTIGATE", "reason": "..." }
     */
    public function recordDecision()
    {
        try {
            // Parse request body
            if (empty($_POST['duplicate_id']) || empty($_POST['decision'])) {
                throw new \InvalidArgumentException('Missing required fields: duplicate_id, decision');
            }

            // Build review decision request
            $request = ReviewDecisionRequest::fromArray($_POST);

            // Get current user
            $currentUser = $GLOBALS['current_user'] ?? 'UNKNOWN';

            // Call service
            $this->adminService->recordReviewDecision($request, $currentUser);

            // Return success
            $this->responseHandler->successResponse('Review decision recorded successfully');
        } catch (\InvalidArgumentException $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 400);
        } catch (EntityNotFoundException $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 404);
        } catch (DuplicateReviewException $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            $this->responseHandler->errorResponse($e->getMessage(), 500);
        }
    }
}

/**
 * Response handler interface for JSON APIs
 */
interface IResponseHandler
{
    /**
     * Send JSON response
     *
     * @param array $data
     * @param int $statusCode
     */
    public function jsonResponse($data, $statusCode = 200);

    /**
     * Send success response
     *
     * @param string $message
     * @param array $data
     */
    public function successResponse($message = 'Success', $data = null);

    /**
     * Send error response
     *
     * @param string $message
     * @param int $statusCode
     */
    public function errorResponse($message, $statusCode = 400);
}
