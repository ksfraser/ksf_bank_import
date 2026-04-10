<?php
namespace Ksfraser\FaBankImport\Import\Services\Review;

use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Exceptions\DuplicateReviewException;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\DuplicateReviewDisplay;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\QueryFilters;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\ReviewDecisionRequest;
use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;

/**
 * AdminReviewService: handles duplicate review operations for admin dashboard.
 *
 * Responsibilities:
 * - Query pending duplicates with filtering and pagination
 * - Convert domain models to display DTOs
 * - Record admin review decisions via DuplicateReviewService
 *
 * @package Ksfraser\FaBankImport\Import\Services\Review
 */
final class AdminReviewService
{
    public function __construct(
        private mixed $duplicateTransactionRepository, // Accepts any object with required methods
        private mixed $duplicateReviewService, // Accepts DuplicateReviewService or compatible
        private mixed $logger, // Accepts any logger with info() method
    ) {
    }

    /**
     * Query pending duplicates with filtering and pagination
     *
     * @param QueryFilters $filters Filtering and pagination criteria
     * @return array{items: DuplicateReviewDisplay[], total: int, page: int, per_page: int}
     */
    public function queryPendingDuplicates(QueryFilters $filters): array
    {
        // Get paginated filtered duplicates
        $duplicates = $this->duplicateTransactionRepository->findPendingWithFilters($filters);
        $total = $this->duplicateTransactionRepository->countPendingWithFilters($filters);

        // Convert to display DTOs
        $items = array_map(
            fn (DuplicateTransaction $dup) => $this->domainToDisplay($dup),
            $duplicates
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'per_page' => $filters->perPage,
        ];
    }

    /**
     * Get full details of a specific duplicate
     *
     * @param int $duplicateId
     * @return DuplicateReviewDisplay
     * @throws EntityNotFoundException
     */
    public function getDuplicateDetails(int $duplicateId): DuplicateReviewDisplay
    {
        try {
            $duplicate = $this->duplicateTransactionRepository->findById($duplicateId);
        } catch (\Exception $e) {
            throw EntityNotFoundException::transactionNotFound($duplicateId);
        }

        return $this->domainToDisplay($duplicate);
    }

    /**
     * Record a review decision (approve/reject/investigate)
     *
     * @param ReviewDecisionRequest $request Decision and reason
     * @param string $decidedBy User email/identification
     * @throws EntityNotFoundException If duplicate not found
     * @throws DuplicateReviewException If duplicate not in PENDING status
     */
    public function recordReviewDecision(ReviewDecisionRequest $request, string $decidedBy): void
    {
        // Load duplicate - interface declares findById throws if not found
        try {
            $duplicate = $this->duplicateTransactionRepository->findById($request->duplicateId);
        } catch (\Exception $e) {
            throw EntityNotFoundException::transactionNotFound($request->duplicateId);
        }

        // Verify it's in PENDING status
        if ($duplicate->decision_status !== 'PENDING') {
            throw new DuplicateReviewException(
                sprintf('Duplicate %d is not in PENDING status (current: %s)',
                    $request->duplicateId,
                    $duplicate->decision_status
                )
            );
        }

        // Route to appropriate decision method
        match ($request->decision) {
            'APPROVED' => $this->duplicateReviewService->approve(
                $request->duplicateId,
                $request->reason ?? '',
                $decidedBy
            ),
            'REJECTED' => $this->duplicateReviewService->reject(
                $request->duplicateId,
                $request->reason ?? '',
                $decidedBy
            ),
            'INVESTIGATE' => $this->duplicateReviewService->investigate(
                $request->duplicateId,
                $request->reason ?? '',
                $decidedBy
            ),
        };

        // Log the decision
        $this->logger->info(
            sprintf(
                'Duplicate review decision recorded: ID=%d, Decision=%s, By=%s',
                $request->duplicateId,
                $request->decision,
                $decidedBy
            )
        );
    }

    /**
     * Convert DuplicateTransaction domain model to display DTO
     */
    private function domainToDisplay(DuplicateTransaction $duplicate): DuplicateReviewDisplay
    {
        return DuplicateReviewDisplay::fromArray([
            'id' => $duplicate->id,
            'transaction_code' => $duplicate->transaction_code,
            'amount' => $duplicate->amount,
            'trans_date' => $duplicate->transaction_date,
            'decision_status' => $duplicate->decision_status,
            'decided_by' => $duplicate->decided_by,
            'decided_at' => $duplicate->decided_at,
            'reason' => $duplicate->reason,
            'confidence_score' => $duplicate->confidence_score,
            'matched_transaction_count' => $duplicate->matched_transaction_count,
            'created_at' => $duplicate->created_at,
        ]);
    }
}
