<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * PartnerValidationException - Thrown when partner data validation fails
 * 
 * Used when partner data violates constraints or business rules.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerValidationException extends PartnerException
{
    public static function emptyName(): self
    {
        return new self('Partner name cannot be empty');
    }

    public static function invalidOccurrenceCount(int $count): self
    {
        return new self(
            sprintf('Invalid occurrence count: %d (must be >= 0)', $count)
        );
    }

    public static function nameExceedsMaxLength(int $length): self
    {
        return new self(
            sprintf('Partner name exceeds maximum length of 255 characters (got %d)', $length)
        );
    }
}
