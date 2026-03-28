<?php

namespace Ksfraser\FaBankImport\Import\Validators;

use Ksfraser\FaBankImport\Import\Exceptions\StatementValidationException;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionValidationException;
use Ksfraser\FaBankImport\Import\Results\ValidationResult;

/**
 * Validator for bank statements before import.
 * 
 * Ensures statement data meets all preconditions for import:
 * - Statement has required fields
 * - Statement has at least one transaction
 * - Statement dates are valid and chronological
 * - Statement is not already imported (duplicate check)
 */
class ImportStatementValidator
{
    /**
     * Validate a complete statement object.
     *
     * @param object|array $statement Statement data
     * @param array $options Validation options (duplicateCheck, dateRange, etc.)
     * @return ValidationResult
     */
    public function validate($statement, array $options = []): ValidationResult
    {
        $result = ValidationResult::valid();
        $data = is_array($statement) ? $statement : (array)$statement;

        // Check statement is not empty
        if (empty($data)) {
            throw StatementValidationException::missingFields(['statement data']);
        }

        // Validate required fields
        $requiredFields = ['id', 'date', 'transactions'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $result->addFieldError('statement', 'Missing required fields: ' . implode(', ', $missingFields));
            $result->recordRuleCheck('required_fields', false);
            return $result;
        }
        $result->recordRuleCheck('required_fields', true);

        // Validate statement has transactions
        $transactions = $data['transactions'] ?? [];
        if (empty($transactions)) {
            throw StatementValidationException::noTransactions();
        }
        $result->recordRuleCheck('has_transactions', true);

        // Validate dates
        if (!$this->validateDateRange($data['date'] ?? '', $data)) {
            $result->addFieldError('date', 'Invalid or illogical date range');
            $result->recordRuleCheck('valid_dates', false);
        } else {
            $result->recordRuleCheck('valid_dates', true);
        }

        // Check for duplicates (if option enabled)
        if ($options['checkDuplicate'] ?? false) {
            if (!$this->checkDuplicate($data['id'], $data['source_file'] ?? '')) {
                throw StatementValidationException::duplicateStatement(
                    $data['id'],
                    $data['source_file'] ?? 'unknown'
                );
            }
            $result->recordRuleCheck('not_duplicate', true);
        }

        return $result;
    }

    /**
     * Validate statement date range is valid and chronological.
     *
     * @param string|null $startDate
     * @param array $data Full statement data for context
     * @return bool
     */
    private function validateDateRange(?string $startDate, array $data): bool
    {
        if (empty($startDate)) {
            return false;
        }

        // Validate date format
        if (!$this->isValidDate($startDate)) {
            return false;
        }

        // Check statement end date if provided
        $endDate = $data['end_date'] ?? null;
        if ($endDate && !$this->isValidDate($endDate)) {
            return false;
        }

        // Ensure start <= end
        if ($endDate && strtotime($startDate) > strtotime($endDate)) {
            return false;
        }

        return true;
    }

    /**
     * Validate individual date string.
     *
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        $time = strtotime($date);
        return $time !== false && $time > 0;
    }

    /**
     * Check if statement is already imported (duplicate).
     *
     * @param int $statementId
     * @param string $sourceFile
     * @return bool True if NOT a duplicate (OK to import)
     */
    private function checkDuplicate(int $statementId, string $sourceFile): bool
    {
        // In actual implementation:
        // $result = db_query("SELECT id FROM bi_statements WHERE file_name = '{$sourceFile}' AND id = {$statementId}");
        // return !db_fetch_assoc($result);
        
        // For now, assume no duplicate
        return true;
    }

    /**
     * Validate transaction count within acceptable range.
     *
     * @param array|object $statement
     * @param int $minTransactions Minimum required
     * @param int $maxTransactions Maximum allowed
     * @return ValidationResult
     */
    public function validateTransactionCount($statement, int $minTransactions = 1, int $maxTransactions = 10000): ValidationResult
    {
        $result = ValidationResult::valid();
        $data = is_array($statement) ? $statement : (array)$statement;

        $count = count($data['transactions'] ?? []);

        if ($count < $minTransactions) {
            $result->addFieldError('transactions', "Minimum {$minTransactions} transactions required");
            $result->recordRuleCheck('min_transactions', false);
        } else {
            $result->recordRuleCheck('min_transactions', true);
        }

        if ($count > $maxTransactions) {
            $result->addFieldError('transactions', "Maximum {$maxTransactions} transactions allowed");
            $result->recordRuleCheck('max_transactions', false);
        } else {
            $result->recordRuleCheck('max_transactions', true);
        }

        return $result;
    }

    /**
     * Validate statement amount reconciliation.
     *
     * @param array|object $statement
     * @param float $bankTotalAmount Amount from bank statement header
     * @param float $tolerance Acceptable difference (e.g., 0.01)
     * @return ValidationResult
     */
    public function validateAmountReconciliation($statement, float $bankTotalAmount, float $tolerance = 0.01): ValidationResult
    {
        $result = ValidationResult::valid();
        $data = is_array($statement) ? $statement : (array)$statement;

        $calculatedTotal = $this->calculateTransactionTotal($data['transactions'] ?? []);
        $difference = abs($bankTotalAmount - $calculatedTotal);

        if ($difference > $tolerance) {
            $result->addFieldError('amount', sprintf(
                'Amount mismatch: bank reports %.2f, transactions total %.2f (difference: %.2f)',
                $bankTotalAmount,
                $calculatedTotal,
                $difference
            ));
            $result->recordRuleCheck('amount_reconciliation', false);
        } else {
            $result->recordRuleCheck('amount_reconciliation', true);
        }

        return $result;
    }

    /**
     * Calculate total amount from transactions.
     *
     * @param array $transactions
     * @return float
     */
    private function calculateTransactionTotal(array $transactions): float
    {
        $total = 0.0;
        foreach ($transactions as $txn) {
            $amount = is_array($txn) ? ($txn['amount'] ?? 0) : ($txn->amount ?? 0);
            $total += (float)$amount;
        }
        return round($total, 2);
    }
}
