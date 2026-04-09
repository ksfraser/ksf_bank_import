<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Review;

use DateTimeImmutable;
use DateTime;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

use Ksfraser\FaBankImport\Import\Services\Review\DuplicateReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\ReviewDecision;
use Ksfraser\FaBankImport\Import\Services\Review\Interfaces\IDuplicateReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\Interfaces\IEventPublisher;
use Ksfraser\FaBankImport\Import\Services\Review\Interfaces\ILogger;
use Ksfraser\FaBankImport\Import\Events\DuplicateDecisionMade;
use Ksfraser\FaBankImport\Import\Exceptions\InvalidWorkflowTransitionException;
use Ksfraser\FaBankImport\Import\Exceptions\InvalidReasonException;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;

/**
 * Unit Tests for DuplicateReviewService
 * 
 * These tests verify the core business logic of the review service without
 * touching the database. Repository, event publisher, and logger are mocked.
 */
final class DuplicateReviewServiceTest extends TestCase
{
    private DuplicateReviewService $service;
    private MockObject|IDuplicateTransactionRepository $mockRepository;
    private MockObject|IEventPublisher $mockEventPublisher;
    private MockObject|ILogger $mockLogger;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(IDuplicateTransactionRepository::class);
        $this->mockEventPublisher = $this->createMock(IEventPublisher::class);
        $this->mockLogger = $this->createMock(ILogger::class);

