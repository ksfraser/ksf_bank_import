<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

use Ksfraser\Exceptions\Domain\InvalidRepositoryStateException;

/**
 * TransferMatch - Immutable entity for matched transfers between accounts
 * 
 * Represents a matching relationship between two internal bank transfers (same amount, opposite DC).
 * Used for reconciliation of transfers between FA accounts.
 * Immutable after creation.
 * 
 * Invariants:
 * - Both transaction IDs must be > 0 and different
 * - sourceTransactionId and targetTransactionId must be from same transfer pair
 * - matchStatus tracks reconciliation state
 * 
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @stable - Part of Shared Kernel API
 */
final class TransferMatch
{
    private int $id;
    private int $sourceTransactionId;
    private int $targetTransactionId;
    private int $matchStatus;
    private string $matchReason;
    private ?int $faTransNo;
    private ?int $faTransType;

    /**
     * Private constructor - use factory methods instead
     */
    private function __construct(
        int $sourceTransactionId,
        int $targetTransactionId
    ) {
        if ($sourceTransactionId <= 0 || $targetTransactionId <= 0) {
            throw InvalidRepositoryStateException::stateFailed('Transaction IDs must be > 0');
        }
        if ($sourceTransactionId === $targetTransactionId) {
            throw InvalidRepositoryStateException::selfReferencingNotAllowed('match', $sourceTransactionId);
        }

        $this->id = 0;
        $this->sourceTransactionId = $sourceTransactionId;
        $this->targetTransactionId = $targetTransactionId;
        $this->matchStatus = 0;
        $this->matchReason = '';
        $this->faTransNo = null;
        $this->faTransType = null;
    }

    /**
     * Create a new transfer match
     */
    public static function create(
        int $sourceTransactionId,
        int $targetTransactionId
    ): self {
        return new self($sourceTransactionId, $targetTransactionId);
    }

    /**
     * Recreate match from database row
     */
    public static function fromDatabase(array $row): self {
        $match = new self(
            (int)($row['source_transaction_id'] ?? (int)($row['sourceTransactionId'] ?? 0)),
            (int)($row['target_transaction_id'] ?? (int)($row['targetTransactionId'] ?? 0))
        );

        $match->id = (int)($row['id'] ?? 0);
        $match->matchStatus = (int)($row['match_status'] ?? 0);
        $match->matchReason = (string)($row['match_reason'] ?? '');
        $match->faTransNo = isset($row['fa_trans_no']) ? (int)$row['fa_trans_no'] : null;
        $match->faTransType = isset($row['fa_trans_type']) ? (int)$row['fa_trans_type'] : null;

        return $match;
    }

    // Getters only - no setters (immutable)

    public function getId(): int { return $this->id; }
    public function getSourceTransactionId(): int { return $this->sourceTransactionId; }
    public function getTargetTransactionId(): int { return $this->targetTransactionId; }
    public function getMatchStatus(): int { return $this->matchStatus; }
    public function getMatchReason(): string { return $this->matchReason; }
    public function getFATransNo(): ?int { return $this->faTransNo; }
    public function getFATransType(): ?int { return $this->faTransType; }

    /**
     * Check if match is confirmed
     */
    public function isConfirmed(): bool {
        return $this->matchStatus > 0;
    }

    /**
     * Check if match has FA journal entry
     */
    public function hasJournalEntry(): bool {
        return $this->faTransNo !== null && $this->faTransType !== null;
    }

    /**
     * Export to database-ready array
     */
    public function toDatabase(): array {
        return [
            'id' => $this->id,
            'source_transaction_id' => $this->sourceTransactionId,
            'target_transaction_id' => $this->targetTransactionId,
            'match_status' => $this->matchStatus,
            'match_reason' => $this->matchReason,
            'fa_trans_no' => $this->faTransNo,
            'fa_trans_type' => $this->faTransType,
        ];
    }
}
