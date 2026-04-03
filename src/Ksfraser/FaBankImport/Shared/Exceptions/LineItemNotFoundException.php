<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Shared\Exceptions;

/**
 * LineItemNotFoundException - Factory for consistent LineItem not found errors
 * 
 * Creates EntityNotFoundException instances with consistent messages for
 * LineItem lookup failures.
 * 
 * @package Ksfraser\FaBankImport\Shared\Exceptions
 */
final class LineItemNotFoundException
{
    /**
     * Create exception for line item not found by ID
     *
     * @param int $id Line item ID
     * @return EntityNotFoundException
     */
    public static function byId(int $id): EntityNotFoundException
    {
        return new EntityNotFoundException("Line item with ID {$id} not found");
    }

    /**
     * Create exception for line items not found by transaction ID
     *
     * @param int $transactionId Transaction ID
     * @return EntityNotFoundException
     */
    public static function byTransactionId(int $transactionId): EntityNotFoundException
    {
        return new EntityNotFoundException("No line items found for transaction ID {$transactionId}");
    }

    /**
     * Create exception for line items not found by GL account
     *
     * @param int $glAccount GL account number
     * @return EntityNotFoundException
     */
    public static function byGLAccount(int $glAccount): EntityNotFoundException
    {
        return new EntityNotFoundException("No line items found for GL account {$glAccount}");
    }
}

