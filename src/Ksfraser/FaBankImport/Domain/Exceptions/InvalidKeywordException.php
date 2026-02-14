<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :InvalidKeywordException [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for InvalidKeywordException.
 */
namespace Ksfraser\FaBankImport\Domain\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a keyword is invalid
 *
 * @package Ksfraser\FaBankImport\Domain\Exceptions
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class InvalidKeywordException extends InvalidArgumentException
{
    /**
     * Create exception for empty keyword
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self('Keyword cannot be empty');
    }

    /**
     * Create exception for keyword too short
     *
     * @param string $keyword
     * @param int    $minLength
     *
     * @return self
     */
    public static function tooShort(string $keyword, int $minLength): self
    {
        return new self(
            sprintf(
                'Keyword "%s" is too short (minimum %d characters)',
                $keyword,
                $minLength
            )
        );
    }

    /**
     * Create exception for keyword too long
     *
     * @param string $keyword
     * @param int    $maxLength
     *
     * @return self
     */
    public static function tooLong(string $keyword, int $maxLength): self
    {
        return new self(
            sprintf(
                'Keyword "%s" is too long (maximum %d characters)',
                substr($keyword, 0, 50) . '...',
                $maxLength
            )
        );
    }

    /**
     * Create exception for numeric-only keyword
     *
     * @param string $keyword
     *
     * @return self
     */
    public static function numericOnly(string $keyword): self
    {
        return new self(
            sprintf('Keyword "%s" cannot be purely numeric', $keyword)
        );
    }

    /**
     * Create exception for stopword
     *
     * @param string $keyword
     *
     * @return self
     */
    public static function isStopword(string $keyword): self
    {
        return new self(
            sprintf('Keyword "%s" is a stopword and cannot be used', $keyword)
        );
    }
}
