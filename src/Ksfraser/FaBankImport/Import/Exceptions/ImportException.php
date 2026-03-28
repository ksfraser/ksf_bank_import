<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Base exception for all bank import operations.
 * 
 * Provides common handling for recoverable and non-recoverable errors
 * in the bank statement import flow.
 */
class ImportException extends \Exception
{
    /**
     * @var array Additional context for debugging
     */
    protected array $context = [];

    /**
     * @var bool Whether this error is recoverable (can continue with next item)
     */
    protected bool $recoverable = false;

    /**
     * Create a new import exception.
     *
     * @param string $message Error message
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception for chaining
     * @param array $context Additional debugging context
     * @param bool $recoverable Whether this error is recoverable
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        \Throwable $previous = null,
        array $context = [],
        bool $recoverable = false
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->recoverable = $recoverable;
    }

    /**
     * Get additional context for this exception.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if this error is recoverable.
     *
     * @return bool
     */
    public function isRecoverable(): bool
    {
        return $this->recoverable;
    }

    /**
     * Add context to the exception.
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
