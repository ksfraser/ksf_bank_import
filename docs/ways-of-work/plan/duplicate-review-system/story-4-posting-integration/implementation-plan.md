---
title: "Story 4: Transaction Posting Integration - Implementation Plan"
epic: "Duplicate Review System"
feature: "Transaction Posting Integration"
status: "Ready for Implementation"
timeline: "3 days"
story_points: "13"
created: "2026-04-09"
version: "1.0"
---

# Story 4: Transaction Posting Integration - Implementation Plan

## Executive Summary

This document outlines the step-by-step implementation approach for Story 4: Transaction Posting Integration. The feature integrates the duplicate review decision system (Story 2) with the existing transaction posting infrastructure, routing APPROVED duplicates to GL posting and REJECTED duplicates to archive.

**Scope**: Core posting orchestration, validation, audit logging, and error handling
**Timeline**: 3 business days
**Story Points**: 13 (2 points infrastructure setup, 3 points per main service, 4 points testing)
**Methodology**: Test-Driven Development (TDD)

---

## Phase Overview

```
Phase 1: Foundations (0.5 days)
├─ Setup database migrations
├─ Create DTOs and value objects
└─ Create interfaces & exceptions

Phase 2: Core Services (1.5 days)
├─ PostingOrchestratorService
├─ PostingEligibilityService
├─ RetryPolicyService
└─ ArchiveService

Phase 3: Integration & Testing (1 day)
├─ Integrate with existing repos
├─ Create unit tests (100% coverage)
├─ Create integration tests
└─ Performance validation

Total: 3 days across 3 phases
```

---

## Phase 1: Foundations (0.5 days)

### Task 1.1: Database Migrations (0.5 points)

**Objective**: Create database schema for posting audit log, archive, and constraints

**TDD Approach**: 
1. Write test expecting posting_audit_log table with exact columns
2. Write test expecting bi_transactions_rejected_archive table
3. Implement migrations to pass tests

**Implementation Steps**:

**Step 1a**: Create migration file `migrations/002_create_posting_audit_log.php`

```php
<?php
namespace Ksfraser\FaBankImport\Migrations;

class CreatePostingAuditLog
{
    public function up($pdo)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS posting_audit_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transaction_id BIGINT NOT NULL UNIQUE,
    
    -- Review context (snapshot at posting time)
    review_decision VARCHAR(50),
    review_decided_by VARCHAR(255),
    review_decided_at DATETIME(3),
    review_reason TEXT,
    
    -- Posting result
    posted_status ENUM('POSTED', 'SKIPPED', 'HELD', 'ERROR') NOT NULL,
    posted_at DATETIME(3),
    posted_to_account VARCHAR(50),
    error_message TEXT,
    retry_count TINYINT DEFAULT 0,
    
    -- Audit
    created_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    
    FOREIGN KEY (transaction_id) REFERENCES bi_transactions_dupe(id) ON DELETE RESTRICT,
    INDEX idx_posted_status (posted_status),
    INDEX idx_posted_at (posted_at DESC),
    INDEX idx_review_decision (review_decision),
    CONSTRAINT chk_no_post_rejected 
        CHECK (NOT (review_decision = 'REJECTED' AND posted_status = 'POSTED'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        
        $pdo->exec($sql);
    }
    
    public function down($pdo)
    {
        $pdo->exec('DROP TABLE IF EXISTS posting_audit_log');
    }
}
```

**Step 1b**: Create migration file `migrations/003_create_rejected_archive.php`

