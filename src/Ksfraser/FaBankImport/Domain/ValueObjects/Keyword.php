<?php

namespace Ksfraser\FaBankImport\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing a single keyword
 *
 * Encapsulates keyword text with validation rules for pattern matching.
 *
 * @package Ksfraser\FaBankImport\Domain\ValueObjects
 * @author  Kevin Fraser
 * @version 1.0.0
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class Keyword
{
    /**
     * @var string The keyword text
     */
    private string $text;

    /**
     * @var int Minimum allowed keyword length
     */
    private const MIN_LENGTH = 2;

    /**
     * @var int Maximum allowed keyword length
     */
    private const MAX_LENGTH = 100;

    /**
     * Create a new Keyword value object
     *
     * @param string $text The keyword text (will be normalized)
     *
     * @throws InvalidArgumentException If keyword is invalid
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(string $text)
    {
        $normalized = $this->normalize($text);
        $this->validate($normalized);
        $this->text = $normalized;
    }

    /**
     * Get keyword text
     *
     * @return string
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Get keyword length
     *
     * @return int
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLength(): int
    {
        return strlen($this->text);
    }

    /**
     * Check if keyword is valid (meets length requirements)
     *
     * @return bool
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function isValid(): bool
    {
        $length = $this->getLength();
        return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
    }

    /**
     * Check if this keyword contains another keyword
     *
     * @param Keyword $other
     *
     * @return bool
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function contains(Keyword $other): bool
    {
        return stripos($this->text, $other->text) !== false;
    }

    /**
     * Check if this keyword equals another
     *
     * Case-insensitive comparison
     *
     * @param Keyword $other
     *
     * @return bool
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function equals(Keyword $other): bool
    {
        return strcasecmp($this->text, $other->text) === 0;
    }

    /**
     * Check if keyword matches a stopword pattern
     *
     * @param array<string> $stopwords List of stopwords
     *
     * @return bool
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function isStopword(array $stopwords): bool
    {
        foreach ($stopwords as $stopword) {
            if (strcasecmp($this->text, $stopword) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * String representation
     *
     * @return string
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __toString(): string
    {
        return $this->text;
    }

    /**
     * Normalize keyword text
     *
     * - Trim whitespace
     * - Convert to lowercase
     * - Remove special characters except letters, numbers, hyphens, and spaces
     * - Collapse multiple spaces to single space
     *
     * @param string $text
     *
     * @return string
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function normalize(string $text): string
    {
        // Trim and lowercase
        $normalized = strtolower(trim($text));

        // Remove everything except alphanumeric, hyphens, and spaces
        $normalized = preg_replace('/[^a-z0-9\-\s]/', '', $normalized);

        // Collapse multiple spaces to single space
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Validate keyword
     *
     * @param string $text Normalized keyword text
     *
     * @throws InvalidArgumentException If keyword is invalid
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function validate(string $text): void
    {
        if ($text === '') {
            throw new InvalidArgumentException('Keyword cannot be empty');
        }

        $length = strlen($text);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Keyword too short (min %d chars): "%s"',
                    self::MIN_LENGTH,
                    $text
                )
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Keyword too long (max %d chars): "%s"',
                    self::MAX_LENGTH,
                    substr($text, 0, 50) . '...'
                )
            );
        }

        // Check if keyword is purely numeric (usually not useful)
        if (is_numeric($text)) {
            throw new InvalidArgumentException(
                "Keyword cannot be purely numeric: \"{$text}\""
            );
        }
    }
}
