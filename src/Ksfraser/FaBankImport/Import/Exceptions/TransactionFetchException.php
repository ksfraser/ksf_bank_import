<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a transaction cannot be fetched from the database.
 * 
 * Examples:
 * - Transaction ID not found
 * - Database query failed
 * - Returned data is malformed
 */
class TransactionFetchException extends ImportException
{
    protected bool $recoverable = true;

    /**
     * Create exception for transaction not found.
     *
     * @param int $transactionId
     * @return self
     */
    public static function notFound(int $transactionId): self
    {
        return new self(
            "Transaction {$transactionId} not found in database",
            3001,
            null,
            ['transaction_id' => $transactionId]
        );
    }

    /**
     * Create exception for database query failure.
     *
     * @param string $query
     * @param string $error
     * @return self
     */
    public static function queryFailed(string $query, string $error): self
    {
        return new self(
            "Transaction fetch query failed: {$error}",
            3002,
            null,
            ['query' => $query, 'database_error' => $error],
            false
        );
    }

    /**
     * Create exception for malformed result data.
     *
     * @param int $transactionId
     * @param array $data
     * @param string $reason
     * @return self
     */
    public static function malformedData(int $transactionId, array $data, string $reason): self
    {
        return new self(
            "Transaction {$transactionId}: Malformed data returned - {$reason}",
            3003,
            null,
            ['transaction_id' => $transactionId, 'data' => $data, 'reason' => $reason]
        );
    }
}
