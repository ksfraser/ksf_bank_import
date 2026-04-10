<?php
namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Review\AdminReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\DuplicateReviewDisplay;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\QueryFilters;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\ReviewDecisionRequest;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Import\Exceptions\DuplicateReviewException;  
use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;
use DateTime;

/**
 * Test AdminReviewService: handles duplicate review operations for admin dashboard.
 *
 * Responsibilities:
 * - Query pending duplicates with filtering and pagination
 * - Convert domain models to display DTOs
 * - Record admin review decisions via DuplicateReviewService
 */
class AdminReviewServiceTest extends TestCase
{
    private AdminReviewService $service;
    private TestDuplicateTransactionRepository $duplicateRepository;
    private TestDuplicateReviewService $reviewService;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->duplicateRepository = new TestDuplicateTransactionRepository();
        $this->reviewService = new TestDuplicateReviewService();
        $this->logger = new TestLogger();

        $this->service = new AdminReviewService(
            duplicateTransactionRepository: $this->duplicateRepository,
            duplicateReviewService: $this->reviewService,
            logger: $this->logger,
        );
    }

    /**
     * @test
     * queryPendingDuplicates returns paginated list of pending duplicates
     */
    public function test_query_pending_duplicates_returns_paginated_list(): void
    {
        // Setup: repository has pending duplicates
        $duplicate1 = $this->createDuplicate(id: 1, status: 'PENDING', amount: 1000, code: 'TXN001');
        $duplicate2 = $this->createDuplicate(id: 2, status: 'PENDING', amount: 2000, code: 'TXN002');
        $duplicate3 = $this->createDuplicate(id: 3, status: 'PENDING', amount: 3000, code: 'TXN003');

        $this->duplicateRepository->addPendingDuplicate($duplicate1);
        $this->duplicateRepository->addPendingDuplicate($duplicate2);
        $this->duplicateRepository->addPendingDuplicate($duplicate3);

        // Execute: query with pagination
        $filters = QueryFilters::fromArray(['page' => 1, 'per_page' => 2]);
        $result = $this->service->queryPendingDuplicates($filters);

        // Verify: returns paginated results
        $this->assertEquals(2, count($result['items']));
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertInstanceOf(DuplicateReviewDisplay::class, $result['items'][0]);
    }

    /**
     * @test
     * queryPendingDuplicates applies date range filter
     */
    public function test_query_applies_date_range_filter(): void
    {
        $duplicate1 = $this->createDuplicate(id: 1, status: 'PENDING', createdAt: '2026-04-01');
        $duplicate2 = $this->createDuplicate(id: 2, status: 'PENDING', createdAt: '2026-04-15');
        $duplicate3 = $this->createDuplicate(id: 3, status: 'PENDING', createdAt: '2026-05-01');

        $this->duplicateRepository->addPendingDuplicate($duplicate1);
        $this->duplicateRepository->addPendingDuplicate($duplicate2);
        $this->duplicateRepository->addPendingDuplicate($duplicate3);

        // Query with date range filter
        $filters = QueryFilters::fromArray([
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'page' => 1,
            'per_page' => 50,
        ]);

        $result = $this->service->queryPendingDuplicates($filters);

        // Should return only duplicates in date range
        $this->assertEquals(2, count($result['items']));
        $this->assertEquals(2, $result['total']);
    }

    /**
     * @test
     * queryPendingDuplicates applies amount range filter
     */
    public function test_query_applies_amount_range_filter(): void
    {
        $this->duplicateRepository->addPendingDuplicate($this->createDuplicate(id: 1, amount: 500));
        $this->duplicateRepository->addPendingDuplicate($this->createDuplicate(id: 2, amount: 1500));
        $this->duplicateRepository->addPendingDuplicate($this->createDuplicate(id: 3, amount: 2500));

        $filters = QueryFilters::fromArray([
            'min_amount' => 1000,
            'max_amount' => 2000,
        ]);

        $result = $this->service->queryPendingDuplicates($filters);

        $this->assertEquals(1, count($result['items']));
        $this->assertEquals(1500, $result['items'][0]->amount);
    }

    /**
     * @test
     * queryPendingDuplicates applies search term filter (by transaction code)
     */
    public function test_query_applies_search_term_filter(): void
    {
        $this->duplicateRepository->addPendingDuplicate($this->createDuplicate(id: 1, code: 'INV001'));
        $this->duplicateRepository->addPendingDuplicate($this->createDuplicate(id: 2, code: 'TXN002'));
        $this->duplicateRepository->addPendingDuplicate($this->createDuplicate(id: 3, code: 'INV003'));

        $filters = QueryFilters::fromArray(['search_term' => 'INV']);

        $result = $this->service->queryPendingDuplicates($filters);

        $this->assertEquals(2, count($result['items']));
        $this->assertTrue(str_contains($result['items'][0]->transactionCode, 'INV'));
    }

    /**
     * @test
     * recordReviewDecision calls DuplicateReviewService with correct decision
     */
    public function test_record_review_decision_approves_duplicate(): void
    {
        $duplicate = $this->createDuplicate(id: 42, status: 'PENDING');
        $this->duplicateRepository->setFindByIdResult($duplicate);

        $request = ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
            'decision' => 'APPROVED',
            'reason' => 'Matches our records',
        ]);

        $this->service->recordReviewDecision($request, decidedBy: 'admin@example.com');

        // Verify: reviewService.approve was called
        $this->assertTrue($this->reviewService->approveWasCalled);
        $this->assertEquals(42, $this->reviewService->lastDuplicateId);
        $this->assertEquals('Matches our records', $this->reviewService->lastReason);
        $this->assertEquals('admin@example.com', $this->reviewService->lastDecidedBy);
    }

    /**
     * @test
     * recordReviewDecision rejects duplicate with reason
     */
    public function test_record_review_decision_rejects_duplicate(): void
    {
        $duplicate = $this->createDuplicate(id: 42, status: 'PENDING');
        $this->duplicateRepository->setFindByIdResult($duplicate);

        $request = ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
            'decision' => 'REJECTED',
            'reason' => 'Amount mismatch',
        ]);

        $this->service->recordReviewDecision($request, decidedBy: 'admin@example.com');

        // Verify: reviewService.reject was called
        $this->assertTrue($this->reviewService->rejectWasCalled);
        $this->assertEquals('Amount mismatch', $this->reviewService->lastReason);
    }

    /**
     * @test
     * recordReviewDecision marks for investigation
     */
    public function test_record_review_decision_marks_for_investigation(): void
    {
        $duplicate = $this->createDuplicate(id: 42, status: 'PENDING');
        $this->duplicateRepository->setFindByIdResult($duplicate);

        $request = ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
            'decision' => 'INVESTIGATE',
            'reason' => 'Need manual review',
        ]);

        $this->service->recordReviewDecision($request, decidedBy: 'admin@example.com');

        // Verify: reviewService.investigate was called
        $this->assertTrue($this->reviewService->investigateWasCalled);
    }

    /**
     * @test
     * recordReviewDecision throws if duplicate not found
     */
    public function test_record_review_decision_throws_if_duplicate_not_found(): void
    {
        $this->duplicateRepository->setFindByIdResult(null);

        $request = ReviewDecisionRequest::fromArray([
            'duplicate_id' => 999,
            'decision' => 'APPROVED',
        ]);

        $this->expectException(EntityNotFoundException::class);
        $this->service->recordReviewDecision($request, decidedBy: 'admin@example.com');
    }

    /**
     * @test
     * recordReviewDecision throws if duplicate not in PENDING status
     */
    public function test_record_review_decision_throws_if_not_pending(): void
    {
        $duplicate = $this->createDuplicate(id: 42, status: 'APPROVED');
        $this->duplicateRepository->setFindByIdResult($duplicate);

        $request = ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
            'decision' => 'REJECTED',
        ]);

        $this->expectException(DuplicateReviewException::class);
        $this->service->recordReviewDecision($request, decidedBy: 'admin@example.com');
    }

    /**
     * @test
     * getDuplicateDetails returns full display DTO for a duplicate
     */
    public function test_get_duplicate_details_returns_display_dto(): void
    {
        $duplicate = $this->createDuplicate(id: 42, code: 'TXN123', amount: 1500.50);
        $this->duplicateRepository->setFindByIdResult($duplicate);

        $dto = $this->service->getDuplicateDetails(42);

        $this->assertInstanceOf(DuplicateReviewDisplay::class, $dto);
        $this->assertEquals(42, $dto->id);
        $this->assertEquals('TXN123', $dto->transactionCode);
        $this->assertEquals(1500.50, $dto->amount);
    }

    /**
     * @test
     * getDuplicateDetails throws if duplicate not found
     */
    public function test_get_duplicate_details_throws_if_not_found(): void
    {
        $this->duplicateRepository->setFindByIdResult(null);

        $this->expectException(EntityNotFoundException::class);
        $this->service->getDuplicateDetails(999);
    }

    /**
     * @test
     * logs review decision recording
     */
    public function test_logs_review_decision_recording(): void
    {
        $duplicate = $this->createDuplicate(id: 42);
        $this->duplicateRepository->setFindByIdResult($duplicate);

        $request = ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
            'decision' => 'APPROVED',
        ]);

        $this->service->recordReviewDecision($request, decidedBy: 'admin@example.com');

        $this->assertTrue($this->logger->infoWasLogged);
    }

    // Helper methods

    private function createDuplicate(
        int $id = 1,
        string $status = 'PENDING',
        float $amount = 1000.00,
        string $code = 'TXN001',
        string $createdAt = '2026-04-08'
    ) {
        // Create a mock object that mimics DuplicateTransaction structure
        $obj = new \stdClass();
        $obj->duplicate_id = $id;
        $obj->transaction_code = $code;
        $obj->amount = $amount;
        $obj->transaction_date = $createdAt;
        $obj->decision_status = $status;
        $obj->decided_by = null;
        $obj->decided_at = null;
        $obj->reason = null;
        $obj->confidence_score = 85.0;
        $obj->matched_transaction_count = 1;
        $obj->created_at = $createdAt;
        $obj->updated_at = $createdAt;
        // Add property accessors for both snake_case (for domain object) and camelCase
        $obj->id = $id;
        return $obj;
    }
}

