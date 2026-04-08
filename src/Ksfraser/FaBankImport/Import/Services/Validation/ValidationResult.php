<?php

namespace Ksfraser\FaBankImport\Import\Services\Validation;

/**
 * Validation result object (Notification Pattern)
 *
 * Encapsulates the outcome of a validation operation including:
 * - Validation status (valid/invalid)
 * - All validation errors encountered
 * - Error count for logging
 *
 * Immutable: Errors are collected during validation and passed to constructor
 * Benefits over separate state:
 * - Result is self-contained, easier to pass around
 * - No need to call getErrors() separately
 * - Thread-safe (immutable)
 * - Testable without managing service state
 */
final class ValidationResult
{
    /**
     * Whether validation passed
     *
     * @var bool
     */
    private bool $isValid;

    /**
     * Validation errors encountered (empty if valid)
     *
     * @var array<int, string>
     */
    private array $errors;

    /**
     * Create validation result
     *
     * @param bool $isValid True if validation passed
     * @param array<int, string> $errors Validation errors (empty array if valid)
     */
    public function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    /**
     * Check if validation passed
     *
     * @return bool True if valid
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get all validation errors
     *
     * @return array<int, string> Array of error messages (empty if valid)
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error count
     *
     * Useful for logging: "Validation failed with 3 errors"
     *
     * @return int Number of validation errors
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get first error message (or null)
     *
     * Useful for simple error reporting
     *
     * @return string|null First error message or null if valid
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Get errors as formatted string
     *
     * @param string $separator Line separator (default: newline)
     * @return string Formatted error message or empty string if valid
     */
    public function getErrorsAsString(string $separator = PHP_EOL): string
    {
        return empty($this->errors) ? '' : implode($separator, $this->errors);
    }

    /**
     * Create valid result
     *
     * Factory method for readable code: `ValidationResult::valid()`
     *
     * @return self
     */
    public static function valid(): self
    {
        return new self(true, []);
    }

    /**
     * Create invalid result with errors
     *
     * Factory method for readable code
     *
     * @param array<int, string> $errors Validation errors
     * @return self
     */
    public static function invalid(array $errors): self
    {
        return new self(false, $errors);
    }
}
