<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review;

use DateTimeImmutable;

/**
 * ReviewDecision DTO - Immutable data transfer object representing the outcome of a review decision
 * 
 * This DTO is returned by the DuplicateReviewService to communicate the result of
 * recording a decision (approve/reject/investigate) for a duplicate transaction.
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
final class ReviewDecision
{
    /** @var int */
    public $transactionId;
    /** @var string */
    public $decisionStatus;
    /** @var string */
    public $decidedBy;
    /** @var DateTimeImmutable */
    public $decidedAt;
    /** @var ?string */
    public $reason = null;
    /** @var ?string */
    public $notes = null;

    /**
     * @param int $transactionId The ID of the transaction that was reviewed
     * @param string $decisionStatus The new status (APPROVED, REJECTED, INVESTIGATE)
     * @param string $decidedBy The identifier of who made the decision
     * @param DateTimeImmutable $decidedAt The UTC timestamp of the decision
     * @param string|null $reason Optional reason (particularly for rejections)
     * @param string|null $notes Optional notes (particularly for investigations)
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function __construct(
        int $transactionId,
        string $decisionStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt,
        ?string $reason = null,
        ?string $notes = null
    ) {
        $this->transactionId = $transactionId;
        $this->decisionStatus = $decisionStatus;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $decidedAt;
        $this->reason = $reason;
        $this->notes = $notes;
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
