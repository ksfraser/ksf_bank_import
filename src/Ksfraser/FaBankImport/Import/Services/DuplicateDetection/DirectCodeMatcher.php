<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Level 1: Direct Code Matcher
 * 
 * Performs authoritative duplicate detection based on:
 * - transactionCode (bank-assigned unique code)
 * - acctid (our account identifier)
 * 
 * If both match an existing transaction, it IS a duplicate (100% certainty).
 * No whitelist can override this match.
 * 
 * Responsibility: Fast, definitive duplicate check via indexed database lookup
 */
class DirectCodeMatcher
{
    /**
     * Fields to compare for duplicate validation.
     * 
     * These fields must all match for a transaction to be considered an "exact" duplicate.
     * If code+acctid match but any of these fields differ, flag for review.
     */
    private const FIELDS_TO_COMPARE = [
        'valueTimestamp',
        'transactionAmount',
        'merchant',
        'memo',
        'reference'
    ];

    /**
     * Find exact duplicate by transactionCode + acctid.
     *
     * @param array $transaction Transaction data with 'transactionCode' and 'acctid'
     * @return array|null Existing transaction if found, null otherwise
     * @throws TransactionFetchException If query fails
     */
    public function find(array $transaction): ?array
    {
        $transactionCode = $transaction['transactionCode'] ?? null;
        $acctid = $transaction['acctid'] ?? null;
        
        // Both fields required for matching
        if (!$transactionCode || !$acctid) {
            return null;
        }
        
        try {
            $query = sprintf(
                "SELECT * FROM %s bi_transactions 
                 WHERE transactionCode = %s 
                 AND acctid = %s
                 LIMIT 1",
                TB_PREF,
                db_escape($transactionCode),
                db_escape($acctid)
            );
            
            $result = db_query($query, 'Could not query for exact duplicate');
            return db_fetch_assoc($result);
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_transactions WHERE transactionCode=? AND acctid=?",
                $e->getMessage()
            );
        }
    }

    /**
     * Find code match AND compare all fields to detect field mismatches.
     *
     * **PHASE 2 ENHANCEMENT**: When transactionCode+acctid match, we now validate
     * that ALL transaction details also match. If code matches but data differs,
     * this indicates possible data corruption or transmission error.
     *
     * @param array $newTransaction Incoming transaction data
     * @return array|null Result array with keys:
     *   - 'match': The existing transaction (if found)
     *   - 'fields_that_differ': CSV string of mismatched fields (if code matches)
     *   - 'all_fields_match': Boolean indicating if all fields are identical
     * @throws TransactionFetchException If query fails
     */
    public function findAndCompare(array $newTransaction): ?array
    {
        // First find code match
        $existingTransaction = $this->find($newTransaction);
        
        if (!$existingTransaction) {
            return null;  // No code match found
        }
        
        // Code matches - now compare all fields
        $fieldsThatDiffer = $this->getFieldsThatDiffer($newTransaction, $existingTransaction);
        
        return [
            'match' => $existingTransaction,
            'fields_that_differ' => $fieldsThatDiffer,  // CSV string or empty
            'all_fields_match' => empty($fieldsThatDiffer),  // Boolean
            'is_code_match' => true  // For explicit intent
        ];
    }

    /**
     * Compare all relevant fields between two transactions.
     *
     * @param array $newTransaction New/incoming transaction
     * @param array $existingTransaction Existing transaction from DB
     * @return string CSV string of fields that differ (e.g., "memo,amount") or empty ""
     */
    private function getFieldsThatDiffer(array $newTransaction, array $existingTransaction): string
    {
        $differences = [];
        
        foreach (self::FIELDS_TO_COMPARE as $field) {
            $newValue = $newTransaction[$field] ?? null;
            $existingValue = $existingTransaction[$field] ?? null;
            
            // Normalize for comparison (trim strings, convert types)
            $newValue = $this->normalizeValue($newValue);
            $existingValue = $this->normalizeValue($existingValue);
            
            if ($newValue !== $existingValue) {
                $differences[] = $field;
            }
        }
        
        return implode(',', $differences);
    }

    /**
     * Normalize values for field comparison.
     *
     * Handles:
     * - Whitespace trimming
     * - Type casting
     * - Null/empty normalization
     * - Float precision
     *
     * @param mixed $value
     * @return mixed Normalized value
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Trim strings
        if (is_string($value)) {
            return trim($value);
        }
        
        // Round floats to 2 decimal places for amount comparison
        if (is_numeric($value)) {
            return round((float)$value, 2);
        }
        
        return $value;
    }
}
