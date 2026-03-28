<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a referenced bank account cannot be found.
 * 
 * Examples:
 * - Account number not found in FA
 * - Account ID out of range
 * - Account is inactive
 */
class BankAccountNotFoundException extends ImportException
{
    protected bool $recoverable = false;

    /**
     * Create exception for account number not found.
     *
     * @param string $accountNumber
     * @return self
     */
    public static function byAccountNumber(string $accountNumber): self
    {
        return new self(
            "Bank account not found: account number '{$accountNumber}'",
            8001,
            null,
            ['account_number' => $accountNumber]
        );
    }

    /**
     * Create exception for account ID not found.
     *
     * @param int $accountId
     * @return self
     */
    public static function byAccountId(int $accountId): self
    {
        return new self(
            "Bank account not found: account ID {$accountId}",
            8002,
            null,
            ['account_id' => $accountId]
        );
    }

    /**
     * Create exception for inactive account.
     *
     * @param int $accountId
     * @param string $accountNumber
     * @return self
     */
    public static function inactive(int $accountId, string $accountNumber): self
    {
        return new self(
            "Bank account is inactive: {$accountNumber} (ID: {$accountId})",
            8003,
            null,
            ['account_id' => $accountId, 'account_number' => $accountNumber]
        );
    }

    /**
     * Create exception for multiple accounts found.
     *
     * @param string $searchCriteria
     * @param int $count
     * @return self
     */
    public static function ambiguous(string $searchCriteria, int $count): self
    {
        return new self(
            "Ambiguous bank account search: found {$count} matches for '{$searchCriteria}'",
            8004,
            null,
            ['search_criteria' => $searchCriteria, 'count' => $count]
        );
    }
}
