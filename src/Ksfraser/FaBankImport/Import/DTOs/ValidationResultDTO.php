<?php

namespace Ksfraser\FaBankImport\Import\DTOs;

/**
 * Data transfer object for validation results
 *
 * Contains the outcome of validating parsed data against business rules.
 * Allows validation to proceed even with warnings, blocking only on errors.
 *
 * Immutable - use create() factory or builder methods for construction.
 */
final class ValidationResultDTO
{
    /** @var array<int, string> List of error messages that block import */
    private array $errors;

    /** @var array<int, string> List of warning messages that don't block */
    private array $warnings;

    /** @var array<string, array<int, string>> Map of rule => violations */
    private array $ruleViolations;

    /** @var bool Whether validation passed (no errors) */
    private bool $valid;

    /**
     * Create validation result DTO
     *
     * @param bool $valid Whether validation passed
     * @param array<int, string> $errors Blocking errors
     * @param array<int, string> $warnings Non-blocking warnings
     * @param array<string, array<int, string>> $ruleViolations Rule violations map
     */
    private function __construct(
        bool $valid,
        array $errors = [],
        array $warnings = [],
        array $ruleViolations = []
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors); // Re-index
        $this->warnings = array_values($warnings);
        $this->ruleViolations = $ruleViolations;
    }

    /**
     * Create successful validation result
     *
     * @param array<int, string> $warnings Optional warnings
     * @return self
     */
    public static function success(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    /**
     * Create failed validation result
     *
     * @param array<int, string> $errors List of errors
     * @param array<int, string> $warnings Optional warnings
     * @param array<string, array<int, string>> $ruleViolations Optional rule violations
     * @return self
     */
    public static function failure(
        array $errors = [],
        array $warnings = [],
        array $ruleViolations = []
    ): self {
        return new self(false, $errors, $warnings, $ruleViolations);
    }

    /**
     * Create result with single error
     *
     * @param string $error The error message
     * @return self
     */
    public static function error(string $error): self
    {
        return new self(false, [$error]);
    }

    /**
     * Check if validation passed (no errors)
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get all error messages
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error count
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get all warning messages
     *
     * @return array<int, string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get warning count
     *
     * @return int
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Get rule violations map
     *
     * @return array<string, array<int, string>>
     */
    public function getRuleViolations(): array
    {
        return $this->ruleViolations;
    }

    /**
     * Check if specific rule was violated
     *
     * @param string $ruleName The rule name to check
     * @return bool
     */
    public function hasRuleViolation(string $ruleName): bool
    {
        return isset($this->ruleViolations[$ruleName]);
    }

    /**
     * Convert to array for storage/serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'ruleViolations' => $this->ruleViolations,
            'errorCount' => count($this->errors),
            'warningCount' => count($this->warnings),
        ];
    }
}