```php
<?php
namespace Ksfraser\FaBankImport\Migrations;

class CreateRejectedArchive
{
    public function up($pdo)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS bi_transactions_rejected_archive (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    duplicate_id BIGINT NOT NULL UNIQUE,
    
    -- Rejection info
    rejection_reason VARCHAR(255) NOT NULL,
    rejected_by VARCHAR(255) NOT NULL,
    rejected_at DATETIME(3) NOT NULL,
    rejection_notes TEXT,
    
    -- Original data snapshot (immutable)
    original_data_snapshot JSON NOT NULL,
    
    -- Audit
    archived_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    created_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    
    FOREIGN KEY (duplicate_id) REFERENCES bi_transactions_dupe(id) ON DELETE RESTRICT,
    INDEX idx_archived_at (archived_at DESC),
    INDEX idx_rejection_reason (rejection_reason),
    CONSTRAINT archive_immutable CHECK (created_at <= archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        
        $pdo->exec($sql);
        
        // Add column to bi_transactions_dupe to track when posted
        $alterDupe = <<<SQL
ALTER TABLE bi_transactions_dupe 
ADD COLUMN posted_at DATETIME(3) NULL AFTER updated_at;
SQL;
        
        $pdo->exec($alterDupe);
    }
    
    public function down($pdo)
    {
        $pdo->exec('ALTER TABLE bi_transactions_dupe DROP COLUMN posted_at');
        $pdo->exec('DROP TABLE IF EXISTS bi_transactions_rejected_archive');
    }
}
```

**Step 1c**: Add columns to existing `bi_transactions` table (migration)

```php
<?php
namespace Ksfraser\FaBankImport\Migrations;

class AddDuplicateTrackingToBiTransactions
{
    public function up($pdo)
    {
        $sql = <<<SQL
ALTER TABLE bi_transactions 
ADD COLUMN source VARCHAR(50) DEFAULT 'manual' AFTER amount,
ADD COLUMN duplicate_id BIGINT NULL AFTER source;

ALTER TABLE bi_transactions
ADD CONSTRAINT fk_duplicate_id 
FOREIGN KEY (duplicate_id) REFERENCES bi_transactions_dupe(id) ON DELETE SET NULL;

CREATE INDEX idx_duplicate_id ON bi_transactions(duplicate_id);
SQL;
        
        $pdo->exec($sql);
    }
    
    public function down($pdo)
    {
        $pdo->exec('ALTER TABLE bi_transactions DROP FOREIGN KEY fk_duplicate_id');
        $pdo->exec('ALTER TABLE bi_transactions DROP INDEX idx_duplicate_id');
        $pdo->exec('ALTER TABLE bi_transactions DROP COLUMN duplicate_id, DROP COLUMN source');
    }
}
```

**Acceptance Criteria**:
- [ ] posting_audit_log table exists with 14 columns
- [ ] bi_transactions_rejected_archive table exists with 9 columns
- [ ] Foreign keys prevent orphaned records
- [ ] Constraints prevent REJECTED transactions from being posted
- [ ] Indexes exist on key query paths (status, dates)
- [ ] Migration rollback (down) tested and working

**Definition of Done**:
- Migrations execute without error
- Schema validated against test expectations
- No existing data integrity violations
- Both tables have appropriate access permissions

---

### Task 1.2: Create DTOs & Value Objects (0.2 points)

**Objective**: Define immutable data transfer objects for posting workflow

**Files to Create**:
- `src/.../Import/Services/Posting/DTOs/PostingRequestDTO.php`
- `src/.../Import/Services/Posting/DTOs/PostingResultDTO.php`
- `src/.../Import/Services/Posting/DTOs/PostingAuditDTO.php`

**PostingRequestDTO.php**:
```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\DTOs;

use DateTimeImmutable;

/**
 * Immutable request object for posting a transaction.
 * Created once and never modified.
 */
final class PostingRequestDTO
{
    public readonly int $transactionId;
    public readonly string $transactionCode;
    public readonly float $amount;
    public readonly string $glAccount;
    public readonly string $decisionStatus;
    public readonly string $decidedBy;
    public readonly DateTimeImmutable $decidedAt;
    
    public function __construct(
        int $transactionId,
        string $transactionCode,
        float $amount,
        string $glAccount,
        string $decisionStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt
    ) {
        $this->transactionId = $transactionId;
        $this->transactionCode = $transactionCode;
        $this->amount = $amount;
        $this->glAccount = $glAccount;
        $this->decisionStatus = $decisionStatus;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $decidedAt;
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            intval($data['transaction_id']),
            strval($data['transaction_code']),
            floatval($data['amount']),
            strval($data['gl_account']),
            strval($data['decision_status']),
            strval($data['decided_by']),
            new DateTimeImmutable($data['decided_at'])
        );
    }
    
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'transaction_code' => $this->transactionCode,
            'amount' => $this->amount,
            'gl_account' => $this->glAccount,
            'decision_status' => $this->decisionStatus,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt->format(\DateTime::ATOM),
        ];
    }
}
```

