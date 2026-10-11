<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Exception;

use RuntimeException;

/**
 * Thrown when an OCR operation on a statement fails or returns invalid data.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Exception
 * @author  Kevin Fraser
 */
class StatementOcrException extends RuntimeException
{
    /**
     * @param string $reason Human-readable reason for failure.
     * @return self
     */
    public static function forReason(string $reason): self
    {
        return new self('Statement OCR failed: ' . $reason);
    }

    /**
     * @param string $field Field that was missing in the parsed result.
     * @return self
     */
    public static function missingField(string $field): self
    {
        return new self('Statement OCR result is missing required field: ' . $field);
    }
}
