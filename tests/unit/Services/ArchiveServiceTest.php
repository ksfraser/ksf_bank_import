<?php
/**
 * Archive Service Test Suite
 *
 * Tests for the ArchiveService class that manages archiving of REJECTED
 * and INVESTIGATE duplicate transactions.
 *
 * @package Ksfraser\FaBankImport\Tests\Unit\Services
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Archive\ArchiveService;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Import\Exceptions\DuplicateReviewException;

class ArchiveServiceTest extends TestCase
{
    private $archiveService;
    private $testRepository;
    private $testArchiveRepository;
    private $testLogger;

    protected function setUp(): void
    {
        $this->testRepository = new TestDuplicateRepository();
        $this->testArchiveRepository = new ArchiveTestArchiveRepository();
        $this->testLogger = new ArchiveTestLogger();

        $this->archiveService = new ArchiveService(
            $this->testRepository,
            $this->testArchiveRepository,
            $this->testLogger
        );

        // Populate test data
        $this->populateTestData();
    }

    private function populateTestData(): void
    {
        // Add REJECTED transaction
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 1,
            transactionCode: 'TXN-001',
            transDate: '2026-04-01',
            amount: 1000.00,
            counterpartyName: 'Vendor A',
            matchReason: 'Code + Date match',
            confidenceScore: 95,
            decisionStatus: 'REJECTED',
            createdAt: '2026-04-08',
            decidedBy: 'admin@example.com',
            decidedAt: '2026-04-09',
            reason: 'False positive'
        ));

        // Add INVESTIGATE transaction
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 2,
            transactionCode: 'TXN-002',
            transDate: '2026-04-02',
            amount: 2500.50,
            counterpartyName: 'Customer B',
            matchReason: 'Amount + Date match',
            confidenceScore: 78,
            decisionStatus: 'INVESTIGATE',
            createdAt: '2026-04-08',
            decidedBy: 'admin@example.com',
            decidedAt: '2026-04-09',
            reason: null
        ));

        // Add APPROVED transaction (should not be archivable)
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 3,
            transactionCode: 'TXN-003',
            transDate: '2026-04-03',
            amount: 500.00,
            counterpartyName: 'Vendor C',
            matchReason: 'Full match',
            confidenceScore: 100,
            decisionStatus: 'APPROVED',
            createdAt: '2026-04-08',
            decidedBy: 'admin@example.com',
            decidedAt: '2026-04-09',
            reason: null
        ));

        // Add PENDING transaction (should not be archivable)
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 4,
            transactionCode: 'TXN-004',
            transDate: '2026-04-04',
            amount: 1500.00,
            counterpartyName: 'Service D',
            matchReason: 'Code match',
            confidenceScore: 85,
            decisionStatus: 'PENDING',
            createdAt: '2026-04-08',
            decidedBy: null,
            decidedAt: null,
            reason: null
        ));
    }

    /**
     * @test
     * Archive rejected transaction successfully
     */
    public function test_archive_rejected_transaction_successfully(): void
    {
        $this->archiveService->archiveRejected(1, 'False positive mismatch', 'admin@example.com');

        $this->assertTrue($this->testArchiveRepository->archiveCalled);
        $this->assertEquals(1, $this->testArchiveRepository->lastArchivedId);
        $this->assertEquals('REJECTED', $this->testArchiveRepository->lastStatus);
        $this->assertEquals('False positive mismatch', $this->testArchiveRepository->lastReason);
    }

    /**
     * @test
     * Archive rejected transaction logs info
     */
    public function test_archive_rejected_transaction_logs_info(): void
    {
        $this->archiveService->archiveRejected(1, 'False positive', 'curator@example.com');

        $this->assertCount(1, $this->testLogger->infoCalls);
        $this->assertStringContainsString('archived', strtolower($this->testLogger->infoCalls[0]));
        $this->assertStringContainsString('REJECTED', $this->testLogger->infoCalls[0]);
    }

    /**
     * @test
     * Archive for investigation successfully
     */
    public function test_archive_for_investigation_successfully(): void
    {
        $this->archiveService->archiveForInvestigation(2, 'Needs supervisor review', 'admin@example.com');

        $this->assertTrue($this->testArchiveRepository->archiveCalled);
        $this->assertEquals(2, $this->testArchiveRepository->lastArchivedId);
        $this->assertEquals('INVESTIGATE', $this->testArchiveRepository->lastStatus);
        $this->assertEquals('Needs supervisor review', $this->testArchiveRepository->lastReason);
    }

    /**
     * @test
     * Archive for investigation logs info
     */
    public function test_archive_for_investigation_logs_info(): void
    {
        $this->archiveService->archiveForInvestigation(2, 'Supervisor review needed', 'admin@example.com');

        $this->assertCount(1, $this->testLogger->infoCalls);
        $this->assertStringContainsString('investigation', strtolower($this->testLogger->infoCalls[0]));
    }

    /**
     * @test
     * Archive rejected fails for approved transaction
     */
    public function test_archive_rejected_fails_for_approved_transaction(): void
    {
        $this->expectException(DuplicateReviewException::class);
        $this->archiveService->archiveRejected(3, 'Should not archive approved', 'admin@example.com');
    }

    /**
     * @test
     * Archive for investigation fails for approved transaction
     */
    public function test_archive_investigation_fails_for_approved_transaction(): void
    {
        $this->expectException(DuplicateReviewException::class);
        $this->archiveService->archiveForInvestigation(3, 'Should not archive approved', 'admin@example.com');
    }

    /**
     * @test
     * Archive rejected fails for pending transaction
     */
    public function test_archive_rejected_fails_for_pending_transaction(): void
    {
        $this->expectException(DuplicateReviewException::class);
        $this->archiveService->archiveRejected(4, 'Should not archive pending', 'admin@example.com');
    }

    /**
     * @test
     * Archive fails for non-existent transaction
     */
    public function test_archive_fails_for_nonexistent_transaction(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->archiveService->archiveRejected(999, 'Does not exist', 'admin@example.com');
    }

    /**
     * @test
     * Archive for investigation fails for non-existent transaction
     */
    public function test_archive_investigation_fails_for_nonexistent(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->archiveService->archiveForInvestigation(999, 'Does not exist', 'admin@example.com');
    }

    /**
     * @test
     * Get archive stats returns counts
     */
    public function test_get_archive_stats_returns_counts(): void
    {
        // Archive some transactions first
        $this->archiveService->archiveRejected(1, 'Reason', 'admin@example.com');
        $this->archiveService->archiveForInvestigation(2, 'Notes', 'admin@example.com');

        $stats = $this->archiveService->getArchiveStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('investigated', $stats);
    }

    /**
     * @test
     * Query archived transactions
     */
    public function test_query_archived_transactions(): void
    {
        $this->archiveService->archiveRejected(1, 'Reason', 'admin@example.com');

        $filters = [];
        $results = $this->archiveService->queryArchived($filters, page: 1, perPage: 10);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('items', $results);
        $this->assertArrayHasKey('total', $results);
        $this->assertArrayHasKey('page', $results);
        $this->assertArrayHasKey('per_page', $results);
    }

    /**
     * @test
     * Get archive details
     */
    public function test_get_archive_details(): void
    {
        $this->testArchiveRepository->addArchive([
            'id' => 100,
            'duplicate_id' => 1,
            'status' => 'REJECTED',
            'reason' => 'False positive',
            'archived_at' => '2026-04-09 10:00:00',
            'archived_by' => 'admin@example.com'
        ]);

        $details = $this->archiveService->getArchiveDetails(100);

        $this->assertIsArray($details);
        $this->assertEquals(100, $details['id']);
        $this->assertEquals('REJECTED', $details['status']);
    }

    /**
     * @test
     * Get archive details throws for non-existent
     */
    public function test_get_archive_details_throws_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->archiveService->getArchiveDetails(999);
    }

    /**
     * @test
     * Bulk archive rejected transactions
     */
    public function test_bulk_archive_rejected_transactions(): void
    {
        // Add more REJECTED transactions for bulk test
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 5,
            transactionCode: 'TXN-005',
            transDate: '2026-04-05',
            amount: 3000.00,
            counterpartyName: 'Vendor E',
            matchReason: 'Partial match',
            confidenceScore: 60,
            decisionStatus: 'REJECTED',
            createdAt: '2026-04-08',
            decidedBy: 'admin@example.com',
            decidedAt: '2026-04-09',
            reason: 'Manual review'
        ));

        $result = $this->archiveService->bulkArchiveRejected(
            [1, 5],
            'Bulk rejection batch',
            'batch@example.com'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('archived_count', $result);
        $this->assertArrayHasKey('failed_ids', $result);
        $this->assertArrayHasKey('errors', $result);

        $this->assertEquals(2, $result['archived_count']);
        $this->assertEmpty($result['failed_ids']);
    }

    /**
     * @test
     * Bulk archive handles partial failures
     */
    public function test_bulk_archive_handles_partial_failures(): void
    {
        // Try to archive: 1 (REJECTED, should work), 3 (APPROVED, should fail)
        $result = $this->archiveService->bulkArchiveRejected(
            [1, 3],
            'Mixed batch',
            'batch@example.com'
        );

        $this->assertEquals(1, $result['archived_count']);
        $this->assertCount(1, $result['failed_ids']);
        $this->assertContains(3, $result['failed_ids']);
    }

    /**
     * @test
     * Archive failed logs error
     */
    public function test_archive_failed_logs_error(): void
    {
        // Make archive repository fail
        $this->testArchiveRepository->shouldFail = true;

        try {
            $this->archiveService->archiveRejected(1, 'Will fail', 'admin@example.com');
        } catch (DuplicateReviewException $e) {
            // Expected
        }

        $this->assertCount(1, $this->testLogger->errorCalls);
        $this->assertStringContainsString('Failed to archive', $this->testLogger->errorCalls[0]);
    }

    /**
     * @test
     * Archive validates transaction status before archiving
     */
    public function test_archive_validates_status(): void
    {
        $this->testRepository->addTransaction(new DuplicateTransaction(
            id: 10,
            transactionCode: 'TXN-010',
            transDate: '2026-04-10',
            amount: 100.00,
            counterpartyName: 'Test',
            matchReason: 'Test',
            confidenceScore: 90,
            decisionStatus: 'SOMETHING_ELSE',
            createdAt: '2026-04-08',
            decidedBy: null,
            decidedAt: null,
            reason: null
        ));

        $this->expectException(DuplicateReviewException::class);
        $this->archiveService->archiveRejected(10, 'Invalid status', 'admin@example.com');
    }

    /**
     * @test
     * Multiple archive operations maintain separate logs
     */
    public function test_multiple_archive_operations_log_separately(): void
    {
        $this->archiveService->archiveRejected(1, 'First', 'user1@example.com');
        $this->archiveService->archiveForInvestigation(2, 'Second', 'user2@example.com');

        $this->assertCount(2, $this->testLogger->infoCalls);
    }
}

