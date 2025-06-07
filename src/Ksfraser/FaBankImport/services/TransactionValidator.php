<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\Application\Config\Config;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

class TransactionValidator
{
    private $config;
    private $errors = [];

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function validate(BankTransactionInterface $transaction): bool
    {
        $this->errors = [];

        $this->validateType($transaction)
             ->validateAmount($transaction)
             ->validateDate($transaction);

        return empty($this->errors);
    }

    private function validateType(BankTransactionInterface $transaction): self
    {
        $allowedTypes = $this->config->get('transaction.allowed_types');
        
        if (!in_array($transaction->getTransactionType(), $allowedTypes)) {
            $this->errors[] = "Invalid transaction type: {$transaction->getTransactionType()}";
        }

        return $this;
    }

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

    private function validateDate(BankTransactionInterface $transaction): self
    {
        $date = \DateTime::createFromFormat('Y-m-d', $transaction->getDate());
        
        if (!$date || $date->format('Y-m-d') !== $transaction->getDate()) {
            $this->errors[] = "Invalid transaction date format: {$transaction->getDate()}";
        }

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