// Test doubles

class TestDuplicateTransactionRepository
{
    private array $pendingDuplicates = [];
    private $findByIdResult = null;

    public function addPendingDuplicate($duplicate): void
    {
        $this->pendingDuplicates[] = $duplicate;
    }

    public function setFindByIdResult($result): void
    {
        $this->findByIdResult = $result;
    }

    public function findPendingWithFilters(QueryFilters $filters): array
    {
        $filtered = array_filter($this->pendingDuplicates, function (DuplicateTransaction $d) use ($filters) {
            if ($d->decision_status !== 'PENDING') {
                return false;
            }

            if ($filters->hasDateRange()) {
                $date = $d->created_at;
                if ($filters->startDate && $date < $filters->startDate) {
                    return false;
                }
                if ($filters->endDate && $date > $filters->endDate) {
                    return false;
                }
            }

            if ($filters->hasAmountRange()) {
                if ($filters->minAmount && $d->amount < $filters->minAmount) {
                    return false;
                }
                if ($filters->maxAmount && $d->amount > $filters->maxAmount) {
                    return false;
                }
            }

            if ($filters->searchTerm && strpos($d->transaction_code, $filters->searchTerm) === false) {
                return false;
            }

            return true;
        });

        return array_slice($filtered, $filters->calculateOffset(), $filters->perPage);
    }

