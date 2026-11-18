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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
final class AdminReviewService
{
    /** @var object */
    private $duplicateTransactionRepository;
    /** @var object */
    private $duplicateReviewService;
    /** @var object */
    private $logger;

    /**
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function __construct(
        $duplicateTransactionRepository, // Accepts any object with required methods
        $duplicateReviewService, // Accepts DuplicateReviewService or compatible
        $logger // Accepts any logger with info() method
    ) {
        $this->duplicateTransactionRepository = $duplicateTransactionRepository;
        $this->duplicateReviewService = $duplicateReviewService;
        $this->logger = $logger;
    }

    /**
     * Query pending duplicates with filtering and pagination
     *
     * @param QueryFilters $filters Filtering and pagination criteria
     * @return array{items: DuplicateReviewDisplay[], total: int, page: int, per_page: int}
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function queryPendingDuplicates(QueryFilters $filters): array
    {
        // Get paginated filtered duplicates
        $duplicates = $this->duplicateTransactionRepository->findPendingWithFilters($filters);
        $total = $this->duplicateTransactionRepository->countPendingWithFilters($filters);

        // Convert to display DTOs
        $items = array_map(
            function ($dup) { return $this->domainToDisplay($dup); },
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function getDuplicateDetails(int $duplicateId): DuplicateReviewDisplay
    {
        try {
            $duplicate = $this->duplicateTransactionRepository->findById($duplicateId);
        } catch (\Exception $e) {
            throw EntityNotFoundException::transactionNotFound($duplicateId);
        }

        if ($duplicate === null) {
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function recordReviewDecision(ReviewDecisionRequest $request, string $decidedBy): void
    {
        // Load duplicate - interface declares findById throws if not found
        try {
            $duplicate = $this->duplicateTransactionRepository->findById($request->duplicateId);
        } catch (\Exception $e) {
            throw EntityNotFoundException::transactionNotFound($request->duplicateId);
        }

        if ($duplicate === null) {
            throw EntityNotFoundException::transactionNotFound($request->duplicateId);
        }

        // Verify it's in PENDING status
        if ($duplicate->getDecisionStatus() !== 'PENDING') {
            throw new DuplicateReviewException(
                sprintf('Duplicate %d is not in PENDING status (current: %s)',
                    $request->duplicateId,
                    $duplicate->getDecisionStatus()
                )
            );
        }

        // Route to appropriate decision method
        switch ($request->decision) {
            case 'APPROVED':
                $this->duplicateReviewService->approve(
                    $request->duplicateId,
                    $request->reason ?? '',
                    $decidedBy
                );
                break;
            case 'REJECTED':
                $this->duplicateReviewService->reject(
                    $request->duplicateId,
                    $request->reason ?? '',
                    $decidedBy
                );
                break;
            case 'INVESTIGATE':
                $this->duplicateReviewService->investigate(
                    $request->duplicateId,
                    $request->reason ?? '',
                    $decidedBy
                );
                break;
        }

        // Log the decision
        $this->logger->info(
            sprintf(
                'Decision recorded: ID=%d, Decision=%s, By=%s',
                $request->duplicateId,
                $request->decision,
                $decidedBy
            )
        );
    }

    /**
     * Convert DuplicateTransaction domain model to display DTO
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    private function domainToDisplay($duplicate): DuplicateReviewDisplay
    {
        $row = $duplicate->toArray();
        $row['id'] = $row['duplicate_id'];
        $row['matched_transaction_count'] = 0;

        return DuplicateReviewDisplay::fromArray($row);
    }
}
