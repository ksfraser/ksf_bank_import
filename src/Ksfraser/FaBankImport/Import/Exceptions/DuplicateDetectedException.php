<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Exception thrown when duplicate transactions are detected
 *
 * Indicates that a transaction matching an existing transaction was found,
 * requiring user review for merge or cancellation.
 */
class DuplicateDetectedException extends ImportException
{
    /** @var array<int, array<string, mixed>> List of potential duplicate matches */
    private array $matches;

    /**
     * Create duplicate exception with matching records
     *
     * @param array<int, array<string, mixed>> $matches List of matching transaction records
     * @param string|null $summary Optional summary message
     */
    public function __construct(array $matches = [], ?string $summary = null)
    {
        $this->matches = array_values($matches); // Re-index
        $message = $summary ?? "Duplicate detected: " . count($matches) . " match(es) found";
        parent::__construct($message);
    }

    /**
     * Get all matching duplicate records
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    /**
     * Get count of matches found
     *
     * @return int
     */
    public function getMatchCount(): int
    {
        return count($this->matches);
    }

    /**
     * Create exception for exact duplicate (same transaction ref + amount + date)
     *
     * @param int $existingTransactionId The ID of the existing transaction
     * @param array<string, mixed> $newTransaction The new transaction data
     * @param array<string, mixed> $existingTransaction The existing transaction data
     * @return self
     */
    public static function exactDuplicate(
        int $existingTransactionId,
        array $newTransaction,
        array $existingTransaction
    ): self {
        return new self(
            [
                [
                    'id' => $existingTransactionId,
                    'type' => 'exact',
                    'new_txn' => $newTransaction,
                    'existing_txn' => $existingTransaction,
                ]
            ],
            "Exact duplicate found: transaction {$existingTransactionId}"
        );
    }

    /**
     * Create exception for probable duplicate (similar but not identical)
     *
     * @param array<int, array<string, mixed>> $probableMatches List of probable matches
     * @return self
     */
    public static function probableDuplicate(array $probableMatches): self
    {
        return new self(
            array_map(function ($match) {
                return array_merge($match, ['type' => 'probable']);
            }, $probableMatches),
            "Probable duplicate(s) found: " . count($probableMatches) . " match(es)"
        );
    }
}