// ============================================
// Test Doubles
// ============================================

class TestDuplicateRepository
{
    private $transactions = [];

    public function addTransaction(DuplicateTransaction $transaction): void
    {
        $this->transactions[$transaction->id] = $transaction;
    }

    public function findById(int $id): ?DuplicateTransaction
    {
        return $this->transactions[$id] ?? null;
    }
}

class ArchiveTestArchiveRepository
{
    public $archiveCalled = false;
    public $lastArchivedId = null;
    public $lastStatus = null;
    public $lastReason = null;
    public $shouldFail = false;
    private $archives = [];

    public function archive(int $duplicateId, string $status, string $reason, string $archivedBy): void
    {
        if ($this->shouldFail) {
            throw new \Exception('Archive operation failed');
        }

        $this->archiveCalled = true;
        $this->lastArchivedId = $duplicateId;
        $this->lastStatus = $status;
        $this->lastReason = $reason;
    }

    public function countByStatus(string $status): int
    {
        return 0;
    }

    public function findArchived(array $filters, int $page, int $perPage): array
    {
        return [
            'items' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->archives[$id] ?? null;
    }

    public function addArchive(array $archive): void
    {
        $this->archives[$archive['id']] = $archive;
    }
}

class ArchiveTestLogger
{
    public $infoCalls = [];
    public $errorCalls = [];
    public $warningCalls = [];

    public function info($message, array $context = []): void
    {
        $this->infoCalls[] = $message;
    }

    public function error($message, array $context = []): void
    {
        $this->errorCalls[] = $message;
    }

    public function warning($message, array $context = []): void
    {
        $this->warningCalls[] = $message;
    }

    public function debug($message, array $context = []): void
    {
    }
}