**PostingResultDTO.php**:
```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\DTOs;

use DateTimeImmutable;

/**
 * Immutable result object representing posting outcome.
 */
final class PostingResultDTO
{
    public readonly int $transactionId;
    public readonly string $status; // POSTED, SKIPPED, HELD, ERROR
    public readonly ?string $glRecordId;
    public readonly ?string $errorMessage;
    public readonly ?DateTimeImmutable $postedAt;
    public readonly int $retryCount;
    
    public function __construct(
        int $transactionId,
        string $status,
        ?string $glRecordId = null,
        ?string $errorMessage = null,
        ?DateTimeImmutable $postedAt = null,
        int $retryCount = 0
    ) {
        $this->transactionId = $transactionId;
        $this->status = $status;
        $this->glRecordId = $glRecordId;
        $this->errorMessage = $errorMessage;
        $this->postedAt = $postedAt;
        $this->retryCount = $retryCount;
    }
    
    public static function posted(int $txnId, string $glId): self
    {
        return new self($txnId, 'POSTED', $glId, null, new DateTimeImmutable(), 0);
    }
    
    public static function skipped(int $txnId, string $reason = ''): self
    {
        return new self($txnId, 'SKIPPED', null, $reason, null, 0);
    }
    
    public static function error(int $txnId, string $message, int $retryCount = 0): self
    {
        return new self($txnId, 'ERROR', null, $message, null, $retryCount);
    }
}
```

**Acceptance Criteria**:
- [ ] DTOs are immutable (readonly properties)
- [ ] fromArray() and toArray() methods work correctly
- [ ] Factory methods (posted(), skipped(), error()) available
- [ ] No setters or property modification possible

---

### Task 1.3: Create Interfaces & Exception Classes (0.3 points)

**Objective**: Define contracts and exception hierarchy for posting services

**Interfaces**:

`src/.../Import/Services/Posting/Interfaces/IPostingOrchestratorService.php`:
```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingResultDTO;

interface IPostingOrchestratorService
{
    /**
     * Execute posting for a single transaction based on review decision.
     * 
     * @throws InvalidPostingStatusException
     * @throws PostingException
     */
    public function executePosting(PostingRequestDTO $request): PostingResultDTO;
    
    /**
     * Execute batch posting for approved transactions.
     * @return array<PostingResultDTO>
     */
    public function executeBatch(int $limit = 1000): array;
    
    /**
     * Query posting status for a transaction.
     */
    public function getPostingStatus(int $transactionId): PostingResultDTO;
    
    /**
     * Rollback a batch posting by admin action.
     */
    public function rollbackBatch(string $batchId, string $reason, string $performedBy): bool;
}
```

`src/.../Import/Services/Posting/Interfaces/IPostingEligibilityService.php`:
```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

interface IPostingEligibilityService
{
    // Constants for eligibility status
    const STATUS_ELIGIBLE = 'ELIGIBLE';
    const STATUS_SKIP = 'SKIP'; // REJECTED or INVESTIGATE
    const STATUS_HOLD = 'HOLD'; // Insufficient GL balance
    const STATUS_ERROR = 'ERROR'; // Invalid state
    
    /**
     * Determine if transaction should be posted.
     * Pure function: no side effects, deterministic result.
     * 
     * @param string $decisionStatus APPROVED, REJECTED, INVESTIGATE, PENDING
     * @param float $amount Transaction amount
     * @param string $glAccount GL account code
     * 
     * @return string One of STATUS_* constants
     */
    public function determineEligibility(
        string $decisionStatus, 
        float $amount, 
        string $glAccount
    ): string;
}
```

**Exception Classes**:

