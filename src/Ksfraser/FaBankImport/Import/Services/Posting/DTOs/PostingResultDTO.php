<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\DTOs;

use DateTimeImmutable;

/**
 * Immutable result object representing posting outcome.
 * Returned after attempting to copy a duplicate to the main transactions table.
 */
final class PostingResultDTO
{
    public readonly int $duplicateId;
    public readonly string $status; // POSTED, SKIPPED, HELD, ERROR
    public readonly ?int $mainTransactionId; // ID from bi_transactions after successful copy
    public readonly ?string $errorMessage;
    public readonly ?DateTimeImmutable $postedAt;
    public readonly int $retryCount;

    public function __construct(
        int $duplicateId,
        string $status,
        ?int $mainTransactionId = null,
        ?string $errorMessage = null,
        ?DateTimeImmutable $postedAt = null,
        int $retryCount = 0
    ) {
        $this->duplicateId = $duplicateId;
        $this->status = $status;
        $this->mainTransactionId = $mainTransactionId;
        $this->errorMessage = $errorMessage;
        $this->postedAt = $postedAt;
        $this->retryCount = $retryCount;
    }

    /**
     * Factory: Transaction successfully copied to main table.
     */
    public static function posted(int $duplicateId, int $mainTransactionId): self
    {
        return new self(
            $duplicateId,
            'POSTED',
            $mainTransactionId,
            null,
            new DateTimeImmutable(),
            0
        );
    }

    /**
     * Factory: Transaction skipped (rejected/investigate - not copied).
     */
    public static function skipped(int $duplicateId, string $reason = ''): self
    {
        return new self(
            $duplicateId,
            'SKIPPED',
            null,
            $reason,
            null,
            0
        );
    }

    /**
     * Factory: Transaction copy failed with error.
     */
    public static function error(int $duplicateId, string $message, int $retryCount = 0): self
    {
        return new self(
            $duplicateId,
            'ERROR',
            null,
            $message,
            null,
            $retryCount
        );
    }

    /**
     * Factory: Transaction held pending manual review.
     */
    public static function held(int $duplicateId, string $reason = ''): self
    {
        return new self(
            $duplicateId,
            'HELD',
            null,
            $reason,
            null,
            0
        );
    }

    /**
     * Recreate from audit log array.
     */
    public static function fromArray(array $data): self
    {
        $postedAt = null;
        if (isset($data['posted_at']) && !empty($data['posted_at'])) {
            if ($data['posted_at'] instanceof DateTimeImmutable) {
                $postedAt = $data['posted_at'];
            } else {
                $postedAt = new DateTimeImmutable($data['posted_at']);
            }
        }

        return new self(
            intval($data['duplicate_id']),
            strval($data['posted_status']),
            isset($data['main_transaction_id']) ? intval($data['main_transaction_id']) : null,
            $data['error_message'] ?? null,
            $postedAt,
            intval($data['retry_count'] ?? 0)
        );
    }

    /**
     * Serialize to array for audit logging or API response.
     */
    public function toArray(): array
    {
        return [
            'duplicate_id' => $this->duplicateId,
            'main_transaction_id' => $this->mainTransactionId,
            'posted_status' => $this->status,
            'posted_at' => $this->postedAt ? $this->postedAt->format(\DateTime::ATOM) : null,
            'error_message' => $this->errorMessage,
            'retry_count' => $this->retryCount,
        ];
    }

    /**
     * Check if posting was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'POSTED';
    }

    /**
     * Check if posting failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'ERROR';
    }

    /**
     * Check if transaction was skipped (rejected or investigate).
     */
    public function isSkipped(): bool
    {
        return $this->status === 'SKIPPED';
    }

    /**
     * Check if transaction is held for review.
     */
    public function isHeld(): bool
    {
        return $this->status === 'HELD';
    }
}
