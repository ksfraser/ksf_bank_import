<?php

namespace Ksfraser\FaBankImport\Import\Results;

/**
 * Base result class for operation outcomes.
 * 
 * Captures whether an operation succeeded, any errors/warnings,
 * and the result data.
 */
class OperationResult
{
    /**
     * @var bool
     */
    private bool $success;

    /**
     * @var array Error messages
     */
    private array $errors = [];

    /**
     * @var array Warning messages
     */
    private array $warnings = [];

    /**
     * @var mixed Operation result data
     */
    private mixed $data = null;

    /**
     * @var array Additional context
     */
    private array $context = [];

    /**
     * Create a successful operation result.
     *
     * @param mixed $data
     * @param array $context
     * @return self
     */
    public static function success(mixed $data = null, array $context = []): self
    {
        $result = new self();
        $result->success = true;
        $result->data = $data;
        $result->context = $context;
        return $result;
    }

    /**
     * Create a failed operation result.
     *
     * @param string $error
     * @param mixed $data
     * @param array $context
     * @return self
     */
    public static function failure(string $error, mixed $data = null, array $context = []): self
    {
        $result = new self();
        $result->success = false;
        $result->errors[] = $error;
        $result->data = $data;
        $result->context = $context;
        return $result;
    }

    /**
     * Check if operation succeeded.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if operation failed.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Add an error message.
     *
     * @param string $error
     * @return $this
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        if ($this->success) {
            $this->success = false;
        }
        return $this;
    }

    /**
     * Get all error messages.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message.
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Add a warning message.
     *
     * @param string $warning
     * @return $this
     */
    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    /**
     * Get all warning messages.
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get operation result data.
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Set operation result data.
     *
     * @param mixed $data
     * @return $this
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get context value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getContext(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->context;
        }
        return $this->context[$key] ?? $default;
    }

    /**
     * Add context value.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
}
