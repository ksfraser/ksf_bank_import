<?php
namespace Ksfraser\FaBankImport\Tests\Unit\Services\Posting;

use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use Ksfraser\FaBankImport\Import\Services\Posting\PostingOrchestratorService;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\ITransactionPostingService;
use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;
use Ksfraser\FaBankImport\Repositories\Interfaces\ITransactionRepository;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IArchiveService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IRetryPolicy;
use Psr\Log\LoggerInterface;

/**
 * Test PostingOrchestratorService: main orchestration service for copying approved duplicates.
 * Tests all workflow paths: happy path (copy), error path (retry), skipped path (archive).
 */
class PostingOrchestratorServiceTest extends TestCase
{
    private PostingOrchestratorService $service;
    private TestDuplicateRepository $repoMock;
    private TestTransactionRepository $mainRepoMock;
    private TestPostingEligibilityService $eligibilityMock;
    private TestTransactionPostingService $postingServiceMock;
    private TestArchiveService $archiveServiceMock;
    private TestRetryPolicy $retryPolicyMock;
    private TestLogger $loggerMock;

    protected function setUp(): void
    {
        $this->repoMock = new TestDuplicateRepository();
        $this->mainRepoMock = new TestTransactionRepository();
        $this->eligibilityMock = new TestPostingEligibilityService();
        $this->postingServiceMock = new TestTransactionPostingService();
        $this->archiveServiceMock = new TestArchiveService();
        $this->retryPolicyMock = new TestRetryPolicy();
        $this->loggerMock = new TestLogger();

        $this->service = new PostingOrchestratorService(
            $this->repoMock,
            $this->mainRepoMock,
            $this->eligibilityMock,
            $this->postingServiceMock,
            $this->archiveServiceMock,
            $this->retryPolicyMock,
            $this->loggerMock
        );
    }

    /**
     * @test
     * Happy path: APPROVED transaction is successfully copied to main table
     */
    public function test_approved_eligible_transaction_is_posted(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 42,
            transactionCode: 'TXN001',
            amount: 1000.00,
            decisionStatus: 'APPROVED',
            decidedBy: 'user@example.com',
            decidedAt: new DateTimeImmutable(),
            decisionReason: 'Perfect match'
        );

        $this->eligibilityMock->setResult('ELIGIBLE');
        $this->postingServiceMock->setReturnId(999);

        $result = $this->service->executePosting($request);

        $this->assertEquals('POSTED', $result->status);
        $this->assertEquals(42, $result->duplicateId);
        $this->assertEquals(999, $result->mainTransactionId);
        $this->assertTrue($result->isSuccessful());
    }

    /**
     * @test
     * REJECTED transaction is archived, not copied
     */
    public function test_rejected_transaction_is_skipped_and_archived(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 43,
            transactionCode: 'TXN002',
            amount: 500.00,
            decisionStatus: 'REJECTED',
            decidedBy: 'approver@example.com',
            decidedAt: new DateTimeImmutable(),
            decisionReason: 'Mismatch in amounts'
        );

        $this->eligibilityMock->setResult('SKIP');

        $result = $this->service->executePosting($request);

        $this->assertEquals('SKIPPED', $result->status);
        $this->assertTrue($result->isSkipped());
        $this->assertTrue($this->archiveServiceMock->wasArchiveCalled());
    }

    /**
     * @test
     * INVESTIGATE transaction is skipped pending manual review
     */
    public function test_investigate_transaction_is_skipped(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 44,
            transactionCode: 'TXN003',
            amount: 750.00,
            decisionStatus: 'INVESTIGATE',
            decidedBy: 'reviewer@example.com',
            decidedAt: new DateTimeImmutable(),
            decisionReason: 'Conflicting data'
        );

        $this->eligibilityMock->setResult('SKIP');

        $result = $this->service->executePosting($request);

        $this->assertEquals('SKIPPED', $result->status);
    }

    /**
     * @test
     * PENDING status is held waiting for review completion
     */
    public function test_pending_transaction_is_held(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 45,
            transactionCode: 'TXN004',
            amount: 1200.00,
            decisionStatus: 'PENDING',
            decidedBy: '',
            decidedAt: new DateTimeImmutable(),
            decisionReason: ''
        );

        $this->eligibilityMock->setResult('HOLD');

        $result = $this->service->executePosting($request);

        $this->assertEquals('HELD', $result->status);
        $this->assertTrue($result->isHeld());
    }

    /**
     * @test
     * Amount exceeding limit is held for manual review
     */
    public function test_amount_exceeding_limit_is_held(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 46,
            transactionCode: 'TXN005',
            amount: 1000001.00,
            decisionStatus: 'APPROVED',
            decidedBy: 'user@example.com',
            decidedAt: new DateTimeImmutable(),
            decisionReason: ''
        );

        $this->eligibilityMock->setResult('HOLD');

        $result = $this->service->executePosting($request);

        $this->assertEquals('HELD', $result->status);
    }

    /**
     * @test
     * Database error during copy is caught and returned as ERROR
     */
    public function test_database_error_during_copy_returns_error(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 47,
            transactionCode: 'TXN006',
            amount: 2000.00,
            decisionStatus: 'APPROVED',
            decidedBy: 'user@example.com',
            decidedAt: new DateTimeImmutable(),
            decisionReason: ''
        );

        $this->eligibilityMock->setResult('ELIGIBLE');
        $this->postingServiceMock->setShouldThrow(true);

        $result = $this->service->executePosting($request);

        $this->assertEquals('ERROR', $result->status);
        $this->assertTrue($result->isFailed());
        $this->assertNotEmpty($result->errorMessage);
    }

    /**
     * @test
     * Invalid eligibility status returns ERROR
     */
    public function test_invalid_eligibility_status_returns_error(): void
    {
        $request = new PostingRequestDTO(
            duplicateId: 48,
            transactionCode: 'TXN007',
            amount: 500.00,
            decisionStatus: 'INVALID',
            decidedBy: 'user@example.com',
            decidedAt: new DateTimeImmutable(),
            decisionReason: ''
        );

        $this->eligibilityMock->setResult('ERROR');

        $result = $this->service->executePosting($request);

        $this->assertEquals('ERROR', $result->status);
    }
}

