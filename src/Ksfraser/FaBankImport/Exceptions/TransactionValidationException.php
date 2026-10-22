<?php

namespace Ksfraser\FaBankImport\Exceptions;

//TODO:  flesh out specific validations and their resulting exceptions

/**
 * @BABOK FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
 */
class TransactionValidationException extends \Exception
{
    private $validationErrors;

    /**
 * @BABOK FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(array $errors, string $message = "", int $code = 0)
    {
        parent::__construct(
            $message ?: 'Transaction validation failed: ' . implode(', ', $errors),
            $code
        );
        $this->validationErrors = $errors;
    }

    /**
 * @BABOK FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
