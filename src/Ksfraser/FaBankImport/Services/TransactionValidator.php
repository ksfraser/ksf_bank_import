<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\Application\Config\Config;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

/**
 * @BABOK CLASS ROLLUP: TransactionValidator — 6 methods; BR-001 (6); BR-002 (6); FR-008 (6)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/TransactionValidator.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionValidator
{
    private $config;
    private $errors = [];

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct */
    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: validate */
    public function validate(BankTransactionInterface $transaction): bool
    {
        $this->errors = [];

        $this->validateType($transaction)
             ->validateAmount($transaction)
             ->validateDate($transaction);

        return empty($this->errors);
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: validateType */
    private function validateType(BankTransactionInterface $transaction): self
    {
        $allowedTypes = $this->config->get('transaction.allowed_types');
        
        if (!in_array($transaction->getTransactionType(), $allowedTypes)) {
            $this->errors[] = "Invalid transaction type: {$transaction->getTransactionType()}";
        }

        return $this;
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: validateAmount */
    private function validateAmount(BankTransactionInterface $transaction): self
    {
        $maxAmount = $this->config->get('transaction.max_amount');
        
        if ($transaction->getAmount() > $maxAmount) {
            $this->errors[] = "Transaction amount exceeds maximum allowed: {$maxAmount}";
        }

        if ($transaction->getAmount() <= 0) {
            $this->errors[] = "Transaction amount must be greater than zero";
        }

        return $this;
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: validateDate */
    private function validateDate(BankTransactionInterface $transaction): self
    {
        $date = \DateTime::createFromFormat('Y-m-d', $transaction->getDate());
        
        if (!$date || $date->format('Y-m-d') !== $transaction->getDate()) {
            $this->errors[] = "Invalid transaction date format: {$transaction->getDate()}";
        }

        return $this;
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getErrors */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
