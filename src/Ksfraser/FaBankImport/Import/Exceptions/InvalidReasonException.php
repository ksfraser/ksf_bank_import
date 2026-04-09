<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when decision reason/notes validation fails
 * 
 * Example: Trying to reject without providing a reason
 */
class InvalidReasonException extends DuplicateReviewException
{
    /**
     * Factory method for required reason
     * 
     * @return self
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
     */
    public static function invalidCharacters(): self {
        return new self(
            message: 'Reason contains invalid characters',
            code: 1004
        );
    }
}
