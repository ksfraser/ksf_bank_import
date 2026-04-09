<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review;

use DateTimeImmutable;
use DateTimeZone;

use Ksfraser\FaBankImport\Import\Services\Review\Interfaces\IDuplicateReviewService;
use Ksfraser\FaBankImport\Import\Services\Review\Interfaces\IEventPublisher;
use Ksfraser\FaBankImport\Import\Services\Review\Interfaces\ILogger;
use Ksfraser\FaBankImport\Import\Events\DuplicateDecisionMade;
use Ksfraser\FaBankImport\Import\Exceptions\InvalidWorkflowTransitionException;
use Ksfraser\FaBankImport\Import\Exceptions\InvalidReasonException;
use Ksfraser\FaBankImport\Import\Exceptions\RepositoryException;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;

/**
 * DuplicateReviewService - Service for recording reviewer decisions on duplicate transactions
 * 
 * This service handles the core business logic for recording decisions (approve/reject/investigate)
 * on detected duplicate transactions. It enforces workflow rules, creates audit trails, and
 * publishes domain events for downstream integration.
 * 
 * @implements IDuplicateReviewService
 */
final class DuplicateReviewService implements IDuplicateReviewService
{
    /**
     * Valid workflow state transitions
     * 
     * @var array<string, list<string>>
     */
    private const VALID_TRANSITIONS = [
        'PENDING' => ['APPROVED', 'REJECTED', 'INVESTIGATE'],
        'INVESTIGATE' => ['APPROVED', 'REJECTED'],
    ];

    /**
     * Maximum length for reason/notes fields
     */
    private const MAX_REASON_LENGTH = 500;

    /**
     * Initialize service with required dependencies
     * 
     * @param IDuplicateTransactionRepository $repository For persisting decisions and audit
     * @param IEventPublisher $eventPublisher For publishing decision events
     * @param ILogger $logger For operation logging
     */
    public function __construct(
        private readonly IDuplicateTransactionRepository $repository,
        private readonly IEventPublisher $eventPublisher,
        private readonly ILogger $logger,
    ) {
    }

    /**
     * Record an approval decision for a duplicate transaction
     * 
     * @param DuplicateTransaction $transaction
     * @param string $decidedBy
     * @param string|null $reason
     * 
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     * @throws RepositoryException
     */
    public function approve(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $reason = null
    ): ReviewDecision {
        // Validate workflow transition
        if ($transaction->getDecisionStatus() !== 'PENDING') {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $transaction->getDecisionStatus(),
                'APPROVED'
            );
        }

        // Sanitize and prepare decision data
        $sanitizedReason = $this->sanitizeText($reason);
        $decidedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            // Update entity state
            $transaction->approve($decidedBy, $sanitizedReason);

            // Persist to database
            $this->repository->update($transaction);

            // Create audit record
            $this->repository->auditDecision(
                $transaction->getDuplicateId(),
                'APPROVED',
                $decidedBy,
                $decidedAt,
                $sanitizedReason,
                null
            );

            // Publish event
            $event = new DuplicateDecisionMade(
                transactionId: $transaction->getDuplicateId(),
                previousStatus: 'PENDING',
                newStatus: 'APPROVED',
                decidedBy: $decidedBy,
                decidedAt: $decidedAt,
                reason: $sanitizedReason,
            );
            $this->publishEvent($event);

            // Log success
            $this->logger->info(
                'Duplicate transaction approved',
                [
                    'transaction_id' => $transaction->getDuplicateId(),
                    'decided_by' => $decidedBy,
                ]
            );

