<?php

namespace Ksfraser\FaBankImport\Import\Results;

use DateTime;

/**
 * Result of validation operations.
 * 
 * Contains detailed validation rules, violations, and field-level errors.
 * Supports both field-level and general validation errors/warnings.
 */
class ValidationResult extends OperationResult
{
    /**
     * @var array Field-level error messages
     */
    private array $fieldErrors = [];

    /**
     * @var array Validation rules that were checked
     */
    private array $checkedRules = [];

    /**
     * @var array General validation errors (non-field-level)
     */
    private array $generalErrors = [];

    /**
     * @var array Validation warnings (non-blocking)
     */
    private array $generalWarnings = [];

    /**
     * @var array Summary of rules (rule => pass/fail/warning)
     */
    private array $rulesSummary = [];

    /**
     * @var DateTime Timestamp when validation occurred
     */
    private DateTime $validatedAt;

    /**
     * Constructor - Initialize with timestamp
     */
    public function __construct()
    {
        $this->validatedAt = new DateTime();
    }

    /**
     * Create a passed validation result.
     *
     * @return self
     */
    public static function valid(): self
    {
        $result = self::success();
        return new self();
    }

    /**
     * Create a failed validation result.
     *
     * @param string $generalError
     * @return self
     */
    public static function invalid(string $generalError): self
    {
        $result = new self();
        $result->generalErrors[] = $generalError;
        return $result;
    }

    /**
     * Create a validation result from errors, warnings, and rules summary
     *
     * @param bool $success Whether validation passed
     * @param array<string> $errors General validation errors
     * @param array<string> $warnings General validation warnings
     * @param array<string, string> $rulesSummary Rules summary (rule name => pass/fail/warning)
     * @return self
     */
    public static function fromValidation(bool $success, array $errors = [], array $warnings = [], array $rulesSummary = []): self
    {
        $result = new self();
        $result->generalErrors = $errors;
        $result->generalWarnings = $warnings;
        $result->rulesSummary = $rulesSummary;
        return $result;
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->generalErrors);
    }

    /**
     * Add a field-level validation error.
     *
     * @param string $field
     * @param string $error
     * @return $this
     */
    public function addFieldError(string $field, string $error): self
    {
        if (!isset($this->fieldErrors[$field])) {
            $this->fieldErrors[$field] = [];
        }
        $this->fieldErrors[$field][] = $error;
        return $this;
    }

    /**
     * Add a general validation error
     *
     * @param string $error
     * @return $this
     */
    public function addError(string $error): self
    {
        $this->generalErrors[] = $error;
        return $this;
    }

    /**
     * Add a validation warning
     *
     * @param string $warning
     * @return $this
     */
    public function addWarning(string $warning): self
    {
        $this->generalWarnings[] = $warning;
        return $this;
    }

    /**
     * Get field-level errors.
     *
     * @param string|null $field If provided, get errors for only this field
     * @return array
     */
    public function getFieldErrors(?string $field = null): array
    {
        if ($field !== null) {
            return $this->fieldErrors[$field] ?? [];
        }
        return $this->fieldErrors;
    }

    /**
     * Get all general validation errors
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->generalErrors;
    }

    /**
     * Check if there are any errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->generalErrors);
    }

    /**
     * Get error count
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return count($this->generalErrors);
    }

    /**
     * Get all validation warnings
     *
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->generalWarnings;
    }

    /**
     * Check if there are any warnings
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->generalWarnings);
    }

    /**
     * Get warning count
     *
     * @return int
     */
    public function getWarningCount(): int
    {
        return count($this->generalWarnings);
    }

    /**
     * Get the rules summary
     *
     * @return array<string, string>
     */
    public function getRulesSummary(): array
    {
        return $this->rulesSummary;
    }

    /**
     * Get validation timestamp
     *
     * @return DateTime
     */
    public function getValidatedAt(): DateTime
    {
        return $this->validatedAt;
    }

    /**
     * Get human-readable validation summary
     *
     * @return string
     */
    public function getSummary(): string
    {
        $status = $this->isValid() ? 'PASSED' : 'FAILED';
        $lines = ["Validation {$status}: " . count($this->rulesSummary) . ' rules checked'];

        if ($this->hasErrors()) {
            $lines[] = $this->getErrorCount() . ' error(s) found:';
            foreach ($this->generalErrors as $error) {
                $lines[] = "  - $error";
            }
        }

        if ($this->hasWarnings()) {
            $lines[] = 'Warnings: ' . $this->getWarningCount() . ' issue(s) found';
            foreach ($this->generalWarnings as $warning) {
                $lines[] = "  - $warning";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Record a validation rule that was checked.
     *
     * @param string $ruleName
     * @param bool $passed
     * @return $this
     */
    public function recordRuleCheck(string $ruleName, bool $passed): self
    {
        $this->checkedRules[$ruleName] = $passed;
        return $this;
    }

    /**
     * Get all checked validation rules.
     *
     * @return array
     */
    public function getCheckedRules(): array
    {
        return $this->checkedRules;
    }

    /**
     * Get count of validation violations.
     *
     * @return int
     */
    public function getViolationCount(): int
    {
        return count($this->generalErrors) + count($this->fieldErrors);
    }
}
