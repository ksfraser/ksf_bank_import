<?php
namespace Ksfraser\FaBankImport\Import\Services\Review\DTOs;

/**
 * DuplicateReviewDisplay DTO: immutable view of a duplicate transaction for dashboard display.
 *
 * Represents a duplicate transaction with its review status and decision information.
 * Used to display duplicates on the admin dashboard with all necessary UI context.
 *
 * @package Ksfraser\FaBankImport\Import\Services\Review\DTOs
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
final class DuplicateReviewDisplay
{
    /** @var int */
    public $id;
    /** @var string */
    public $transactionCode;
    /** @var float */
    public $amount;
    /** @var string */
    public $transDate;
    /** @var string */
    public $decisionStatus; // PENDING, APPROVED, REJECTED, INVESTIGATE
    /** @var ?string */
    public $decidedBy; // email/username of reviewer
    /** @var ?string */
    public $decidedAt; // ISO datetime
    /** @var ?string */
    public $reason;
    /** @var float */
    public $confidenceScore; // 0-100
    /** @var int */
    public $matchedTransactionCount;
    /** @var string */
    public $createdAt; // ISO datetime
    /** @var ?string */
    public $counterpartyName = null; // optional display name

    /**
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function __construct(
        int $id,
        string $transactionCode,
        float $amount,
        string $transDate,
        string $decisionStatus,
        ?string $decidedBy,
        ?string $decidedAt,
        ?string $reason,
        float $confidenceScore,
        int $matchedTransactionCount,
        string $createdAt,
        ?string $counterpartyName = null
    ) {
        $this->id = $id;
        $this->transactionCode = $transactionCode;
        $this->amount = $amount;
        $this->transDate = $transDate;
        $this->decisionStatus = $decisionStatus;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $decidedAt;
        $this->reason = $reason;
        $this->confidenceScore = $confidenceScore;
        $this->matchedTransactionCount = $matchedTransactionCount;
        $this->createdAt = $createdAt;
        $this->counterpartyName = $counterpartyName;
    }

    /**
     * Create DTO from array (typically from database query or request)
     *
     * @param array<string, mixed> $data
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function fromArray(array $data): self
    {
        $requiredKeys = [
            'id',
            'transaction_code',
            'amount',
            'trans_date',
            'decision_status',
            'confidence_score',
            'matched_transaction_count',
            'created_at',
        ];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new \InvalidArgumentException('Missing: ' . $key);
            }
        }

        return new self(
            id: (int) ($data['id']),
            transactionCode: (string) ($data['transaction_code']),
            amount: (float) ($data['amount']),
            transDate: (string) ($data['trans_date']),
            decisionStatus: (string) ($data['decision_status']),
            decidedBy: isset($data['decided_by']) ? (string) $data['decided_by'] : null,
            decidedAt: isset($data['decided_at']) ? (string) $data['decided_at'] : null,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            confidenceScore: (float) ($data['confidence_score']),
            matchedTransactionCount: (int) ($data['matched_transaction_count']),
            createdAt: (string) ($data['created_at']),
            counterpartyName: isset($data['counterparty_name']) ? (string) $data['counterparty_name'] : null,
        );
    }

    /**
     * Serialize DTO to array
     *
     * @return array<string, mixed>
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
            'counterparty_name' => $this->counterpartyName,
        ];
    }

    /**
     * Check if this duplicate is still pending review
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function isPending(): bool
    {
        return $this->decisionStatus === 'PENDING';
    }

    /**
     * Check if this duplicate has been reviewed
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function isReviewed(): bool
    {
        return $this->decisionStatus !== 'PENDING';
    }
}