`src/.../Import/Exceptions/PostingException.php` (base):
```php
<?php
namespace Ksfraser\FaBankImport\Import\Exceptions;

class PostingException extends \DomainException
{
    public static function glApiError(string $message, ?\Throwable $previous = null): self
    {
        return new self("GL API Error: {$message}", 0, $previous);
    }
    
    public static function networkError(string $message, ?\Throwable $previous = null): self
    {
        return new self("Network Error: {$message}", 0, $previous);
    }
}
```

`src/.../Import/Exceptions/InvalidPostingStatusException.php`:
```php
<?php
namespace Ksfraser\FaBankImport\Import\Exceptions;

class InvalidPostingStatusException extends PostingException
{
    public static function invalidStatus(string $status): self
    {
        return new self("Invalid posting status: {$status}. Expected POSTED|SKIPPED|HELD|ERROR");
    }
}
```

**Acceptance Criteria**:
- [ ] All interfaces define clear contracts
- [ ] Exception hierarchy follows PSR standards
- [ ] Factory methods available for common error scenarios
- [ ] Backward compatible with existing exception handling

---

## Phase 2: Core Services (1.5 days)

### Task 2.1: PostingEligibilityService (1 point)

**Objective**: Pure function to determine posting eligibility based on review status

**TDD Approach**:
1. Red: Write failing test for APPROVED → ELIGIBLE
2. Green: Implement minimal code
3. Refactor: Add validation logic

**Test Cases** (`tests/unit/Services/Posting/PostingEligibilityServiceTest.php`):

```php
<?php
namespace Ksfraser\FaBankImport\Tests\Unit\Services\Posting;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Posting\PostingEligibilityService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;

class PostingEligibilityServiceTest extends TestCase
{
    private IPostingEligibilityService $service;
    
    protected function setUp(): void
    {
        $this->service = new PostingEligibilityService();
    }
    
    public function test_approved_eligible(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 1000.00, '1100-AR');
        $this->assertEquals(IPostingEligibilityService::STATUS_ELIGIBLE, $result);
    }
    
    public function test_rejected_skipped(): void
    {
        $result = $this->service->determineEligibility('REJECTED', 1000.00, '1100-AR');
        $this->assertEquals(IPostingEligibilityService::STATUS_SKIP, $result);
    }
    
    public function test_investigate_skipped(): void
    {
        $result = $this->service->determineEligibility('INVESTIGATE', 1000.00, '1100-AR');
        $this->assertEquals(IPostingEligibilityService::STATUS_SKIP, $result);
    }
    
    public function test_pending_hold(): void
    {
        $result = $this->service->determineEligibility('PENDING', 1000.00, '1100-AR');
        $this->assertEquals(IPostingEligibilityService::STATUS_HOLD, $result);
    }
    
    public function test_invalid_status_error(): void
    {
        $result = $this->service->determineEligibility('INVALID', 1000.00, '1100-AR');
        $this->assertEquals(IPostingEligibilityService::STATUS_ERROR, $result);
    }
    
    public function test_amount_exceeds_limit(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 1000001.00, '1100-AR');
        $this->assertEquals(IPostingEligibilityService::STATUS_HOLD, $result);
    }
    
    public function test_invalid_gl_account_error(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 1000.00, 'INVALID!!!');
        $this->assertEquals(IPostingEligibilityService::STATUS_ERROR, $result);
    }
}
```

**Implementation** (`src/.../Import/Services/Posting/PostingEligibilityService.php`):

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting;

use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;

class PostingEligibilityService implements IPostingEligibilityService
{
    private const MAX_AMOUNT = 1000000.00;
    private const VALID_GL_PATTERN = '/^[A-Z0-9\-]+$/';
    
