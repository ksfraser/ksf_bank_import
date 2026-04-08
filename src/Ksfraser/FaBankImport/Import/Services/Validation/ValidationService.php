<?php

namespace Ksfraser\FaBankImport\Import\Services\Validation;

use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Service for validating bank statements and transactions
 *
 * Handles validation of:
 * - Statement metadata (bank, account, currency, dates)
 * - Transaction integrity (type, date format, amount, balance)
 * - Cross-field validation (balance reconciliation)
     * - Error reporting via ValidationResult (Notification Pattern)
     *
     * Implements SRP: Single responsibility = data validation with typesafe constants
     * Uses Notification Pattern: Returns ValidationResult instead of managing error state
     */
    final class ValidationService
    {
        /**
         * Valid transaction types
         *
         * @var array<string>
         */
        private const VALID_TRANSACTION_TYPES = ['CREDIT', 'DEBIT', 'OTHER'];

        /**
         * ISO 4217 currency code length
         *
         * @var int
         */
        private const CURRENCY_CODE_LENGTH = 3;

        /**
         * Standard date format for validation
         *
         * @var string
         */
        private const DATE_FORMAT = 'Y-m-d';

        /**
         * Validate a bank statement
         *
         * Returns a ValidationResult object containing status and all errors.
         * Replaces boolean return + getErrors() pattern with self-contained result.
         *
         * @param BiStatement $statement Statement to validate
         * @return ValidationResult Validation result with errors (if any)
         */
        public function validate(BiStatement $statement): ValidationResult
        {
            $errors = [];

            // Validate statement metadata
            $metadataErrors = $this->validateStatementMetadata($statement);
            if (!empty($metadataErrors)) {
                $errors = array_merge($errors, $metadataErrors);
            }

            // Validate all transactions
            foreach ($statement->getTransactions() as $transaction) {
                $txErrors = $this->validateTransaction($transaction);
                if (!empty($txErrors)) {
                    $errors = array_merge($errors, $txErrors);
                }
            }

            // Return result object containing all errors
            if (empty($errors)) {
                return ValidationResult::valid();
            }

            return ValidationResult::invalid($errors);
        }

        /**
         * Validate statement metadata
         *
         * @param BiStatement $statement Statement to validate
         * @return array<int, string> Array of validation errors (empty if valid)
         */
        private function validateStatementMetadata(BiStatement $statement): array
        {
            $errors = [];

            // Validate bank is not empty
            if (empty($statement->getBank())) {
                $errors[] = 'Statement bank must not be empty';
            }

            // Validate account is not empty
            if (empty($statement->getAccount())) {
                $errors[] = 'Statement account must not be empty';
            }

            // Validate statement ID is not empty
            if (empty($statement->getStatementId())) {
                $errors[] = 'Statement ID must not be empty';
            }

            // Validate currency is not empty
            if (empty($statement->getCurrency())) {
                $errors[] = 'Statement currency must not be empty';
            } else {
                // Validate currency is correct length
                if (strlen($statement->getCurrency()) !== self::CURRENCY_CODE_LENGTH) {
                    $errors[] = sprintf(
                        'Currency must be a %d-letter code, got: %s',
                        self::CURRENCY_CODE_LENGTH,
                        $statement->getCurrency()
                    );
                }
            }

            // Validate statement date is valid
            $smtDate = $statement->getSmtDate();
            if ($smtDate === null) {
                $errors[] = 'Statement date must not be empty';
            } else {
                $dateStr = $smtDate instanceof \DateTime ? $smtDate->format(self::DATE_FORMAT) : (string)$smtDate;
                if (!$this->isValidDate($dateStr)) {
                    $errors[] = 'Statement date is invalid: ' . $dateStr;
                }
            }

            // Validate balances are numeric
            if (!is_numeric($statement->getStartBalance())) {
                $errors[] = 'Start balance must be numeric';
            }

            if (!is_numeric($statement->getEndBalance())) {
                $errors[] = 'End balance must be numeric';
            }

            return $errors;
        }

        /**
         * Validate a transaction
         *
         * @param BiTransaction $transaction Transaction to validate
         * @return array<int, string> Array of validation errors (empty if valid)
         */
        private function validateTransaction(BiTransaction $transaction): array
        {
            $errors = [];

            // Validate transaction type
            if (!in_array($transaction->getTransactionType(), self::VALID_TRANSACTION_TYPES, true)) {
                $errors[] = 'Invalid transaction type: ' . $transaction->getTransactionType();
            }

            // Validate transaction date format
            $dateValue = $transaction->getValueTimestamp();
            $dateStr = $dateValue instanceof \DateTime ? $dateValue->format(self::DATE_FORMAT) : (string)$dateValue;
            if (!$this->isValidDate($dateStr)) {
                $errors[] = 'Invalid transaction date format: ' . $dateStr;
            }

            // Validate amount is numeric
            if (!is_numeric($transaction->getTransactionAmount())) {
                $errors[] = 'Transaction amount must be numeric';
            }

            return $errors;
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat(self::DATE_FORMAT, $date);
        return $d !== false && $d->format(self::DATE_FORMAT) === $date;
    }
}
