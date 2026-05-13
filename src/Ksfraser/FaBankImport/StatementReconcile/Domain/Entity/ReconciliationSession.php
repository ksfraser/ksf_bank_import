<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Entity;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;

/**
 * Aggregate root representing a single reconciliation attempt for one statement.
 *
 * Lifecycle:
 *   1. Created in 'pending' status by the MatchingEngine.
 *   2. User reviews and adjusts pairs via addPair() / removePair().
 *   3. User approves → approve() transitions to 'approved' and records who/when.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Entity
 * @author  Kevin Fraser
 */
final class ReconciliationSession
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';

    /** @var int|null Null until persisted. */
    private $id;

    /** @var int FK to bi_statement_ocr.id */
    private $statementOcrId;

    /** @var MatchedPair[] */
    private $matchedPairs;

    /** @var string[] Statement line IDs with no bank match. */
    private $unmatchedStatementLineIds;

    /** @var int[] FA bank transaction IDs not matched to any statement line. */
    private $unmatchedBankTransactionIds;

    /** @var string One of STATUS_PENDING | STATUS_APPROVED. */
    private $status;

    /** @var int|null FA user ID who approved. */
    private $persistedByUserId;

    /** @var \DateTimeImmutable|null */
    private $persistedAt;

    /**
     * Private – use factory methods.
     *
     * @param int|null      $id
     * @param int           $statementOcrId
     * @param MatchedPair[] $matchedPairs
     * @param string[]      $unmatchedStatementLineIds
     * @param int[]         $unmatchedBankTransactionIds
     * @param string        $status
     * @param int|null      $persistedByUserId
     * @param \DateTimeImmutable|null $persistedAt
     */
    private function __construct(
        ?int $id,
        int $statementOcrId,
        array $matchedPairs,
        array $unmatchedStatementLineIds,
        array $unmatchedBankTransactionIds,
        string $status,
        ?int $persistedByUserId,
        ?\DateTimeImmutable $persistedAt
    ) {
        $this->id                          = $id;
        $this->statementOcrId              = $statementOcrId;
        $this->matchedPairs                = $matchedPairs;
        $this->unmatchedStatementLineIds   = $unmatchedStatementLineIds;
        $this->unmatchedBankTransactionIds = $unmatchedBankTransactionIds;
        $this->status                      = $status;
        $this->persistedByUserId           = $persistedByUserId;
        $this->persistedAt                 = $persistedAt;
    }

    /**
     * Create a fresh pending session from matching engine output.
     *
     * @param int           $statementOcrId
     * @param MatchedPair[] $matchedPairs
     * @param string[]      $unmatchedStatementLineIds
     * @param int[]         $unmatchedBankTransactionIds
     * @return self
     */
    public static function createPending(
        int $statementOcrId,
        array $matchedPairs,
        array $unmatchedStatementLineIds,
        array $unmatchedBankTransactionIds
    ): self {
        return new self(
            null,
            $statementOcrId,
            $matchedPairs,
            array_values($unmatchedStatementLineIds),
            array_values($unmatchedBankTransactionIds),
            self::STATUS_PENDING,
            null,
            null
        );
    }

    /**
     * Reconstitute from a database row + decoded JSON sub-arrays.
     *
     * @param array $row            DB row with id, statement_ocr_id, status, persisted_by_user_id, persisted_at
     * @param array $pairs          Decoded matched_pairs_json
     * @param array $unmatchedLines Decoded unmatched_statement_line_ids
     * @param array $unmatchedBank  Decoded unmatched_bank_transaction_ids
     * @return self
     */
    public static function fromDatabase(
        array $row,
        array $pairs,
        array $unmatchedLines,
        array $unmatchedBank
    ): self {
        $matchedPairs = array_map(
            static function (array $p): MatchedPair { return MatchedPair::fromArray($p); },
            $pairs
        );

        $persistedAt = null;
        if (!empty($row['persisted_at'])) {
            $persistedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['persisted_at']);
            if ($persistedAt === false) {
                $persistedAt = null;
            }
        }

        return new self(
            (int)    $row['id'],
            (int)    $row['statement_ocr_id'],
            $matchedPairs,
            array_values(array_map('strval', $unmatchedLines)),
            array_values(array_map('intval', $unmatchedBank)),
            (string) ($row['status'] ?? self::STATUS_PENDING),
            isset($row['persisted_by_user_id']) ? (int) $row['persisted_by_user_id'] : null,
            $persistedAt
        );
    }

    // -------------------------------------------------------------------------
    // Domain behaviour
    // -------------------------------------------------------------------------

    /**
     * Add or replace a matched pair for a given statement line.
     *
     * Also removes the line from unmatched statement lines if present.
     *
     * @param MatchedPair $pair
     * @return void
     */
    public function addPair(MatchedPair $pair): void
    {
        $this->assertPending('addPair');

        // Remove existing pair for the same statement line, if any.
        $this->matchedPairs = array_values(
            array_filter(
                $this->matchedPairs,
                static function (MatchedPair $p) use ($pair): bool {
                    return $p->getStatementLineId() !== $pair->getStatementLineId();
                }
            )
        );

        $this->matchedPairs[] = $pair;

        // Remove from unmatched if it was there.
        $this->unmatchedStatementLineIds = array_values(
            array_filter(
                $this->unmatchedStatementLineIds,
                static function (string $id) use ($pair): bool {
                    return $id !== $pair->getStatementLineId();
                }
            )
        );
    }

    /**
     * Remove a matched pair by statement line ID, placing the line back into unmatched.
     *
     * @param string $statementLineId
     * @return void
     */
    public function removePair(string $statementLineId): void
    {
        $this->assertPending('removePair');

        $removed = false;
        $this->matchedPairs = array_values(
            array_filter(
                $this->matchedPairs,
                static function (MatchedPair $p) use ($statementLineId, &$removed): bool {
                    if ($p->getStatementLineId() === $statementLineId) {
                        $removed = true;
                        return false;
                    }
                    return true;
                }
            )
        );

        if ($removed && !in_array($statementLineId, $this->unmatchedStatementLineIds, true)) {
            $this->unmatchedStatementLineIds[] = $statementLineId;
        }
    }

    /**
     * Approve the session, locking further changes.
     *
     * @param int $userId FA user ID performing the approval.
     * @return void
     */
    public function approve(int $userId): void
    {
        if ($this->status === self::STATUS_APPROVED) {
            throw ReconciliationException::alreadyApproved($this->status);
        }

        $this->status            = self::STATUS_APPROVED;
        $this->persistedByUserId = $userId;
        $this->persistedAt       = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatementOcrId(): int
    {
        return $this->statementOcrId;
    }

    /** @return MatchedPair[] */
    public function getMatchedPairs(): array
    {
        return $this->matchedPairs;
    }

    /** @return string[] */
    public function getUnmatchedStatementLineIds(): array
    {
        return $this->unmatchedStatementLineIds;
    }

    /** @return int[] */
    public function getUnmatchedBankTransactionIds(): array
    {
        return $this->unmatchedBankTransactionIds;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getPersistedByUserId(): ?int
    {
        return $this->persistedByUserId;
    }

    public function getPersistedAt(): ?\DateTimeImmutable
    {
        return $this->persistedAt;
    }

    // -------------------------------------------------------------------------
    // Persistence helpers
    // -------------------------------------------------------------------------

    public function toStorageArray(): array
    {
        $pairs = array_map(static function (MatchedPair $p): array {
            return $p->toArray();
        }, $this->matchedPairs);

        return [
            'statement_ocr_id'              => $this->statementOcrId,
            'matched_pairs_json'             => json_encode($pairs),
            'unmatched_statement_line_ids'  => json_encode($this->unmatchedStatementLineIds),
            'unmatched_bank_transaction_ids'=> json_encode($this->unmatchedBankTransactionIds),
            'status'                        => $this->status,
            'persisted_by_user_id'          => $this->persistedByUserId,
            'persisted_at'                  => $this->persistedAt !== null
                ? $this->persistedAt->format('Y-m-d H:i:s')
                : null,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param string $operation
     */
    private function assertPending(string $operation): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw ReconciliationException::forReason(
                "Cannot call {$operation}() on a session with status '{$this->status}'"
            );
        }
    }
}