    public function determineEligibility(
        string $decisionStatus, 
        float $amount, 
        string $glAccount
    ): string {
        // Validate GL account format
        if (!preg_match(self::VALID_GL_PATTERN, $glAccount)) {
            return self::STATUS_ERROR;
        }
        
        // Skip non-approved decisions
        if ($decisionStatus === 'REJECTED' || $decisionStatus === 'INVESTIGATE') {
            return self::STATUS_SKIP;
        }
        
        // Hold pending decisions
        if ($decisionStatus === 'PENDING') {
            return self::STATUS_HOLD;
        }
        
        // Return error for invalid status
        if ($decisionStatus !== 'APPROVED') {
            return self::STATUS_ERROR;
        }
        
        // Check amount ceiling
        if ($amount > self::MAX_AMOUNT) {
            return self::STATUS_HOLD;
        }
        
        // Everything passed - eligible for posting
        return self::STATUS_ELIGIBLE;
    }
}
```

**Acceptance Criteria**:
- [ ] All 7 test cases passing
- [ ] Pure function (no state, no DB access)
- [ ] Deterministic behavior (same input = same output)
- [ ] GL account validation prevents injection attacks
- [ ] Amount ceiling prevents excessive GL postings

---

### Task 2.2: PostingOrchestratorService (1.5 points)

**Objective**: Main orchestration service coordinating posting workflow

**Key Responsibilities**:
- Query duplicate status from repository
- Check eligibility
- Execute GL posting or archive
- Log audit trail
- Handle errors with retry

**Implementation** (`src/.../Import/Services/Posting/PostingOrchestratorService.php`):

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting;

use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Ksfraser\FaBankImport\Import\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Repositories\Interfaces\IDuplicateTransactionRepository;
use Ksfraser\FaBankImport\Import\Repositories\Interfaces\ITransactionRepository;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingResultDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingOrchestratorService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\ITransactionPostingService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IArchiveService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IRetryPolicy;
use Ksfraser\FaBankImport\Import\Exceptions\InvalidPostingStatusException;
use Ksfraser\FaBankImport\Import\Exceptions\PostingException;

class PostingOrchestratorService implements IPostingOrchestratorService
{
    public function __construct(
        private IDuplicateTransactionRepository $repo,
        private ITransactionRepository $mainRepo,
        private IPostingEligibilityService $eligibility,
        private ITransactionPostingService $postingService,
        private IArchiveService $archiveService,
        private IRetryPolicy $retryPolicy,
        private LoggerInterface $logger
    ) {}
    
    public function executePosting(PostingRequestDTO $request): PostingResultDTO
    {
        try {
            $this->logger->info(
                "Starting posting for duplicate {$request->duplicateId}",
                ['transaction_code' => $request->transactionCode]
            );
            
            // Step 1: Check eligibility
            $eligibility = $this->eligibility->determineEligibility(
                $request->decisionStatus,
                $request->amount
            );
            
            return match ($eligibility) {
                'ELIGIBLE' => $this->copyToMainTable($request),
                'SKIP' => $this->handleSkipped($request),
                'HOLD' => $this->handleHeld($request),
                'ERROR' => $this->handleError($request, 'Invalid eligibility state'),
                default => throw InvalidPostingStatusException::invalidStatus($eligibility),
            };
        } catch (\Throwable $e) {
            $this->logger->error(
                "Posting failed for duplicate {$request->duplicateId}",
                ['error' => $e->getMessage()]
            );
            return PostingResultDTO::error($request->duplicateId, $e->getMessage());
        }
    }
    
    private function copyToMainTable(PostingRequestDTO $request): PostingResultDTO
    {
        try {
            // Copy approved duplicate to main bi_transactions table
            $mainTxnId = $this->postingService->copyApprovedTransaction(
                $request->duplicateId,
                $request->transactionCode,
                $request->amount,
                $request->decisionStatus
            );
            
            $this->logger->info(
                "Transaction copied to main ledger",
                ['main_txn_id' => $mainTxnId, 'duplicate_id' => $request->duplicateId]
            );
            
            return PostingResultDTO::posted($request->duplicateId, $mainTxnId);
        } catch (PostingException $e) {
            throw $e;
        }
    }
    
    private function handleSkipped(PostingRequestDTO $request): PostingResultDTO
    {
        $this->archiveService->archive(
            $request->duplicateId,
            $request->decisionStatus,
            $request->decisionReason
        );
        
        $this->logger->info(
            "Transaction skipped - archived",
            ['reason' => $request->decisionStatus]
        );
        
        return PostingResultDTO::skipped($request->duplicateId, $request->decisionStatus);
    }
    
    private function handleHeld(PostingRequestDTO $request): PostingResultDTO
    {
        $this->logger->warning(
            "Transaction held for manual review",
            ['reason' => 'Business rule constraint']
        );
        
        return PostingResultDTO::skipped($request->duplicateId, 'HELD');
    }
    
    private function handleError(PostingRequestDTO $request, string $message): PostingResultDTO
    {
        $this->logger->error(
            "Posting error for duplicate",
            ['message' => $message]
        );
        
        return PostingResultDTO::error($request->duplicateId, $message);
    }
    
    public function executeBatch(int $limit = 1000): array
    {
        $results = [];
        $transactions = $this->repo->findApprovedForPosting($limit);
        
        foreach ($transactions as $txn) {
            $request = $this->buildRequest($txn);
            $result = $this->executePosting($request);
            $results[] = $result;
        }
        
        return $results;
    }
    
    public function getPostingStatus(int $duplicateId): PostingResultDTO
    {
        $audit = $this->repo->getAuditHistory($duplicateId);
        
        if (empty($audit)) {
            throw new PostingException("No posting record found");
        }
        
        return PostingResultDTO::fromAudit($audit[0]);
    }
    
    private function buildRequest(DuplicateTransaction $txn): PostingRequestDTO
    {
        return new PostingRequestDTO(
            (int) $txn->getId(),
            $txn->getTransactionCode(),
            (float) $txn->getAmount(),
            $txn->getDecisionStatus(),
            (string) $txn->getDecidedBy(),
            $txn->getReason() ?? '',
            new DateTimeImmutable($txn->getDecidedAt())
        );
    }
}
```

