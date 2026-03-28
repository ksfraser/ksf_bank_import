<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a bank transaction fails validation checks.
 * 
 * Examples:
 * - Invalid transaction amount
 * - Missing counterparty
 * - Invalid transaction dates
 * - Same-account transfer (FROM == TO)
 */
class TransactionValidationException extends ImportException
{
    protected bool $recoverable = true;

    /**
     * Create validation exception for invalid amount.
     *
     * @param string|float $amount
     * @param int $transactionId
     * @return self
     */
    public static function invalidAmount(string|float $amount, int $transactionId): self
    {
        return new self(
            "Transaction {$transactionId}: Invalid amount '{$amount}'",
            2001,
            null,
            ['transaction_id' => $transactionId, 'amount' => $amount]
        );
    }

    /**
     * Create validation exception for missing counterparty.
     *
     * @param int $transactionId
     * @return self
     */
    public static function missingCounterparty(int $transactionId): self
    {
        return new self(
            "Transaction {$transactionId}: Missing counterparty information",
            2002,
            null,
            ['transaction_id' => $transactionId],
            true
        );
    }

    /**
     * Create validation exception for same-account transfer (FROM == TO).
     *
     * @param int $transactionId
     * @param int $bankAccountId
     * @return self
     */
    public static function sameAccountTransfer(int $transactionId, int $bankAccountId): self
    {
        return new self(
            "Transaction {$transactionId}: Bank transfer FROM and TO the same account (account_id: {$bankAccountId})",
            2003,
            null,
            ['transaction_id' => $transactionId, 'bank_account_id' => $bankAccountId],
            false
        );
    }

    /**
     * Create validation exception for invalid dates.
     *
     * @param int $transactionId
     * @param string $date
     * @param string $reason
     * @return self
     */
    public static function invalidDate(int $transactionId, string $date, string $reason = ''): self
    {
        return new self(
            "Transaction {$transactionId}: Invalid date '{$date}'" . ($reason ? " ({$reason})" : ''),
            2004,
            null,
            ['transaction_id' => $transactionId, 'date' => $date, 'reason' => $reason]
        );
    }

    /**
     * Create validation exception for duplicate transaction.
     *
     * @param int $transactionId
     * @param string $referenceNumber
     * @param string $reason
     * @return self
     */
    public static function duplicateTransaction(int $transactionId, string $referenceNumber, string $reason = ''): self
    {
        return new self(
            "Transaction {$transactionId}: Duplicate (ref: {$referenceNumber})" . ($reason ? " - {$reason}" : ''),
            2005,
            null,
            ['transaction_id' => $transactionId, 'reference' => $referenceNumber, 'reason' => $reason],
            true
        );
    }
}