            // Return confirmation DTO
            return new ReviewDecision(
                transactionId: $transaction->getDuplicateId(),
                decisionStatus: 'APPROVED',
                decidedBy: $decidedBy,
                decidedAt: $decidedAt,
                reason: $sanitizedReason,
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to approve duplicate transaction',
                ['error' => $e->getMessage(), 'transaction_id' => $transaction->getDuplicateId()]
            );
            throw RepositoryException::operationFailed('approve', $e->getMessage(), $e);
        }
    }

    /**
     * Record a rejection decision for a duplicate transaction
     * 
     * @param DuplicateTransaction $transaction
     * @param string $decidedBy
     * @param string $reason Required reason for rejection
     * 
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     * @throws InvalidReasonException
     * @throws RepositoryException
     */
    public function reject(
        DuplicateTransaction $transaction,
        string $decidedBy,
        string $reason
    ): ReviewDecision {
        // Validate reason is provided (required for reject)
        if (empty(trim($reason))) {
            throw InvalidReasonException::reasonRequired();
        }

        // Validate workflow transition  
        if ($transaction->getDecisionStatus() !== 'PENDING') {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $transaction->getDecisionStatus(),
                'REJECTED'
            );
        }

        // Sanitize and prepare decision data
        $sanitizedReason = $this->sanitizeText($reason);
        $decidedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            // Update entity state
            $transaction->reject($decidedBy, $sanitizedReason);

            // Persist to database
            $this->repository->update($transaction);

            // Create audit record
            $this->repository->auditDecision(
                $transaction->getDuplicateId(),
                'REJECTED',
                $decidedBy,
                $decidedAt,
                $sanitizedReason,
                null
            );

            // Publish event
            $event = new DuplicateDecisionMade(
                transactionId: $transaction->getDuplicateId(),
                previousStatus: 'PENDING',
                newStatus: 'REJECTED',
                decidedBy: $decidedBy,
                decidedAt: $decidedAt,
                reason: $sanitizedReason,
            );
            $this->publishEvent($event);

            // Log success
            $this->logger->info(
                'Duplicate transaction rejected',
                [
                    'transaction_id' => $transaction->getDuplicateId(),
                    'decided_by' => $decidedBy,
                ]
            );

            // Return confirmation DTO
            return new ReviewDecision(
                transactionId: $transaction->getDuplicateId(),
                decisionStatus: 'REJECTED',
                decidedBy: $decidedBy,
                decidedAt: $decidedAt,
                reason: $sanitizedReason,
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to reject duplicate transaction',
                ['error' => $e->getMessage(), 'transaction_id' => $transaction->getDuplicateId()]
            );
            throw RepositoryException::operationFailed('reject', $e->getMessage(), $e);
        }
    }

    /**
     * Mark a duplicate transaction for further investigation
     * 
     * @param DuplicateTransaction $transaction
     * @param string $decidedBy
     * @param string|null $notes
     * 
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     * @throws RepositoryException
     */
    public function investigate(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision {
        // Validate workflow transition
        if ($transaction->getDecisionStatus() !== 'PENDING') {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $transaction->getDecisionStatus(),
                'INVESTIGATE'
            );
        }

        // Sanitize and prepare decision data
        $sanitizedNotes = $this->sanitizeText($notes);
        $decidedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            // Update entity state
            $transaction->flagForInvestigation($decidedBy, $sanitizedNotes ?? '');

            // Persist to database
            $this->repository->update($transaction);

            // Create audit record (notes stored in second field)
            $this->repository->auditDecision(
                $transaction->getDuplicateId(),
                'INVESTIGATE',
                $decidedBy,
                $decidedAt,
                null,
                $sanitizedNotes
            );

            // Publish event
            $event = new DuplicateDecisionMade(
                transactionId: $transaction->getDuplicateId(),
                previousStatus: 'PENDING',
                newStatus: 'INVESTIGATE',
                decidedBy: $decidedBy,
                decidedAt: $decidedAt,
                reason: null,
            );
            $this->publishEvent($event);

            // Log success
            $this->logger->info(
                'Duplicate transaction marked for investigation',
                [
                    'transaction_id' => $transaction->getDuplicateId(),
                    'decided_by' => $decidedBy,
                ]
            );

            // Return confirmation DTO
            return new ReviewDecision(
                transactionId: $transaction->getDuplicateId(),
                decisionStatus: 'INVESTIGATE',
                decidedBy: $decidedBy,
                decidedAt: $decidedAt,
                notes: $sanitizedNotes,
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to mark duplicate for investigation',
                ['error' => $e->getMessage(), 'transaction_id' => $transaction->getDuplicateId()]
            );
            throw RepositoryException::operationFailed('investigate', $e->getMessage(), $e);
        }
    }

    /**
     * Validate that a workflow state transition is allowed
     * 
     * @param string $currentStatus
     * @param string $requestedDecision
     * 
     * @return void
     * @throws InvalidWorkflowTransitionException
     */
    private function validateWorkflowTransition(string $currentStatus, string $requestedDecision): void
    {
        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $currentStatus,
                $requestedDecision
            );
        }

        if (!in_array($requestedDecision, self::VALID_TRANSITIONS[$currentStatus], true)) {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $currentStatus,
                $requestedDecision
            );
        }
    }

    /**
     * Sanitize text input to prevent injection attacks and enforce length limits
     * 
     * @param string|null $text
     * 
     * @return string|null
     * @throws InvalidReasonException
     */
    private function sanitizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $trimmed = trim($text);

        if (strlen($trimmed) > self::MAX_REASON_LENGTH) {
            throw InvalidReasonException::reasonTooLong(strlen($trimmed), self::MAX_REASON_LENGTH);
        }

        // Basic sanitization: escape HTML entities and remove null bytes
        $sanitized = str_replace("\0", '', htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8'));

        return empty($sanitized) ? null : $sanitized;
    }

    /**
     * Publish event with error handling
     * 
     * If event publishing fails, log the error but don't fail the business operation.
     * The decision is already persisted in the database.
     * 
     * @param DuplicateDecisionMade $event
     * 
     * @return void
     */
    private function publishEvent(DuplicateDecisionMade $event): void
    {
        try {
            $this->eventPublisher->publish($event);
        } catch (\Throwable $e) {
            // Log the error but don't propagate - decision is already committed
            $this->logger->error(
                'Failed to publish decision event',
                ['error' => $e->getMessage(), 'event_type' => $event::class]
            );
        }
    }
}