        $this->service = new DuplicateReviewService(
            $this->mockRepository,
            $this->mockEventPublisher,
            $this->mockLogger
        );
    }

    /**
     * TC-UT-001: Approve a PENDING duplicate transaction
     * 
     * Given: A transaction in PENDING status
     * When: Service approves it with decision details
     * Then: Status updated, audit record created, event published, DTO returned
     */
    public function testApproveValidPending(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(1);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(
                fn($tx) => $tx->getStatus() === 'APPROVED'
            ));

        $this->mockRepository
            ->expects($this->once())
            ->method('auditDecision')
            ->with(
                1,
                'APPROVED',
                'user_123',
                $this->isInstanceOf(DateTimeImmutable::class),
                'same amount, same date',
                null
            );

        $this->mockEventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(DuplicateDecisionMade::class));

        // Act
        $decision = $this->service->approve(
            $transaction,
            'user_123',
            'same amount, same date'
        );

        // Assert
        $this->assertInstanceOf(ReviewDecision::class, $decision);
        $this->assertEquals(1, $decision->transactionId);
        $this->assertEquals('APPROVED', $decision->decisionStatus);
        $this->assertEquals('user_123', $decision->decidedBy);
        $this->assertNotNull($decision->decidedAt);
        $this->assertEquals('same amount, same date', $decision->reason);
        $this->assertNull($decision->notes);
    }

    /**
     * TC-UT-002: Reject a PENDING duplicate transaction
     * 
     * Given: A transaction in PENDING status
     * When: Service rejects it with required reason
     * Then: Status updated to REJECTED with audit trail
     */
    public function testRejectValidPending(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(2);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(
                fn($tx) => $tx->getStatus() === 'REJECTED'
            ));

        $this->mockRepository
            ->expects($this->once())
            ->method('auditDecision')
            ->with(
                2,
                'REJECTED',
                'user_456',
                $this->isInstanceOf(DateTimeImmutable::class),
                'false positive - different accounts',
                null
            );

        $this->mockEventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(DuplicateDecisionMade::class));

        // Act
        $decision = $this->service->reject(
            $transaction,
            'user_456',
            'false positive - different accounts'
        );

        // Assert
        $this->assertEquals('REJECTED', $decision->decisionStatus);
        $this->assertEquals('false positive - different accounts', $decision->reason);
    }

    /**
     * TC-UT-003: Mark PENDING duplicate for investigation
     * 
     * Given: A transaction in PENDING status
     * When: Service marks for investigation with optional notes
     * Then: Status updated to INVESTIGATE
     */
    public function testInvestigateValidPending(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(3);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(
                fn($tx) => $tx->getStatus() === 'INVESTIGATE'
            ));

        $this->mockRepository
            ->expects($this->once())
            ->method('auditDecision')
            ->with(
                3,
                'INVESTIGATE',
                'user_789',
                $this->isInstanceOf(DateTimeImmutable::class),
                null,
                'Need to check invoice dates more carefully'
            );

        $this->mockEventPublisher->expects($this->once())->method('publish');

        // Act
        $decision = $this->service->investigate(
            $transaction,
            'user_789',
            'Need to check invoice dates more carefully'
        );

        // Assert
        $this->assertEquals('INVESTIGATE', $decision->decisionStatus);
        $this->assertEquals('Need to check invoice dates more carefully', $decision->notes);
    }

    /**
     * TC-UT-004: Prevent approving already-approved transaction
     * 
     * Given: A transaction with APPROVED status
     * When: Service attempts to approve again
     * Then: InvalidWorkflowTransitionException thrown, no changes
     */
    public function testApproveAlreadyApproved(): void
    {
        // Arrange
        $transaction = $this->createTransactionWithStatus(4, 'APPROVED');

        $this->mockRepository->expects($this->never())->method('update');
        $this->mockRepository->expects($this->never())->method('auditDecision');
        $this->mockEventPublisher->expects($this->never())->method('publish');

        // Act & Assert
        $this->expectException(InvalidWorkflowTransitionException::class);
        $this->service->approve($transaction, 'user_123', null);
    }

    /**
     * TC-UT-005: Prevent rejecting without reason
     * 
     * Given: A transaction in PENDING status
     * When: Service attempts to reject with empty reason
     * Then: InvalidReasonException thrown
     */
    public function testRejectWithoutReason(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(5);

        $this->mockRepository->expects($this->never())->method('auditDecision');

        // Act & Assert
        $this->expectException(InvalidReasonException::class);
        $this->service->reject($transaction, 'user_123', '');
    }

    /**
     * TC-UT-006: Allow investigate with null notes
     * 
     * Given: A transaction in PENDING status
     * When: Service investigates without notes (null is acceptable)
     * Then: Decision recorded successfully with null notes
     */
    public function testInvestigateWithNullNotes(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(6);

        $this->mockRepository
            ->expects($this->once())
            ->method('auditDecision')
            ->with(
                6,
                'INVESTIGATE',
                'user_999',
                $this->isInstanceOf(DateTimeImmutable::class),
                null,
                null
            );

        // Act
        $decision = $this->service->investigate($transaction, 'user_999', null);

        // Assert
        $this->assertNull($decision->notes);
    }

    /**
     * TC-UT-007: Handle concurrent approve requests (last write wins)
     * 
     * Given: Same transaction approved twice rapidly by different users
     * When: Both approve requests are processed
     * Then: Second approval updates the record, both audited
     */
    public function testConcurrentApproveSameTransaction(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(7);

        $this->mockRepository->expects($this->exactly(2))->method('update');
        $this->mockRepository->expects($this->exactly(2))->method('auditDecision');
        $this->mockEventPublisher->expects($this->exactly(2))->method('publish');

        // Act - First approval
        $decision1 = $this->service->approve($transaction, 'user_alice', 'first decision');
        
        // Update transaction to APPROVED for second attempt
        $approvedTx = $this->createTransactionWithStatus(7, 'APPROVED');
        
        // Second approval should fail (workflow violation)
        $this->expectException(InvalidWorkflowTransitionException::class);
        $this->service->approve($approvedTx, 'user_bob', 'second decision');
    }

    /**
     * TC-UT-008: Event contains correct decision data
     * 
     * Given: A decision is approved
     * When: Service publishes the event
     * Then: Event contains all relevant decision data
     */
    public function testEventPublishedWithCorrectData(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(8);
        $capturedEvent = null;

        $this->mockEventPublisher
            ->method('publish')
            ->willReturnCallback(function ($event) use (&$capturedEvent) {
                $capturedEvent = $event;
            });

        // Act
        $this->service->approve($transaction, 'user_qa', 'matches exactly');

        // Assert
        $this->assertInstanceOf(DuplicateDecisionMade::class, $capturedEvent);
        $this->assertEquals(8, $capturedEvent->transactionId);
        $this->assertEquals('PENDING', $capturedEvent->previousStatus);
        $this->assertEquals('APPROVED', $capturedEvent->newStatus);
        $this->assertEquals('user_qa', $capturedEvent->decidedBy);
        $this->assertEquals('matches exactly', $capturedEvent->reason);
    }

    /**
     * TC-UT-009: Audit record created in database
     * 
     * Given: A decision is recorded
     * When: Audit is requested
     * Then: Audit method called with all required columns
     */
    public function testAuditRecordCreated(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(9);

        $auditCalled = false;
        $this->mockRepository
            ->method('auditDecision')
            ->willReturnCallback(function (
                $txId,
                $status,
                $decidedBy,
                $decidedAt,
                $reason,
                $notes
            ) use (&$auditCalled) {
                $auditCalled = true;
                // Verify all parameters received
                $this->assertEquals(9, $txId);
                $this->assertEquals('APPROVED', $status);
                $this->assertEquals('auditor_1', $decidedBy);
                $this->assertInstanceOf(DateTimeImmutable::class, $decidedAt);
            });

        // Act
        $this->service->approve($transaction, 'auditor_1', 'audit test reason');

        // Assert
        $this->assertTrue($auditCalled);
    }

    /**
     * TC-UT-010: Throw exception for non-existent transaction
     * 
     * Given: Non-existent transaction ID
     * When: Service attempts to review it
     * Then: EntityNotFoundException thrown
     */
    public function testMissingTransactionThrowsException(): void
    {
        // Arrange  
        $mockTransaction = $this->createMock(DuplicateTransaction::class);
        $mockTransaction->expects($this->any())->method('getStatus')->willReturn('PENDING');

        // Simulate repository throwing exception
        $this->mockRepository
            ->method('update')
            ->willThrowException(EntityNotFoundException::transactionNotFound(999));

        // Act & Assert
        $this->expectException(EntityNotFoundException::class);
        $this->service->approve($mockTransaction, 'user_123', null);
    }

    /**
     * TC-UT-011: Sanitize reason field to prevent injection
     * 
     * Given: Reason contains HTML/special characters
     * When: Decision is recorded
     * Then: Reason is sanitized before audit
     */
    public function testReasonFieldSanitized(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(11);
        $capturedReason = null;

        $this->mockRepository
            ->method('auditDecision')
            ->willReturnCallback(function ($txId, $status, $by, $at, $reason) use (&$capturedReason) {
                $capturedReason = $reason;
            });

        $maliciousReason = '<script>alert("xss")</script> same amounts';

        // Act
        $this->service->approve($transaction, 'user_sec', $maliciousReason);

        // Assert - reason should be sanitized
        $this->assertNotNull($capturedReason);
        $this->assertStringNotContainsString('<script>', $capturedReason);
    }

    /**
     * TC-UT-012: Timestamp is UTC
     * 
     * Given: A decision is recorded
     * When: Decision timestamp is captured
     * Then: Timestamp is in UTC timezone
     */
    public function testTimestampIsUTC(): void
    {
        // Arrange
        $transaction = $this->createValidPendingTransaction(12);
        $capturedTime = null;

        $this->mockRepository
            ->method('auditDecision')
            ->willReturnCallback(function ($txId, $status, $by, $time) use (&$capturedTime) {
                $capturedTime = $time;
            });

        // Act
        $decision = $this->service->approve($transaction, 'timezone_test', null);

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $decision->decidedAt);
        $this->assertEquals('UTC', $decision->decidedAt->getTimezone()->getName());
    }

    // ===== Helper Methods =====

    /**
     * Create a mock transaction in PENDING status
     * 
     * @param int $id Transaction ID
     * @return DuplicateTransaction
     */
    private function createValidPendingTransaction(int $id): DuplicateTransaction
    {
        return $this->createTransactionWithStatus($id, 'PENDING');
    }

    /**
     * Create a mock transaction with specific status
     * 
     * @param int $id Transaction ID
     * @param string $status Transaction status
     * @return DuplicateTransaction
     */
    private function createTransactionWithStatus(int $id, string $status): DuplicateTransaction
    {
        // Create a minimal real DuplicateTransaction instance for testing
        // We use reflection to bypass constructor for simplicity in unit tests
        $reflection = new \ReflectionClass(DuplicateTransaction::class);
        $transaction = $reflection->newInstanceWithoutConstructor();
        
        // Set required properties via reflection
        $idProp = $reflection->getProperty('duplicateId');
        $idProp->setAccessible(true);
        $idProp->setValue($transaction, $id);
        
        $statusProp = $reflection->getProperty('decisionStatus');
        $statusProp->setAccessible(true);
        $statusProp->setValue($transaction, $status);
        
        // Set other required properties to avoid null pointer exceptions
        $transactionCodeProp = $reflection->getProperty('transactionCode');
        $transactionCodeProp->setAccessible(true);
        $transactionCodeProp->setValue($transaction, "TX-$id");
        
        $transDatProp = $reflection->getProperty('transDate');
        $transDatProp->setAccessible(true);
        $transDatProp->setValue($transaction, new DateTime());
        
        $amountProp = $reflection->getProperty('amount');
        $amountProp->setAccessible(true);
        $amountProp->setValue($transaction, 100.00);
        
        $counterpartyNameProp = $reflection->getProperty('counterpartyName');
        $counterpartyNameProp->setAccessible(true);
        $counterpartyNameProp->setValue($transaction, 'Test Partner');
        
        $bankAccountIdProp = $reflection->getProperty('bankAccountId');
        $bankAccountIdProp->setAccessible(true);
        $bankAccountIdProp->setValue($transaction, 1);
        
        $createdAtProp = $reflection->getProperty('createdAt');
        $createdAtProp->setAccessible(true);
        $createdAtProp->setValue($transaction, new DateTime());
        
        $updatedAtProp = $reflection->getProperty('updatedAt');
        $updatedAtProp->setAccessible(true);
        $updatedAtProp->setValue($transaction, new DateTime());
        
        return $transaction;
    }
}
