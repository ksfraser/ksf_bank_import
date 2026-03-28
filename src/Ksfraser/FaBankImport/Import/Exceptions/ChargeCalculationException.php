<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when charge calculation encounters issues.
 * 
 * Examples:
 * - Invalid collection IDs
 * - Charge amounts don't match
 * - Database query fails during calculation
 */
class ChargeCalculationException extends ImportException
{
    protected bool $recoverable = true;

    /**
     * Create exception for invalid collection IDs.
     *
     * @param string $collectionIds
     * @param string $reason
     * @return self
     */
    public static function invalidCollectionIds(string $collectionIds, string $reason): self
    {
        return new self(
            "Invalid collection IDs: {$reason}",
            7001,
            null,
            ['collection_ids' => $collectionIds, 'reason' => $reason]
        );
    }

    /**
     * Create exception for amount mismatch.
     *
     * @param float $expected
     * @param float $calculated
     * @param int $transactionId
     * @return self
     */
    public static function amountMismatch(float $expected, float $calculated, int $transactionId): self
    {
        return new self(
            "Charge calculation mismatch for transaction {$transactionId}: expected {$expected}, calculated {$calculated}",
            7002,
            null,
            ['expected' => $expected, 'calculated' => $calculated, 'transaction_id' => $transactionId]
        );
    }

    /**
     * Create exception for calculation query failure.
     *
     * @param int $transactionId
     * @param string $error
     * @return self
     */
    public static function queryFailed(int $transactionId, string $error): self
    {
        return new self(
            "Charge calculation query failed for transaction {$transactionId}: {$error}",
            7003,
            null,
            ['transaction_id' => $transactionId, 'database_error' => $error],
            false
        );
    }
}
