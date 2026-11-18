<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a referenced entity is not found in the database
 * 
 * Example: Trying to review a transaction that doesn't exist
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
class EntityNotFoundException extends DuplicateReviewException
{
    /**
     * Factory method for missing transaction
     * 
     * @param int $transactionId The transaction ID that wasn't found
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function transactionNotFound(int $transactionId): self {
        return new self(
            message: "Duplicate transaction with ID {$transactionId} not found",
            code: 1005
        );
    }

    /**
     * Factory method for missing duplicate record
     *
     * @param int $duplicateId The duplicate ID that wasn't found
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function forDuplicate(int $duplicateId): self {
        return self::transactionNotFound($duplicateId);
    }

    /**
     * Factory method for missing archive record
     *
     * @param int $archiveId The archive ID that wasn't found
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function forArchive(int $archiveId): self {
        return new self(
            message: "Archive record with ID {$archiveId} not found",
            code: 1006
        );
    }
}