// ============================================================================
// TEST DOUBLES
// ============================================================================

class TestDuplicateRepository implements IDuplicateTransactionRepository
{
    public function findById($id): ?object { return null; }
    public function findPending($limit, $offset): array { return []; }
    public function findApprovedForPosting($limit = 1000): array { return []; }
    public function getAuditHistory($id): array { return []; }
    public function findByTransactionCode(string $code): ?object { return null; }
    public function create($entity): int { return 0; }
    public function update($entity): bool { return true; }
    public function delete($id): bool { return true; }
}

class TestTransactionRepository implements ITransactionRepository
{
    public function create($entity): int { return 0; }
    public function update($entity): bool { return true; }
    public function findByCode(string $code): ?object { return null; }
    public function findById($id): ?object { return null; }
    public function delete($id): bool { return true; }
}

class TestPostingEligibilityService implements IPostingEligibilityService
{
    private string $result = 'ELIGIBLE';

    public function setResult(string $result): void { $this->result = $result; }
    public function determineEligibility(string $decisionStatus, float $amount): string { return $this->result; }
}

class TestTransactionPostingService implements ITransactionPostingService
{
    private int $returnId = 999;
    private bool $shouldThrow = false;

    public function setReturnId(int $id): void { $this->returnId = $id; }
    public function setShouldThrow(bool $throw): void { $this->shouldThrow = $throw; }

    public function copyApprovedTransaction(int $duplicateId, string $transactionCode, float $amount, string $decisionStatus): int
    {
        if ($this->shouldThrow) {
            throw new \Exception('Database constraint violation');
        }
        return $this->returnId;
    }
}

class TestArchiveService implements IArchiveService
{
    private bool $archiveCalled = false;

    public function wasArchiveCalled(): bool { return $this->archiveCalled; }
    public function archive(int $duplicateId, string $reason, string $notes = ''): bool
    {
        $this->archiveCalled = true;
        return true;
    }
}

class TestRetryPolicy implements IRetryPolicy
{
    public function calculateBackoff(int $attemptNumber): int { return 5 * (2 ** $attemptNumber); }
}

class TestLogger implements LoggerInterface
{
    private array $logs = [];

    public function emergency($message, array $context = []): void { $this->logs[] = ['emergency', $message]; }
    public function alert($message, array $context = []): void { $this->logs[] = ['alert', $message]; }
    public function critical($message, array $context = []): void { $this->logs[] = ['critical', $message]; }
    public function error($message, array $context = []): void { $this->logs[] = ['error', $message]; }
    public function warning($message, array $context = []): void { $this->logs[] = ['warning', $message]; }
    public function notice($message, array $context = []): void { $this->logs[] = ['notice', $message]; }
    public function info($message, array $context = []): void { $this->logs[] = ['info', $message]; }
    public function debug($message, array $context = []): void { $this->logs[] = ['debug', $message]; }
    public function log($level, $message, array $context = []): void { $this->logs[] = [$level, $message]; }
}
