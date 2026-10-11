<?php

namespace Ksfraser\FaBankImport\Services;

/**
 * TransactionGLValidator - Validate imported bank transactions against GL entries
 * 
 * Mantis #2713: Validate that bank transaction data matches GL entries
 * - Checks that trans_type and trans_no exist in GL
 * - Verifies amounts match between bank import and GL
 * - Flags mismatches for review
 * - Suggests possible matches when validation fails
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20251018
 */
class TransactionGLValidator
{
    protected $db;
    protected $errors = [];
    protected $warnings = [];
    protected $suggestions = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Database connection will be available through global $db in FrontAccounting
        global $db;
        $this->db = $db;
    }
    
    /**
     * Validate all imported transactions that have been matched to GL entries
     * 
     * @param int|null $smt_id Optional statement ID to validate specific statement
     * @return array Validation results with errors, warnings, and suggestions
     */
    public function validateAllTransactions($smt_id = null)
    {
        $this->errors = [];
        $this->warnings = [];
        $this->suggestions = [];
        
        // Get all transactions that have been matched (fa_trans_type and fa_trans_no are set)
        $sql = "SELECT t.id, t.smt_id, t.transactionAmount, t.transactionDC, 
                       t.valueTimestamp, t.entryTimestamp, t.memo, t.account, t.accountName,
                       t.fa_trans_type, t.fa_trans_no, t.status, t.matchinfo
                FROM " . TB_PREF . "bi_transactions t
                WHERE t.fa_trans_type > 0 AND t.fa_trans_no > 0";
        
        if ($smt_id !== null) {
            $sql .= " AND t.smt_id = " . db_escape($smt_id);
        }
        
        $sql .= " ORDER BY t.smt_id, t.id";
        
        $result = db_query($sql, "Failed to retrieve transactions for validation");
        
        $validationResults = [];
        while ($row = db_fetch($result)) {
            $validation = $this->validateTransaction($row);
            if (!$validation['valid']) {
                $validationResults[] = $validation;
            }
        }
        
        return [
            'total_checked' => db_num_rows($result),
            'issues_found' => count($validationResults),
            'results' => $validationResults,
            'summary' => $this->generateSummary($validationResults)
        ];
    }
    
    /**
     * Validate a single transaction against its GL entry
     * 
     * @param array $transaction Transaction data from bi_transactions table
     * @return array Validation result with status, errors, and suggestions
     */
    public function validateTransaction($transaction)
    {
        $result = [
            'trans_id' => $transaction['id'],
            'fa_trans_type' => $transaction['fa_trans_type'],
            'fa_trans_no' => $transaction['fa_trans_no'],
            'bank_amount' => $transaction['transactionAmount'],
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'suggestions' => []
        ];
        
        // Step 1: Check if GL transaction exists
        $glExists = $this->checkGLTransactionExists(
            $transaction['fa_trans_type'], 
            $transaction['fa_trans_no']
        );
        
        if (!$glExists) {
            $result['valid'] = false;
            $result['errors'][] = "GL Transaction does not exist: Type {$transaction['fa_trans_type']}, No {$transaction['fa_trans_no']}";
            
            // Find possible matches
            $suggestions = $this->findPossibleGLMatches($transaction);
            if (!empty($suggestions)) {
                $result['suggestions'] = $suggestions;
            }
            
            return $result;
        }
        
        // Step 2: Get GL transaction details
        $glTrans = $this->getGLTransactionDetails(
            $transaction['fa_trans_type'],
            $transaction['fa_trans_no']
        );
        
        // Step 3: Validate amounts match
        $amountValidation = $this->validateAmounts($transaction, $glTrans);
        if (!$amountValidation['valid']) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $amountValidation['errors']);
            $result['gl_amount'] = $amountValidation['gl_amount'];
            $result['bank_amount'] = $transaction['transactionAmount'];
            $result['variance'] = $amountValidation['variance'];
        }
        
        // Step 4: Validate dates are reasonable
        $dateValidation = $this->validateDates($transaction, $glTrans);
        if (!$dateValidation['valid']) {
            $result['warnings'] = array_merge($result['warnings'], $dateValidation['warnings']);
        }
        
        // Step 5: Validate bank account matches
        $accountValidation = $this->validateBankAccount($transaction, $glTrans);
        if (!$accountValidation['valid']) {
            $result['warnings'] = array_merge($result['warnings'], $accountValidation['warnings']);
        }
        
        return $result;
    }
    
    /**
     * Check if a GL transaction exists
     * 
     * @param int $trans_type Transaction type
     * @param int $trans_no Transaction number
     * @return bool True if transaction exists
     */
    protected function checkGLTransactionExists($trans_type, $trans_no)
    {
        $sql = "SELECT COUNT(*) as cnt 
                FROM " . TB_PREF . "gl_trans 
                WHERE type = " . db_escape($trans_type) . " 
                AND type_no = " . db_escape($trans_no);
        
        $result = db_query($sql, "Failed to check GL transaction existence");
        $row = db_fetch($result);
        
        return $row['cnt'] > 0;
    }
    
    /**
     * Get GL transaction details
     * 
     * @param int $trans_type Transaction type
     * @param int $trans_no Transaction number
     * @return array GL transaction details
     */
    protected function getGLTransactionDetails($trans_type, $trans_no)
    {
        $sql = "SELECT g.*, c.account_name, c.account_code
                FROM " . TB_PREF . "gl_trans g
                LEFT JOIN " . TB_PREF . "chart_master c ON g.account = c.account_code
                WHERE g.type = " . db_escape($trans_type) . " 
                AND g.type_no = " . db_escape($trans_no) . "
                ORDER BY g.counter";
        
        $result = db_query($sql, "Failed to retrieve GL transaction details");
        
        $details = [];
        while ($row = db_fetch($result)) {
            $details[] = $row;
        }
        
        return $details;
    }
    
    /**
     * Validate amounts match between bank import and GL
     * 
     * @param array $transaction Bank transaction
     * @param array $glTrans GL transaction details
     * @return array Validation result
     */
    protected function validateAmounts($transaction, $glTrans)
    {
        $result = ['valid' => true, 'errors' => []];
        
        // Find bank account GL entries
        $bankGLAmounts = [];
        foreach ($glTrans as $gl) {
            // Look for entries that might be the bank account
            // Bank accounts typically start with 1060 or similar
            if (preg_match('/^10[0-9]{2}/', $gl['account'])) {
                $bankGLAmounts[] = abs($gl['amount']);
            }
        }
        
        // Check if any bank GL amount matches the import amount
        $bankAmount = abs($transaction['transactionAmount']);
        $matchFound = false;
        $closestAmount = null;
        $minVariance = PHP_FLOAT_MAX;
        
        foreach ($bankGLAmounts as $glAmount) {
            $variance = abs($glAmount - $bankAmount);
            
            if ($variance < 0.01) { // Allow 1 cent variance for rounding
                $matchFound = true;
                break;
            }
            
            if ($variance < $minVariance) {
                $minVariance = $variance;
                $closestAmount = $glAmount;
            }
        }
        
        if (!$matchFound) {
            $result['valid'] = false;
            $result['errors'][] = "Amount mismatch: Bank={$bankAmount}, GL=" . 
                                  ($closestAmount !== null ? $closestAmount : "No bank account found");
            $result['gl_amount'] = $closestAmount;
            $result['variance'] = $minVariance;
        }
        
        return $result;
    }
    
    /**
     * Validate dates are within reasonable range
     * 
     * @param array $transaction Bank transaction
     * @param array $glTrans GL transaction details
     * @return array Validation result
     */
    protected function validateDates($transaction, $glTrans)
    {
        $result = ['valid' => true, 'warnings' => []];
        
        if (empty($glTrans)) {
            return $result;
        }
        
        $glDate = $glTrans[0]['tran_date'];
        $bankDate = $transaction['valueTimestamp'];
        
        // Calculate days difference
        $glDateTime = new \DateTime($glDate);
        $bankDateTime = new \DateTime($bankDate);
        $daysDiff = abs($glDateTime->diff($bankDateTime)->days);
        
        // Warn if dates are more than 7 days apart
        if ($daysDiff > 7) {
            $result['valid'] = false;
            $result['warnings'][] = "Date variance: GL date={$glDate}, Bank date={$bankDate} ({$daysDiff} days apart)";
        }
        
        return $result;
    }
    
    /**
     * Validate bank account matches
     * 
     * @param array $transaction Bank transaction
     * @param array $glTrans GL transaction details
     * @return array Validation result
     */
    protected function validateBankAccount($transaction, $glTrans)
    {
        $result = ['valid' => true, 'warnings' => []];
        
        $expectedAccount = $transaction['account'];
        $foundMatch = false;
        
        foreach ($glTrans as $gl) {
            if (stripos($gl['account'], $expectedAccount) !== false || 
                stripos($gl['account_name'], $transaction['accountName']) !== false) {
                $foundMatch = true;
                break;
            }
        }
        
        if (!$foundMatch && !empty($expectedAccount)) {
            $result['valid'] = false;
            $result['warnings'][] = "Bank account not found in GL: Expected {$expectedAccount} - {$transaction['accountName']}";
        }
        
        return $result;
    }
    
    /**
     * Find possible GL transaction matches
     * Uses the existing matching routine
     * 
     * @param array $transaction Bank transaction
     * @return array Suggested GL transactions
     */
    protected function findPossibleGLMatches($transaction)
    {
        $suggestions = [];
        
        // Use existing fa_gl matching logic
        $inc = include_once(__DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php');
        if ($inc) {
            try {
                $fa_gl = new \fa_gl();
                $fa_gl->set("amount", $transaction['transactionAmount']);
                $fa_gl->set("transactionDC", $transaction['transactionDC']);
                $fa_gl->set("days_spread", 7); // Look 7 days before and after
                $fa_gl->set("startdate", $transaction['valueTimestamp']);
                $fa_gl->set("enddate", $transaction['entryTimestamp']);
                $fa_gl->set("memo_", $transaction['memo']);
                
                $matches = $fa_gl->find_matching_transactions($transaction['memo']);
                
                // Return top 5 matches
                $suggestions = array_slice($matches, 0, 5);
                
            } catch (\Exception $e) {
                // Silently fail, return empty suggestions
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Generate summary of validation results
     * 
     * @param array $validationResults All validation results
     * @return array Summary statistics
     */
    protected function generateSummary($validationResults)
    {
        $summary = [
            'missing_gl' => 0,
            'amount_mismatch' => 0,
            'date_warnings' => 0,
            'account_warnings' => 0,
            'total_variance' => 0.0
        ];
        
        foreach ($validationResults as $result) {
            foreach ($result['errors'] as $error) {
                if (stripos($error, 'does not exist') !== false) {
                    $summary['missing_gl']++;
                } elseif (stripos($error, 'Amount mismatch') !== false) {
                    $summary['amount_mismatch']++;
                    if (isset($result['variance'])) {
                        $summary['total_variance'] += $result['variance'];
                    }
                }
            }
            
            foreach ($result['warnings'] as $warning) {
                if (stripos($warning, 'Date variance') !== false) {
                    $summary['date_warnings']++;
                } elseif (stripos($warning, 'Bank account not found') !== false) {
                    $summary['account_warnings']++;
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Flag a transaction for review
     * Updates the status field in bi_transactions
     * 
     * @param int $trans_id Transaction ID
     * @param string $reason Reason for flagging
     * @return bool Success
     */
    public function flagTransactionForReview($trans_id, $reason)
    {
        // Status codes: 0=unprocessed, 1=matched, 2=created, -1=flagged for review
        $sql = "UPDATE " . TB_PREF . "bi_transactions 
                SET status = -1, 
                    matchinfo = " . db_escape($reason) . "
                WHERE id = " . db_escape($trans_id);
        
        return db_query($sql, "Failed to flag transaction for review");
    }
    
    /**
     * Get all flagged transactions
     * 
     * @return array Flagged transactions
     */
    public function getFlaggedTransactions()
    {
        $sql = "SELECT t.*, s.bank, s.account as stmt_account
                FROM " . TB_PREF . "bi_transactions t
                LEFT JOIN " . TB_PREF . "bi_statements s ON t.smt_id = s.id
                WHERE t.status = -1
                ORDER BY t.smt_id, t.id";
        
        $result = db_query($sql, "Failed to retrieve flagged transactions");
        
        $flagged = [];
        while ($row = db_fetch($result)) {
            $flagged[] = $row;
        }
        
        return $flagged;
    }
    
    /**
     * Clear flag from a transaction
     * 
     * @param int $trans_id Transaction ID
     * @return bool Success
     */
    public function clearFlag($trans_id)
    {
        $sql = "UPDATE " . TB_PREF . "bi_transactions 
                SET status = 0, 
                    matchinfo = NULL
                WHERE id = " . db_escape($trans_id);
        
        return db_query($sql, "Failed to clear transaction flag");
    }
}