**Unit Tests** (`tests/unit/Services/Posting/PostingOrchestratorServiceTest.php`):

```php
<?php
namespace Ksfraser\FaBankImport\Tests\Unit\Services\Posting;

use PHPUnit\Framework\TestCase;
use Mockery;
use Ksfraser\FaBankImport\Import\Services\Posting\PostingOrchestratorService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IGLPostingService;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class PostingOrchestratorServiceTest extends TestCase
{
    private PostingOrchestratorService $service;
    private $repoMock;
    private $eligibilityMock;
    private $glServiceMock;
    private $archiveServiceMock;
    private $retryPolicyMock;
    private $loggerMock;
    
    protected function setUp(): void
    {
        $this->repoMock = Mockery::mock('IDuplicateTransactionRepository');
        $this->eligibilityMock = Mockery::mock(IPostingEligibilityService::class);
        $this->glServiceMock = Mockery::mock(IGLPostingService::class);
        $this->archiveServiceMock = Mockery::mock('IArchiveService');
        $this->retryPolicyMock = Mockery::mock('IRetryPolicy');
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        
        $this->service = new PostingOrchestratorService(
            $this->repoMock,
            $this->eligibilityMock,
            $this->glServiceMock,
            $this->archiveServiceMock,
            $this->retryPolicyMock,
            $this->loggerMock
        );
    }
    
    public function test_post_eligible_transaction(): void
    {
        $request = new PostingRequestDTO(
            42, 'TXN001', 1000.00, '1100-AR', 'APPROVED', 'user@example.com', new DateTimeImmutable()
        );
        
        $this->eligibilityMock
            ->shouldReceive('determineEligibility')
            ->with('APPROVED', 1000.00, '1100-AR')
            ->andReturn('ELIGIBLE');
        
        $this->glServiceMock
            ->shouldReceive('post')
            ->andReturn('GL-999');
        
        $result = $this->service->executePosting($request);
        
        $this->assertEquals('POSTED', $result->status);
        $this->assertEquals('GL-999', $result->glRecordId);
    }
    
    public function test_skip_rejected_transaction(): void
    {
        $request = new PostingRequestDTO(
            43, 'TXN002', 500.00, '1100-AR', 'REJECTED', 'user@example.com', new DateTimeImmutable()
        );
        
        $this->eligibilityMock
            ->shouldReceive('determineEligibility')
            ->andReturn('SKIP');
        
        $this->archiveServiceMock
            ->shouldReceive('archive')
            ->with(43, 'REJECTED');
        
        $result = $this->service->executePosting($request);
        
        $this->assertEquals('SKIPPED', $result->status);
    }
    
    public function tearDown(): void
    {
        Mockery::close();
    }
}
```

