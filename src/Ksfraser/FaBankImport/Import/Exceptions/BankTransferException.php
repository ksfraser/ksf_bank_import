<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when invalid bank transfer scenarios are detected.
 * 
 * Examples:
 * - Transfer FROM account equals TO account
 * - Transfer between unsupported account types
 * - Paired transfer detection issues
 */
class BankTransferException extends ImportException
{
    protected bool $recoverable = false;

    /**
     * Create exception for same-account transfer.
     *
     * @param int $bankAccountId
     * @param int $transactionId
     * @param float $amount
     * @return self
     */
    public static function sameAccount(int $bankAccountId, int $transactionId, float $amount): self
    {
        return new self(
            "Bank transfer error: transferring from and to the same account {$bankAccountId} (transaction {$transactionId}, amount {$amount})",
            5001,
            null,
            ['bank_account_id' => $bankAccountId, 'transaction_id' => $transactionId, 'amount' => $amount],
            false
        );
    }

    /**
     * Create exception for unsupported account types in transfer.
     *
     * @param int $fromAccountId
     * @param int $toAccountId
     * @param string $reason
     * @return self
     */
    public static function unsupportedAccountTypes(int $fromAccountId, int $toAccountId, string $reason): self
    {
        return new self(
            "Bank transfer: Unsupported account types ({$fromAccountId} -> {$toAccountId}) - {$reason}",
            5002,
            null,
            ['from_account_id' => $fromAccountId, 'to_account_id' => $toAccountId, 'reason' => $reason]
        );
    }

    /**
     * Create exception for paired transfer detection failure.
     *
     * @param int $transactionId
     * @param string $reason
     * @return self
     */
    public static function pairDetectionFailed(int $transactionId, string $reason): self
    {
        return new self(
            "Bank transfer: Paired transfer detection failed for transaction {$transactionId} - {$reason}",
            5003,
            null,
            ['transaction_id' => $transactionId, 'reason' => $reason],
            true
        );
    }
}
