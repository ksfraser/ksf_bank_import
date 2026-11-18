<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when decision reason/notes validation fails
 * 
 * Example: Trying to reject without providing a reason
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
class InvalidReasonException extends DuplicateReviewException
{
    /**
     * Factory method for required reason
     * 
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function reasonRequired(): self {
        return new self(
            message: 'A reason is required for reject decisions',
            code: 1002
        );
    }

    /**
     * Factory method for exceeding length limit
     * 
     * @param int $length Actual length
     * @param int $maxLength Maximum allowed length
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function reasonTooLong(int $length, int $maxLength): self {
        return new self(
            message: "Reason exceeds maximum length of {$maxLength} characters (provided: {$length})",
            code: 1003
        );
    }

    /**
     * Factory method for invalid characters
     * 
     * @return self
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function invalidCharacters(): self {
        return new self(
            message: 'Reason contains invalid characters',
            code: 1004
        );
    }
}
