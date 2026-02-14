<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionValidationException [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionValidationException.
 */
namespace Ksfraser\FaBankImport\Exceptions;

//TODO:  flesh out specific validations and their resulting exceptions

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
