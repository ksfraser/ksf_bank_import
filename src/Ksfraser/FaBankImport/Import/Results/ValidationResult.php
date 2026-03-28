<?php

namespace Ksfraser\FaBankImport\Import\Results;

/**
 * Result of validation operations.
 * 
 * Contains detailed validation rules, violations, and field-level errors.
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
     * Create a passed validation result.
     *
     * @return self
     */
    public static function valid(): self
    {
        $result = new self();
        $result->success = true;
        return $result;
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
        $result->success = false;
        $result->errors[] = $generalError;
        return $result;
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->success;
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
        $this->success = false;
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
        return count($this->errors) + count($this->fieldErrors);
    }
}
