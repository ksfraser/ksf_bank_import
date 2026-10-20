<?php

namespace Ksfraser\FaBankImport\Exceptions;

//TODO:  flesh out specific validations and their resulting exceptions

/**
 * @BABOK RTM: FR-050 / ARCH-003; file: Exceptions/TransactionValidationException.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionValidationException extends \Exception
{
    private $validationErrors;

    /** @BABOK FR-050 / ARCH-003 — method: __construct */
    public function __construct(array $errors, string $message = "", int $code = 0)
    {
        parent::__construct(
            $message ?: 'Transaction validation failed: ' . implode(', ', $errors),
            $code
        );
        $this->validationErrors = $errors;
    }

    /** @BABOK FR-050 / ARCH-003 — method: getValidationErrors */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
