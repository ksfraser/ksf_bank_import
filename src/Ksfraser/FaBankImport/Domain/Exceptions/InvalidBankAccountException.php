<?php

/**
 * InvalidBankAccountException.php
 * 
 * Exception thrown when bank account validation fails, such as:
 * - FROM and TO accounts are the same
 * - Account not found
 * - Insufficient funds
 * - Invalid account configuration
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Domain\Exceptions
 */

namespace Ksfraser\FaBankImport\Domain\Exceptions;

/**
 * Exception for invalid bank account operations
 * 
 * Provides static factory methods for creating specific account validation errors,
 * following Domain-Driven Design exception patterns.
 * 
 * @since 1.0.0
 */
class InvalidBankAccountException extends \InvalidArgumentException
{
    /**
     * When FROM and TO accounts are the same in a bank transfer
     * 
     * @param int $accountId The account ID that is both FROM and TO
     * 
     * @return self
     * 
     * @since 1.0.0
     */
    public static function fromAndToAccountsAreSame(int $accountId): self
    {
        return new self(
            "To and From accounts must not be the same account (account {$accountId})"
        );
    }

    /**
     * When a bank account is not found
     * 
     * @param int $accountId The account ID that was not found
     * 
     * @return self
     * 
     * @since 1.0.0
     */
    public static function notFound(int $accountId): self
    {
        return new self(
            "Bank account not found: {$accountId}"
        );
    }

    /**
     * When insufficient funds for a transfer
     * 
     * @param float $required Required amount
     * @param float $available Available balance
     * 
     * @return self
     * 
     * @since 1.0.0
     */
    public static function insufficientFunds(float $required, float $available): self
    {
        return new self(
            "Insufficient funds: required {$required}, available {$available}"
        );
    }

    /**
     * When account is inactive or not available for transfers
     * 
     * @param int $accountId The account ID
     * @param string $reason The reason the account is invalid
     * 
     * @return self
     * 
     * @since 1.0.0
     */
    public static function inactive(int $accountId, string $reason = 'inactive'): self
    {
        return new self(
            "Bank account {$accountId} is {$reason}"
        );
    }
}