**Acceptance Criteria**:
- [ ] 5 unit tests passing
- [ ] executePosting() returns PostingResultDTO
- [ ] GL posting triggers archived for REJECTED
- [ ] Error handling with retry logic
- [ ] Audit logging on all paths

---

### Task 2.3: ArchiveService & RetryPolicyService (0.5 points)

**ArchiveService** (`src/.../Import/Services/Posting/ArchiveService.php`):

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting;

use Psr\Log\LoggerInterface;
use Ksfraser\FaBankImport\Import\Repositories\Interfaces\IDuplicateTransactionRepository;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IArchiveService;

class ArchiveService implements IArchiveService
{
    public function __construct(
        private IDuplicateTransactionRepository $repo,
        private LoggerInterface $logger
    ) {}
    
    public function archive(int $transactionId, string $reason): bool
    {
        $this->logger->info(
            "Archiving transaction",
            ['transaction_id' => $transactionId, 'reason' => $reason]
        );
        
        $transaction = $this->repo->findById($transactionId);
        
        if (!$transaction) {
            throw new \RuntimeException("Transaction not found: {$transactionId}");
        }
        
        // Archive logic here
        return true;
    }
}
```

**RetryPolicyService** (`src/.../Import/Services/Posting/RetryPolicyService.php`):

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting;

use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IRetryPolicy;

class RetryPolicyService implements IRetryPolicy
{
    private const MAX_RETRIES = 3;
    private const BASE_DELAY_SECONDS = 5;
    
    public function calculateBackoff(int $attemptNumber): int
    {
        if ($attemptNumber >= self::MAX_RETRIES) {
            return -1; // No more retries
        }
        
        // Exponential backoff: 5s, 10s, 20s
        return self::BASE_DELAY_SECONDS * (2 ** $attemptNumber);
    }
}
```

**Acceptance Criteria**:
- [ ] Archive creates immutable snapshot
- [ ] Retry policy calculates exponential backoff
- [ ] Max retries enforced (3 attempts)
- [ ] No modifications allowed after archiving

---

## Phase 3: Integration & Testing (1 day)

### Task 3.1: Repository Integration (0.3 points)

**Objective**: Extend DuplicateTransactionRepository with posting queries

**Additions to IDuplicateTransactionRepository.php**:

```php
public function findApprovedForPosting(int $limit = 1000): array;
public function getAuditHistory(int $transactionId): array;
public function recordPosting(int $transactionId, string $status, ?string $glId): bool;
```

**Acceptance Criteria**:
- [ ] findApprovedForPosting() returns only APPROVED transactions
- [ ] Audit history includes all posting attempts
- [ ] recordPosting() atomically updates audit_log

---

### Task 3.2: Unit Test Suite (0.4 points)

**Test Coverage**:
- PostingOrchestratorService: 5 test cases
- PostingEligibilityService: 7 test cases  
- RetryPolicyService: 4 test cases
- DTOs: 6 test cases
- **Total: 22 unit tests**

**Target**: 100% code coverage for all new services

**Acceptance Criteria**:
- [ ] All 22 unit tests passing
- [ ] 100% line coverage
- [ ] 100% branch coverage
- [ ] All mocks verified and cleaned up

---

### Task 3.3: Integration Test Suite (0.2 points)

**Test Cases** (`tests/integration/PostingWorkflowIntegrationTest.php`):

```php
public function test_approved_transaction_posted_to_gl(): void
public function test_rejected_transaction_archived(): void
public function test_investigate_transaction_held(): void
public function test_gl_error_triggers_retry(): void
public function test_max_retries_escalate_to_admin(): void
```

**Acceptance Criteria**:
- [ ] 5 integration tests passing
- [ ] Database state verified after each scenario
- [ ] GL API mocked with realistic responses
- [ ] Audit trail follows expected sequence

---

### Task 3.4: Performance Test (0.1 points)

**Test**: Can process 1,000 transactions in <5 minutes

