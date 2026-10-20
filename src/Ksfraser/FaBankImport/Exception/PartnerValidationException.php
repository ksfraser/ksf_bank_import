<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * @BABOK RTM: FR-050 / ARCH-003; file: Exception/PartnerValidationException.php; coverage: docs/TestCases/ (file rollup)
 * PartnerValidationException - Thrown when partner data validation fails
 * 
 * Used when partner data violates constraints or business rules.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerValidationException extends PartnerException
{
    /** @BABOK FR-050 / ARCH-003 — method: emptyName */
    public static function emptyName(): self
    {
        return new self('Partner name cannot be empty');
    }

    /** @BABOK FR-050 / ARCH-003 — method: invalidOccurrenceCount */
    public static function invalidOccurrenceCount(int $count): self
    {
        return new self(
            sprintf('Invalid occurrence count: %d (must be >= 0)', $count)
        );
    }

    /** @BABOK FR-050 / ARCH-003 — method: nameExceedsMaxLength */
    public static function nameExceedsMaxLength(int $length): self
    {
        return new self(
            sprintf('Partner name exceeds maximum length of 255 characters (got %d)', $length)
        );
    }
}
