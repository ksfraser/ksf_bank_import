<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a referenced entity is not found in the database
 * 
 * Example: Trying to review a transaction that doesn't exist
 */
class EntityNotFoundException extends DuplicateReviewException
{
    /**
     * Factory method for missing transaction
     * 
     * @param int $transactionId The transaction ID that wasn't found
     * @return self
     */
    public static function transactionNotFound(int $transactionId): self {
        return new self(
            message: "Duplicate transaction with ID {$transactionId} not found",
            code: 1005
        );
    }
}
