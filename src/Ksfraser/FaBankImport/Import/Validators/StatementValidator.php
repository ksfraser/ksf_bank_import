<?php

namespace Ksfraser\FaBankImport\Import\Validators;

use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\FaBankImport\Import\Results\ValidationResult;
use DateTime;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Statement Validator - Business Rules Validation
 *
 * Validates parsed bank statements against 7+ business rules:
 * 1. Date range validity (start ≤ end, reasonable range)
 * 2. Amount presence and format (non-zero, valid decimals)
 * 3. Merchant/counterparty details (required fields present)
 * 4. Transaction count (min/max thresholds)
 * 5. Account reference consistency
 * 6. Currency format validation (valid ISO codes)
 * 7. Duplicate detection rules
 *
 * Returns ValidationResult with collected errors; does not throw for validation failures.
 *
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Import\Validators
 * @since 2.2.2
 */
class StatementValidator
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Minimum transactions per statement
     *
     * @var int
     */
    private int $minTransactions = 1;

    /**
     * Maximum transactions per statement
     *
     * @var int
     */
    private int $maxTransactions = 10000;

    /**
     * Maximum date range in days
     *
     * @var int
     */
    private int $maxDateRangeDays = 365;

    /**
     * Constructor
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Validate a parsed statement against all business rules
     *
     * Performs all 7 validation rules and collects errors without throwing.
     * Returns ValidationResult with success flag and error collection.
     *
     * @param ParsedStatementDTO $statement Parsed statement to validate
     * @return ValidationResult Validation result with error collection
     */
    public function validate(ParsedStatementDTO $statement): ValidationResult
    {
        $errors = [];
        $warnings = [];
        $rulesSummary = [];

        // Rule 1: Date Range Validation
        $dateRangeResult = $this->validateDateRange($statement);
        if (!$dateRangeResult['valid']) {
            $errors[] = $dateRangeResult['error'];
        }
        $rulesSummary['dateRange'] = $dateRangeResult['valid'] ? 'pass' : 'fail';

        // Rule 2: Amount Validation
        $amountsResult = $this->validateAmounts($statement);
        if (!$amountsResult['valid']) {
            $errors[] = $amountsResult['error'];
        }
        $rulesSummary['amounts'] = $amountsResult['valid'] ? 'pass' : 'fail';

        // Rule 3: Merchant Details Validation
        $merchantResult = $this->validateMerchantDetails($statement);
        if (!$merchantResult['valid']) {
            $errors[] = $merchantResult['error'];
        }
        $rulesSummary['merchantDetails'] = $merchantResult['valid'] ? 'pass' : 'fail';

        // Rule 4: Transaction Count Validation
        $countResult = $this->validateTransactionCount($statement);
        if (!$countResult['valid']) {
            $errors[] = $countResult['error'];
        }
        $rulesSummary['transactionCount'] = $countResult['valid'] ? 'pass' : 'fail';

        // Rule 5: Account Reference Validation
        $accountResult = $this->validateAccountReference($statement);
        if (!$accountResult['valid']) {
            $errors[] = $accountResult['error'];
        }
        $rulesSummary['accountReference'] = $accountResult['valid'] ? 'pass' : 'fail';

        // Rule 6: Currency Format Validation
        $currencyResult = $this->validateCurrencyFormat($statement);
        if (!$currencyResult['valid']) {
            $errors[] = $currencyResult['error'];
        }
        $rulesSummary['currencyFormat'] = $currencyResult['valid'] ? 'pass' : 'fail';

        // Rule 7: Duplicate Detection
        $duplicateResult = $this->validateDuplicateDetection($statement);
        if (!$duplicateResult['valid']) {
            $warnings[] = $duplicateResult['warning'];
        }
        $rulesSummary['duplicateDetection'] = $duplicateResult['valid'] ? 'pass' : 'warning';

        $success = count($errors) === 0;

        $this->logger->info('Statement validation completed', [
            'success' => $success,
            'errors' => count($errors),
            'warnings' => count($warnings),
            'rules' => $rulesSummary,
        ]);

        return ValidationResult::fromValidation($success, $errors, $warnings, $rulesSummary);
    }

    /**
     * Validate: Statement date range valid and reasonable (extracted from transactions)
     *
     * Checks:
     * - Transaction dates exist and are valid
     * - startDate ≤ endDate
     * - Date range ≤ maxDateRangeDays (default 365)
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'error' => string]
     */
    private function validateDateRange(ParsedStatementDTO $statement): array
    {
        if (empty($statement->transactions)) {
            return ['valid' => true]; // Skip if no transactions
        }

        // Extract date range from transactions
        $dates = [];
        foreach ($statement->transactions as $txn) {
            if (!empty($txn['date'])) {
                $dates[] = $txn['date'];
            }
        }

        if (empty($dates)) {
            return [
                'valid' => false,
                'error' => 'No valid transaction dates found in statement',
            ];
        }

        sort($dates);
        $start = new DateTime($dates[0]);
        $end = new DateTime($dates[count($dates) - 1]);

        if ($start > $end) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Invalid date range: startDate (%s) is after endDate (%s)',
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d')
                ),
            ];
        }

        $daysDiff = $end->diff($start)->days;
        if ($daysDiff > $this->maxDateRangeDays) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Date range exceeds maximum: %d days (max %d allowed)',
                    $daysDiff,
                    $this->maxDateRangeDays
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate: Transactions have valid amounts
     *
     * Checks:
     * - At least one transaction exists
     * - Each transaction has a non-null amount
     * - Each amount is numeric and properly formatted
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'error' => string]
     */
    private function validateAmounts(ParsedStatementDTO $statement): array
    {
        if (empty($statement->transactions)) {
            return [
                'valid' => false,
                'error' => 'No transactions present in statement',
            ];
        }

        $invalidAmountCount = 0;
        foreach ($statement->transactions as $transaction) {
            $amount = $transaction['amount'] ?? null;
            if ($amount === null) {
                $invalidAmountCount++;
            } elseif (!is_numeric($amount)) {
                $invalidAmountCount++;
            }
        }

        if ($invalidAmountCount > 0) {
            return [
                'valid' => false,
                'error' => sprintf(
                    '%d transaction(s) have missing or invalid amounts',
                    $invalidAmountCount
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate: Merchant/counterparty details present
     *
     * Checks:
     * - At least 50% of transactions have merchant/beneficiary name
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'error' => string]
     */
    private function validateMerchantDetails(ParsedStatementDTO $statement): array
    {
        if (empty($statement->transactions)) {
            return ['valid' => true]; // Empty statement is valid (handled elsewhere)
        }

        $merchantCount = 0;
        foreach ($statement->transactions as $transaction) {
            $merchant = $transaction['merchant'] ?? $transaction['beneficiary'] ?? null;
            if (!empty($merchant)) {
                $merchantCount++;
            }
        }

        $completeness = $merchantCount / count($statement->transactions);
        if ($completeness < 0.5) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Insufficient merchant details: only %.1f%% of transactions have merchant/beneficiary',
                    $completeness * 100
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate: Transaction count within acceptable range
     *
     * Checks:
     * - Transaction count between minTransactions and maxTransactions
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'error' => string]
     */
    private function validateTransactionCount(ParsedStatementDTO $statement): array
    {
        $count = count($statement->transactions);

        if ($count < $this->minTransactions) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Transaction count too low: %d (minimum %d required)',
                    $count,
                    $this->minTransactions
                ),
            ];
        }

        if ($count > $this->maxTransactions) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Transaction count too high: %d (maximum %d allowed)',
                    $count,
                    $this->maxTransactions
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate: Account reference is consistent
     *
     * Checks:
     * - Account reference is present
     * - Account reference is valid format (alphanumeric, 8-20 chars)
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'error' => string]
     */
    private function validateAccountReference(ParsedStatementDTO $statement): array
    {
        $account = $statement->accountReference;

        if (empty($account)) {
            return [
                'valid' => false,
                'error' => 'Account reference is missing or empty',
            ];
        }

        if (strlen($account) < 8 || strlen($account) > 20) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Account reference format invalid: %d chars (8-20 required)',
                    strlen($account)
                ),
            ];
        }

        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $account)) {
            return [
                'valid' => false,
                'error' => 'Account reference contains invalid characters',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate: Currency format is valid ISO code
     *
     * Checks:
     * - Currency code is present
     * - Currency is valid 3-letter ISO 4217 code
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'error' => string]
     */
    private function validateCurrencyFormat(ParsedStatementDTO $statement): array
    {
        $currency = $statement->currency;

        if (empty($currency)) {
            return [
                'valid' => false,
                'error' => 'Currency code is missing or empty',
            ];
        }

        if (strlen($currency) !== 3 || !preg_match('/^[A-Z]{3}$/', $currency)) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Currency format invalid: "%s" (must be 3-letter ISO code like USD, EUR)',
                    $currency
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate: Check for duplicate transactions (heuristic-based)
     *
     * Checks:
     * - Identifies potential duplicates by amount + date + merchant match
     * - Returns warning (not error) for potential duplicates found
     *
     * @param ParsedStatementDTO $statement
     * @return array<string, mixed> ['valid' => bool, 'warning' => string]
     */
    private function validateDuplicateDetection(ParsedStatementDTO $statement): array
    {
        $seen = [];
        $duplicateCount = 0;

        foreach ($statement->transactions as $transaction) {
            $amount = $transaction['amount'] ?? null;
            $date = $transaction['date'] ?? null;
            $merchant = $transaction['merchant'] ?? $transaction['beneficiary'] ?? null;

            $signature = md5(json_encode([
                'amount' => $amount,
                'date' => $date,
                'merchant' => $merchant,
            ]));

            if (isset($seen[$signature])) {
                $duplicateCount++;
            } else {
                $seen[$signature] = true;
            }
        }

        if ($duplicateCount > 0) {
            return [
                'valid' => false,
                'warning' => sprintf(
                    'Potential duplicates detected: %d transaction(s) have matching amount/date/merchant',
                    $duplicateCount
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Set minimum transaction count threshold
     *
     * @param int $count Minimum number of transactions required
     * @return self Fluent interface
     */
    public function setMinTransactions(int $count): self
    {
        $this->minTransactions = max(0, $count);
        return $this;
    }

    /**
     * Set maximum transaction count threshold
     *
     * @param int $count Maximum number of transactions allowed
     * @return self Fluent interface
     */
    public function setMaxTransactions(int $count): self
    {
        $this->maxTransactions = max(1, $count);
        return $this;
    }

    /**
     * Set maximum date range threshold
     *
     * @param int $days Maximum date range in days
     * @return self Fluent interface
     */
    public function setMaxDateRangeDays(int $days): self
    {
        $this->maxDateRangeDays = max(1, $days);
        return $this;
    }
}
