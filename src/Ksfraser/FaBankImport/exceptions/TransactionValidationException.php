<?php

namespace Ksfraser\FaBankImport\Exceptions;

class TransactionValidationException extends \Exception
{
    private $validationErrors;

    public function __construct(array $errors, string $message = "", int $code = 0)
    {
        parent::__construct(
            $message ?: 'Transaction validation failed: ' . implode(', ', $errors),
            $code
        );
        $this->validationErrors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}