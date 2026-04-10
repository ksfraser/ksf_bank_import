<?php
namespace Ksfraser\FaBankImport\Import\Services\Review\DTOs;

/**
 * ReviewDecisionRequest DTO: immutable request for admin review decisions.
 *
 * Represents an admin's decision to approve, reject, or mark for investigation
 * a duplicate transaction. Includes optional reason and all validation.
 *
 * @package Ksfraser\FaBankImport\Import\Services\Review\DTOs
 */
final readonly class ReviewDecisionRequest
{
    private const MAX_REASON_LENGTH = 255;
    private const VALID_DECISIONS = ['APPROVED', 'REJECTED', 'INVESTIGATE'];

    public function __construct(
        public int $duplicateId,
        public string $decision,
        public ?string $reason = null,
    ) {
    }

    /**
     * Create request from array (typically from API request body)
     *
     * @param array<string, mixed> $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['duplicate_id'])) {
            throw new \InvalidArgumentException('Missing required field: duplicate_id');
        }

        $duplicateId = (int) $data['duplicate_id'];
        if ($duplicateId <= 0) {
            throw new \InvalidArgumentException('duplicate_id must be a positive integer');
        }

        if (!isset($data['decision'])) {
            throw new \InvalidArgumentException('Missing required field: decision');
        }

        $decision = (string) $data['decision'];
        if (!in_array($decision, self::VALID_DECISIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid decision: %s. Must be one of: %s',
                $decision,
                implode(', ', self::VALID_DECISIONS)
            ));
        }

        $reason = isset($data['reason']) ? (string) $data['reason'] : null;
        if ($reason !== null && strlen($reason) > self::MAX_REASON_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'Reason exceeds maximum length of %d characters',
                self::MAX_REASON_LENGTH
            ));
        }

        return new self(
            duplicateId: $duplicateId,
            decision: $decision,
            reason: $reason,
        );
    }

    /**
     * Serialize request to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'duplicate_id' => $this->duplicateId,
            'decision' => $this->decision,
            'reason' => $this->reason,
        ];
    }
}
