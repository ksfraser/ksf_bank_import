<?php
namespace Ksfraser\FaBankImport\Import\Services\Review\DTOs;

/**
 * DuplicateReviewDisplay DTO: immutable view of a duplicate transaction for dashboard display.
 *
 * Represents a duplicate transaction with its review status and decision information.
 * Used to display duplicates on the admin dashboard with all necessary UI context.
 *
 * @package Ksfraser\FaBankImport\Import\Services\Review\DTOs
 */
final readonly class DuplicateReviewDisplay
{
    public function __construct(
        public int $id,
        public string $transactionCode,
        public float $amount,
        public string $transDate,
        public string $decisionStatus, // PENDING, APPROVED, REJECTED, INVESTIGATE
        public ?string $decidedBy, // email/username of reviewer
        public ?string $decidedAt, // ISO datetime
        public ?string $reason,
        public float $confidenceScore, // 0-100
        public int $matchedTransactionCount,
        public string $createdAt, // ISO datetime
    ) {
    }

    /**
     * Create DTO from array (typically from database query or request)
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? throw new \InvalidArgumentException('Missing: id')),
            transactionCode: (string) ($data['transaction_code'] ?? throw new \InvalidArgumentException('Missing: transaction_code')),
            amount: (float) ($data['amount'] ?? throw new \InvalidArgumentException('Missing: amount')),
            transDate: (string) ($data['trans_date'] ?? throw new \InvalidArgumentException('Missing: trans_date')),
            decisionStatus: (string) ($data['decision_status'] ?? throw new \InvalidArgumentException('Missing: decision_status')),
            decidedBy: isset($data['decided_by']) ? (string) $data['decided_by'] : null,
            decidedAt: isset($data['decided_at']) ? (string) $data['decided_at'] : null,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            confidenceScore: (float) ($data['confidence_score'] ?? throw new \InvalidArgumentException('Missing: confidence_score')),
            matchedTransactionCount: (int) ($data['matched_transaction_count'] ?? throw new \InvalidArgumentException('Missing: matched_transaction_count')),
            createdAt: (string) ($data['created_at'] ?? throw new \InvalidArgumentException('Missing: created_at')),
        );
    }

    /**
     * Serialize DTO to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transactionCode,
            'amount' => $this->amount,
            'trans_date' => $this->transDate,
            'decision_status' => $this->decisionStatus,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt,
            'reason' => $this->reason,
            'confidence_score' => $this->confidenceScore,
            'matched_transaction_count' => $this->matchedTransactionCount,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Check if this duplicate is still pending review
     */
    public function isPending(): bool
    {
        return $this->decisionStatus === 'PENDING';
    }

    /**
     * Check if this duplicate has been reviewed
     */
    public function isReviewed(): bool
    {
        return $this->decisionStatus !== 'PENDING';
    }
}