    public function countPendingWithFilters(QueryFilters $filters): int
    {
        return count(array_filter($this->pendingDuplicates, function (DuplicateTransaction $d) use ($filters) {
            if ($d->decision_status !== 'PENDING') {
                return false;
            }

            if ($filters->hasDateRange()) {
                $date = $d->created_at;
                if ($filters->startDate && $date < $filters->startDate) {
                    return false;
                }
                if ($filters->endDate && $date > $filters->endDate) {
                    return false;
                }
            }

            if ($filters->hasAmountRange()) {
                if ($filters->minAmount && $d->amount < $filters->minAmount) {
                    return false;
                }
                if ($filters->maxAmount && $d->amount > $filters->maxAmount) {
                    return false;
                }
            }

            if ($filters->searchTerm && strpos($d->transaction_code, $filters->searchTerm) === false) {
                return false;
            }

            return true;
        }));
    }

    public function findById(int $id): DuplicateTransaction
    {
        return $this->findByIdResult ?? throw new EntityNotFoundException(sprintf('Duplicate %d not found', $id));
    }
}

class TestDuplicateReviewService
{
    public bool $approveWasCalled = false;
    public bool $rejectWasCalled = false;
    public bool $investigateWasCalled = false;
    public int $lastDuplicateId = 0;
    public ?string $lastReason = null;
    public ?string $lastDecidedBy = null;

    public function approve(int $duplicateId, string $reason, string $decidedBy): void
    {
        $this->approveWasCalled = true;
        $this->lastDuplicateId = $duplicateId;
        $this->lastReason = $reason;
        $this->lastDecidedBy = $decidedBy;
    }

    public function reject(int $duplicateId, string $reason, string $decidedBy): void
    {
        $this->rejectWasCalled = true;
        $this->lastDuplicateId = $duplicateId;
        $this->lastReason = $reason;
        $this->lastDecidedBy = $decidedBy;
    }

    public function investigate(int $duplicateId, string $reason, string $decidedBy): void
    {
        $this->investigateWasCalled = true;
        $this->lastDuplicateId = $duplicateId;
        $this->lastReason = $reason;
        $this->lastDecidedBy = $decidedBy;
    }
}

class TestLogger
{
    public bool $infoWasLogged = false;

    public function info($message, array $context = []): void
    {
        $this->infoWasLogged = true;
    }
}
