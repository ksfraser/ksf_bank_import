<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\DTOs;

use DateTimeImmutable;

/**
 * Immutable request object for posting a transaction.
 * Created once and never modified - used when preparing a duplicate for copying to main table.
 */
final class PostingRequestDTO
{
    public readonly int $duplicateId;
    public readonly string $transactionCode;
    public readonly float $amount;
    public readonly string $decisionStatus;
    public readonly string $decidedBy;
    public readonly DateTimeImmutable $decidedAt;
    public readonly string $decisionReason;

    public function __construct(
        int $duplicateId,
        string $transactionCode,
        float $amount,
        string $decisionStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt,
        string $decisionReason = ''
    ) {
        $this->duplicateId = $duplicateId;
        $this->transactionCode = $transactionCode;
        $this->amount = $amount;
        $this->decisionStatus = $decisionStatus;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $decidedAt;
        $this->decisionReason = $decisionReason;
    }

    /**
     * Create from array (typically from database query).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            intval($data['duplicate_id']),
            strval($data['transaction_code']),
            floatval($data['amount']),
            strval($data['decision_status']),
            strval($data['decided_by']),
            new DateTimeImmutable($data['decided_at']),
            strval($data['decision_reason'] ?? '')
        );
    }

    /**
     * Serialize to array for logging or transmission.
     */
    public function toArray(): array
    {
        return [
            'duplicate_id' => $this->duplicateId,
            'transaction_code' => $this->transactionCode,
            'amount' => $this->amount,
            'decision_status' => $this->decisionStatus,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt->format(\DateTime::ATOM),
            'decision_reason' => $this->decisionReason,
        ];
    }
}
