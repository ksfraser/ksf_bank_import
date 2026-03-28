<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a transaction fails during processing.
 * 
 * Examples:
 * - Database insert/update fails
 * - GL posting fails
 * - Contact linking fails unexpectedly
 */
class TransactionProcessingException extends ImportException
{
    protected bool $recoverable = false;

    /**
     * Create exception for database operation failure.
     *
     * @param string $operation INSERT|UPDATE|DELETE
     * @param int $transactionId
     * @param string $error
     * @return self
     */
    public static function databaseOperationFailed(string $operation, int $transactionId, string $error): self
    {
        return new self(
            "Transaction {$transactionId}: {$operation} operation failed - {$error}",
            4001,
            null,
            ['operation' => $operation, 'transaction_id' => $transactionId, 'database_error' => $error],
            false
        );
    }

    /**
     * Create exception for GL posting failure.
     *
     * @param int $transactionId
     * @param string $account
     * @param float $amount
     * @param string $error
     * @return self
     */
    public static function glPostingFailed(int $transactionId, string $account, float $amount, string $error): self
    {
        return new self(
            "Transaction {$transactionId}: GL posting failed for account {$account} ({$amount}) - {$error}",
            4002,
            null,
            ['transaction_id' => $transactionId, 'account' => $account, 'amount' => $amount, 'error' => $error],
            false
        );
    }

    /**
     * Create exception for contact linking failure.
     *
     * @param int $transactionId
     * @param string $reason
     * @return self
     */
    public static function contactLinkingFailed(int $transactionId, string $reason): self
    {
        return new self(
            "Transaction {$transactionId}: Contact linking failed - {$reason}",
            4003,
            null,
            ['transaction_id' => $transactionId, 'reason' => $reason],
            true
        );
    }

    /**
     * Create exception for file operation failure.
     *
     * @param int $fileId
     * @param string $operation
     * @param string $error
     * @return self
     */
    public static function fileOperationFailed(int $fileId, string $operation, string $error): self
    {
        return new self(
            "File {$fileId}: {$operation} failed - {$error}",
            4004,
            null,
            ['file_id' => $fileId, 'operation' => $operation, 'error' => $error],
            false
        );
    }
}
