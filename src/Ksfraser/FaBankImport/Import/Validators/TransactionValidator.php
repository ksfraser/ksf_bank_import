<?php

namespace Ksfraser\FaBankImport\Import\Validators;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionValidationException;
use Ksfraser\FaBankImport\Import\Exceptions\BankTransferException;
use Ksfraser\FaBankImport\Import\Results\ValidationResult;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateDetectionService;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateReviewHandler;

/**
 * Validator for individual bank transactions before processing.
 * 
 * Ensures transaction data meets all preconditions:
 * - Transaction has required fields
 * - Amount is valid and non-zero
 * - Date is valid and within reasonable range
 * - NOT a same-account transfer (critical for data corruption prevention)
 * - Not a duplicate of already-processed transaction
 * - Counterparty information is available
 */
class TransactionValidator
{
    private $duplicateDetectionService;
    private $duplicateReviewHandler;
    
    public function __construct(
        DuplicateDetectionService $duplicateDetectionService = null,
        DuplicateReviewHandler $duplicateReviewHandler = null
    ) {
        $this->duplicateDetectionService = $duplicateDetectionService ?? new DuplicateDetectionService();
        $this->duplicateReviewHandler = $duplicateReviewHandler ?? new DuplicateReviewHandler();
    }
    
    /**
     * Validate a complete transaction object.
     *
     * @param object|array $transaction Transaction data
     * @param int $bankAccountId Bank account this transaction is for
     * @param array $options Validation options
     * @return ValidationResult
     * @throws BankTransferException For same-account transfers (non-recoverable)
     */
    public function validate($transaction, int $bankAccountId, array $options = []): ValidationResult
    {
        $result = ValidationResult::valid();
        $data = is_array($transaction) ? $transaction : (array)$transaction;

        // Validate required fields
        $requiredFields = ['id', 'date', 'amount', 'reference'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $result->addFieldError('transaction', 'Missing required fields: ' . implode(', ', $missingFields));
            $result->recordRuleCheck('required_fields', false);
            return $result;
        }
        $result->recordRuleCheck('required_fields', true);

        $transactionId = $data['id'];

        // Validate amount
        if (!$this->validateAmount($data['amount'])) {
            throw TransactionValidationException::invalidAmount($data['amount'], $transactionId);
        }
        $result->recordRuleCheck('valid_amount', true);

        // Validate date
        if (!$this->validateDate($data['date'])) {
            throw TransactionValidationException::invalidDate($transactionId, $data['date'], 'Invalid date format');
        }
        $result->recordRuleCheck('valid_date', true);

        // **CRITICAL**: Check for same-account transfer (FROM == TO)
        if ($this->isSameAccountTransfer($data, $bankAccountId)) {
            throw BankTransferException::sameAccount(
                $bankAccountId,
                $transactionId,
                $data['amount']
            );
        }
        $result->recordRuleCheck('not_same_account_transfer', true);

        // Check for duplicate using multi-level detection service
        if ($options['checkDuplicate'] ?? false) {
            $statementId = (int)($options['statementId'] ?? 0);
            
            $checkResult = $this->duplicateDetectionService->detect($data);
            
            if ($checkResult->shouldSkip()) {
                // PHASE 1: Level 1 exact match with ALL fields identical
                // → True duplicate: skip import
                throw TransactionValidationException::duplicateTransaction(
                    $transactionId,
                    $data['reference'] ?? '',
                    'Exact duplicate: transactionCode already exists'
                );
            }
            
            if ($checkResult->mustReviewBeforeMerge()) {
                // PHASE 2: Level 1 code match BUT fields differ
                // → Potential data corruption: store for user review
                // This prevents automatic skip and flags for manual inspection
                try {
                    if ($statementId > 0) {
                        $this->duplicateReviewHandler->storeForReview(
                            $data,                                    // Incoming transaction
                            $checkResult->getExactMatch(),           // Matched transaction from DB
                            'EXACT_CODE_MISMATCH',                   // Match type
                            $checkResult->getFieldsThatDiffer(),     // CSV of differing fields
                            $statementId                             // For which statement
                        );
                    }
                } catch (\Throwable $e) {
                    // Log error but don't fail—duplicate staging is best-effort
                    error_log("Failed to store code mismatch duplicate for review: " . $e->getMessage());
                }
                
                // Mark as requiring review (but don't reject—let import proceed to staging)
                $result->addWarning(
                    sprintf(
                        'Code match detected with field differences (%s). Flagged for review.',
                        $checkResult->getFieldsThatDiffer()
                    )
                );
                $result->recordRuleCheck('not_duplicate', false);
            }
            
            if ($checkResult->needsReview()) {
                // PHASE 1/2: Level 2 fuzzy match found but not whitelisted
                // → Possible duplicate: store for user review
                try {
                    if ($statementId > 0) {
                        $fuzzyMatches = $checkResult->getFuzzyMatches();
                        if (!empty($fuzzyMatches)) {
                            $this->duplicateReviewHandler->storeForReview(
                                $data,                      // Incoming transaction
                                $fuzzyMatches[0],           // First fuzzy match
                                'FUZZY_MATCH',              // Match type
                                '',                         // No specific fields differ (fuzzy match)
                                $statementId
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    // Log error but don't fail
                    error_log("Failed to store fuzzy match duplicate for review: " . $e->getMessage());
                }
                
                $result->addWarning(
                    'Possible duplicate: same date, amount, and merchant. '
                    . 'Flagged for review on dashboard.'
                );
                $result->recordRuleCheck('not_duplicate', false);
            } else {
                // Level 1 missed or Level 2 not triggered: safe to import
                $result->recordRuleCheck('not_duplicate', true);
            }
        }

        // Check for counterparty information
        if (!$this->hasCounterpartyInfo($data)) {
            $result->addWarning('No counterparty information available');
            $result->recordRuleCheck('has_counterparty', false);
        } else {
            $result->recordRuleCheck('has_counterparty', true);
        }

        return $result;
    }

    /**
     * Validate transaction amount.
     *
     * @param mixed $amount
     * @return bool
     */
    private function validateAmount(mixed $amount): bool
    {
        if (!is_numeric($amount)) {
            return false;
        }

        $floatAmount = (float)$amount;
        
        // Amount must not be zero
        if ($floatAmount === 0.0) {
            return false;
        }

        // Amount must not exceed reasonable bounds (e.g., 999,999,999.99)
        if (abs($floatAmount) > 999999999.99) {
            return false;
        }

        return true;
    }

    /**
     * Validate transaction date.
     *
     * @param mixed $date
     * @return bool
     */
    private function validateDate(mixed $date): bool
    {
        if (empty($date)) {
            return false;
        }

        $time = strtotime($date);
        if ($time === false || $time <= 0) {
            return false;
        }

        // Date should not be in the future (typical case)
        if ($time > time()) {
            return false; // Can be warned but not failed depending on business rules
        }

        // Date should not be way in the past (before 1990)
        if ($time < strtotime('1990-01-01')) {
            return false;
        }

        return true;
    }

    /**
     * **CRITICAL**: Check if transaction is a same-account transfer.
     *
     * A same-account transfer occurs when the transaction FROM account
     * equals the TO account. This indicates an error in import or circular transfer.
     *
     * @param array $transactionData
     * @param int $bankAccountId Our bank account
     * @return bool True if same-account transfer detected
     */
    private function isSameAccountTransfer(array $transactionData, int $bankAccountId): bool
    {
        // Check if transaction is a transfer type
        $type = $transactionData['type'] ?? '';
        if ($type !== 'TRANSFER' && $type !== 'BT') {
            return false; // Not a transfer
        }

        // Get from and to accounts
        $fromAccount = $transactionData['from_account_id'] ?? null;
        $toAccount = $transactionData['to_account_id'] ?? null;

        // Both from and to must be set for this check
        if ($fromAccount === null || $toAccount === null) {
            return false;
        }

        // Check if both accounts are the same
        if ($fromAccount === $toAccount) {
            return true; // Same account transfer - CRITICAL ERROR
        }

        // Additional check: if one of them is our bank account and they're equal
        if ($fromAccount === $bankAccountId && $toAccount === $bankAccountId) {
            return true;
        }

        return false;
    }

    /**
     * Check if transaction has counterparty information.
     *
     * @param array $transactionData
     * @return bool
     */
    private function hasCounterpartyInfo(array $transactionData): bool
    {
        // Counterparty can be identified by name, account number, or contact ID
        $hasName = !empty($transactionData['counterparty_name']);
        $hasAccount = !empty($transactionData['counterparty_account']);
        $hasContactId = !empty($transactionData['contact_id'] ?? null);
        $hasParserData = !empty($transactionData['parser_contact'] ?? null);

        return $hasName || $hasAccount || $hasContactId || $hasParserData;
    }

    /**
     * Validate that transaction falls within statement date range.
     *
     * @param object|array $transaction
     * @param string $statementStartDate
     * @param string $statementEndDate
     * @return ValidationResult
     */
    public function validateDateRange($transaction, string $statementStartDate, string $statementEndDate): ValidationResult
    {
        $result = ValidationResult::valid();
        $data = is_array($transaction) ? $transaction : (array)$transaction;

        $transactionDate = strtotime($data['date'] ?? '');
        $startTime = strtotime($statementStartDate);
        $endTime = strtotime($statementEndDate);

        if ($transactionDate === false || $startTime === false || $endTime === false) {
            return $result->addFieldError('date', 'Invalid date format');
        }

        if ($transactionDate < $startTime || $transactionDate > $endTime) {
            $result->addFieldError('date', sprintf(
                'Transaction date %s outside statement range %s to %s',
                $data['date'],
                $statementStartDate,
                $statementEndDate
            ));
            $result->recordRuleCheck('date_in_range', false);
        } else {
            $result->recordRuleCheck('date_in_range', true);
        }

        return $result;
    }

    /**
     * Validate transaction against bank transfer rules.
     *
     * @param object|array $transaction
     * @param array $bankAccount Bank account data
     * @return ValidationResult
     */
    public function validateForBankTransfer($transaction, array $bankAccount): ValidationResult
    {
        $result = ValidationResult::valid();
        $data = is_array($transaction) ? (array)$transaction : (array)$transaction;

        $type = $data['type'] ?? '';
        if ($type !== 'TRANSFER' && $type !== 'BT') {
            return $result; // Not a transfer, skip checks
        }

        // Check transfer involves different accounts
        $fromAccount = $data['from_account_id'] ?? null;
        $toAccount = $data['to_account_id'] ?? null;

        if ($fromAccount === $toAccount && $fromAccount !== null) {
            $result->addError('Transfer must involve different bank accounts');
            $result->recordRuleCheck('different_accounts', false);
        } else {
            $result->recordRuleCheck('different_accounts', true);
        }

        // Check both accounts are provided
        if (empty($fromAccount) || empty($toAccount)) {
            $result->addError('Bank transfer requires both FROM and TO account');
            $result->recordRuleCheck('accounts_specified', false);
        } else {
            $result->recordRuleCheck('accounts_specified', true);
        }

        return $result;
    }

    /**
     * Validate transaction against collection/charge requirements.
     *
     * @param object|array $transaction
     * @param string $collectionIds Comma-separated collection IDs
     * @return ValidationResult
     */
    public function validateCollections($transaction, string $collectionIds): ValidationResult
    {
        $result = ValidationResult::valid();

        if (empty($collectionIds)) {
            $result->addWarning('No collection IDs associated with transaction');
            $result->recordRuleCheck('has_collections', false);
            return $result;
        }

        $ids = array_filter(array_map('trim', explode(',', $collectionIds)));
        
        foreach ($ids as $id) {
            if (!is_numeric($id) || (int)$id <= 0) {
                $result->addFieldError('collections', "Invalid collection ID: {$id}");
            }
        }

        $result->recordRuleCheck('has_collections', true);
        return $result;
    }
}