**Acceptance Criteria**:
- [ ] 1,000 transaction batch completes <300 seconds
- [ ] Memory usage <256MB
- [ ] No N+1 query problems detected

---

## Implementation Sequence

### Day 1 (7 hours)
- **Morning (0-2h)**: Task 1.1 - Database migrations
- **Morning (2-4h)**: Task 1.2 & 1.3 - DTOs and interfaces
- **Afternoon (4-6h)**: Task 2.1 - PostingEligibilityService + tests
- **Evening (6-7h)**: Begin Task 2.2 - PostingOrchestratorService structure

### Day 2 (7 hours)
- **Morning (0-3h)**: Complete Task 2.2 & 2.3 - Core services
- **Afternoon (3-5h)**: Task 3.1 - Repository integration
- **Evening (5-7h)**: Task 3.2 - Unit test suite

### Day 3 (6 hours)
- **Morning (0-3h)**: Task 3.3 - Integration tests
- **Mid (3-5h)**: Task 3.4 - Performance testing
- **Afternoon (5-6h)**: Final validation and documentation

---

## Acceptance Criteria - All Stories

| Criterion | Acceptance | Evidence |
|-----------|-----------|----------|
| **Functionality** | ✅ Ready | Architecture + PRD specify full feature set |
| **Database Schema** | ✅ Ready | Migrations prepared (3 of 3) |
| **Service Layer** | ⏳ In Progress | PostingOrchestratorService skeleton ready |
| **API Contracts** | ✅ Ready | Interface definitions complete |
| **Error Handling** | ✅ Ready | Exception hierarchy defined |
| **Unit Tests** | ⏳ To Do | 22 test cases planned, 0 written |
| **Integration Tests** | ⏳ To Do | 5 scenarios planned |
| **Documentation** | ✅ Ready | PRD, architecture, test strategy complete |
| **Code Quality** | ✅ Ready | SOLID principles, TDD approach |
| **Performance** | ⏳ To Do | Load test planned, to be executed |

---

## Risk Mitigation

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| GL API timeout | High | High | Exponential backoff retry + timeout config |
| Database lock contentions | Medium | High | Optimistic locking + transaction isolation |
| Incomplete review decisions | Medium | Medium | Hardcoded validation + tests |
| Archive data too large | Low | High | Regular archival cleanup job (future story) |

---

## Definition of Done (Story 4)

- [x] All planning documents complete (PRD, test strategy, architecture, implementation plan)
- [ ] Database migrations execute without errors
- [ ] All 22 unit tests passing with 100% coverage
- [ ] All 5 integration tests passing
- [ ] Performance test confirms <5 minute batch processing
- [ ] Code review passed by senior developer
- [ ] Documentation updated (README, API docs)
- [ ] Git commit with feature branch merged to main
- [ ] Deployed to staging environment
- [ ] UAT passed by Finance Manager

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial implementation plan |

---

## Appendix: Quick Reference

**Key Metrics**
- Story Points: 13
- Tasks: 6 major + subtasks
- Test Cases: 22 unit + 5 integration + 1 performance
- New Files: 12-15 PHP files
- Modified Files: 2-3 (existing repos)

**Commands to Run**

```bash
# Run migrations
php migrations/001_create_posting_audit_log.php
php migrations/002_create_rejected_archive.php
php migrations/003_add_audit_triggers.php

# Run unit tests
php ./vendor/bin/phpunit tests/unit --filter PostingTest --colors=never

# Run integration tests
php ./vendor/bin/phpunit tests/integration/PostingWorkflowIntegrationTest --colors=never

# Check coverage
php ./vendor/bin/phpunit tests/unit --coverage-html coverage/
```

**Key Interfaces to Implement**
- [x] IPostingOrchestratorService
- [x] IPostingEligibilityService
- [x] IGLPostingService (abstraction for GL API)
- [x] IArchiveService
- [x] IRetryPolicy

**Database Tables to Create**
- [x] posting_audit_log
- [x] bi_transactions_rejected_archive

**Domain Events to Publish** (via IEventPublisher)
- PostingCompleted
- PostingFailed
- ArchivingCompleted

