<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review;

use DateTimeImmutable;

/**
 * ReviewDecision DTO - Immutable data transfer object representing the outcome of a review decision
 * 
 * This DTO is returned by the DuplicateReviewService to communicate the result of
 * recording a decision (approve/reject/investigate) for a duplicate transaction.
 */
final class ReviewDecision
{
    /**
     * @param int $transactionId The ID of the transaction that was reviewed
     * @param string $decisionStatus The new status (APPROVED, REJECTED, INVESTIGATE)
     * @param string $decidedBy The identifier of who made the decision
     * @param DateTimeImmutable $decidedAt The UTC timestamp of the decision
     * @param string|null $reason Optional reason (particularly for rejections)
     * @param string|null $notes Optional notes (particularly for investigations)
     */
    public function __construct(
        public readonly int $transactionId,
        public readonly string $decisionStatus,
        public readonly string $decidedBy,
        public readonly DateTimeImmutable $decidedAt,
        public readonly ?string $reason = null,
        public readonly ?string $notes = null,
    ) {
    }

    /**
     * Convert DTO to array for serialization (e.g., JSON response)
     * 
     * @return array{
     *     transaction_id: int,
     *     decision_status: string,
     *     decided_by: string,
     *     decided_at: string,
     *     reason: string|null,
     *     notes: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'decision_status' => $this->decisionStatus,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }

    /**
     * Create ReviewDecision from array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (int)$data['transaction_id'],
            decisionStatus: $data['decision_status'],
            decidedBy: $data['decided_by'],
            decidedAt: new DateTimeImmutable($data['decided_at'], new \DateTimeZone('UTC')),
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
