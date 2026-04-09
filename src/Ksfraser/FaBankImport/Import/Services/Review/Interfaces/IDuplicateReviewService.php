<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review\Interfaces;

use Ksfraser\FaBankImport\Import\Services\Review\ReviewDecision;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;

/**
 * Interface for duplicate review service
 * 
 * Defines the contract for recording reviewer decisions on duplicate transactions.
 * Implementations must handle workflow state validation and event publishing.
 */
interface IDuplicateReviewService
{
    /**
     * Record an approval decision for a duplicate transaction
     * 
     * @param DuplicateTransaction $transaction The transaction being reviewed
     * @param string $decidedBy The identifier of who is making the decision
     * @param string|null $reason Optional reason for the approval
     * 
     * @return ReviewDecision DTO containing the decision confirmation
     * 
     * @throws InvalidWorkflowTransitionException If transaction is not in PENDING status
     * @throws EntityNotFoundException If transaction doesn't exist
     * @throws RepositoryException If database operation fails
     */
    public function approve(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $reason = null
    ): ReviewDecision;

    /**
     * Record a rejection decision for a duplicate transaction
     * 
     * @param DuplicateTransaction $transaction The transaction being reviewed
     * @param string $decidedBy The identifier of who is making the decision
     * @param string $reason Required reason explaining the rejection
     * 
     * @return ReviewDecision DTO containing the decision confirmation
     * 
     * @throws InvalidWorkflowTransitionException If transaction is not in valid state
     * @throws InvalidReasonException If reason is empty or invalid
     * @throws EntityNotFoundException If transaction doesn't exist
     * @throws RepositoryException If database operation fails
     */
    public function reject(
        DuplicateTransaction $transaction,
        string $decidedBy,
        string $reason
    ): ReviewDecision;

    /**
     * Mark a duplicate transaction for further investigation
     * 
     * @param DuplicateTransaction $transaction The transaction being reviewed
     * @param string $decidedBy The identifier of who is making the decision
     * @param string|null $notes Optional investigation notes
     * 
     * @return ReviewDecision DTO containing the decision confirmation
     * 
     * @throws InvalidWorkflowTransitionException If transaction is not in PENDING status
     * @throws EntityNotFoundException If transaction doesn't exist
     * @throws RepositoryException If database operation fails
     */
    public function investigate(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision;
}
