<?php
/**
 * Transaction Filter Service
 * 
 * Single Responsibility: Build SQL WHERE clauses for filtering bank import transactions.
 * 
 * Mantis Bug #3188: Add bank account filter to process_statements screen
 * 
 * UML Class Diagram:
 * <code>
 * ┌─────────────────────────────────────────┐
 * │    TransactionFilterService             │
 * ├─────────────────────────────────────────┤
 * │ + buildWhereClause(...): string         │
 * │ + extractFiltersFromPost(...): array    │
 * │ - validateDate(date, field): void       │
 * │ - shouldFilterByBankAccount(...): bool  │
 * │ - shouldFilterByStatus(...): bool       │
 * └─────────────────────────────────────────┘
 * </code>
 * 
 * UML Sequence Diagram:
 * <code>
 * Controller      FilterService
 *     │                │
 *     │ extractFiltersFromPost($_POST)
 *     ├───────────────>│
 *     │                │
 *     │  filters[]     │
 *     │<───────────────┤
 *     │                │
 *     │ buildWhereClause(startDate, endDate, status, bankAccount)
 *     ├───────────────>│
 *     │                │
 *     │                │ validateDate()
 *     │                ├──────┐
 *     │                │<─────┘
 *     │                │
 *     │                │ shouldFilterByStatus()
 *     │                ├──────┐
 *     │                │<─────┘
 *     │                │
 *     │                │ shouldFilterByBankAccount()
 *     │                ├──────┐
 *     │                │<─────┘
 *     │                │
 *     │                │ build SQL WHERE
 *     │                ├──────┐
 *     │                │<─────┘
 *     │                │
 *     │  WHERE clause  │
 *     │<───────────────┤
 *     │                │
 *     │ Execute query  │
 *     ├──────┐         │
 *     │<─────┘         │
 * </code>
 * 
 * @package    KsfBankImport
 * @subpackage Services
 * @category   Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 */

namespace KsfBankImport\Services;

/**
 * Service for building SQL WHERE clauses for transaction filtering
 * 
 * This class has a single responsibility: construct SQL WHERE clauses
 * for filtering bank import transactions by date range, status, and
 * bank account.
 * 
 * Design Pattern: Single Responsibility Principle (SRP)
 * - This service has ONE job: build WHERE clauses
 * - Separates filtering logic from data access
 * - Makes filtering logic testable and reusable
 * 
 * Background:
 * Created for Mantis Bug #3188 to add bank account filtering capability
 * to the process_statements screen. Previously, users could only filter
 * by date range and status. Now they can also filter by "Our bank account".
 * 
 * Example usage:
 * <code>
 * $filterService = new TransactionFilterService();
 * 
 * // Extract filters from POST data
 * $filters = $filterService->extractFiltersFromPost($_POST);
 * 
 * // Build WHERE clause
 * $whereClause = $filterService->buildWhereClause(
 *     $filters['startDate'],
 *     $filters['endDate'],
 *     $filters['status'],
 *     $filters['bankAccount']
 * );
 * 
 * // Use in SQL query
 * $sql = "SELECT t.*, s.account FROM bi_transactions t 
 *         LEFT JOIN bi_statements s ON t.smt_id = s.id" . $whereClause;
 * </code>
 * 
 * @since 1.0.0
 */
class TransactionFilterService
{
    /**
     * Constant for "show all" bank accounts
     */
    const BANK_ACCOUNT_ALL = 'ALL';
    
    /**
     * Constant for "show all" status
     */
    const STATUS_ALL = 255;
    
    /**
     * Build SQL WHERE clause for transaction filtering
     * 
     * Constructs a WHERE clause that filters transactions by:
     * - Date range (always applied)
     * - Status (optional: 0=unsettled, 1=settled, 255=all)
     * - Bank account (optional: specific account or 'ALL')
     * 
     * @param string      $startDate    Start date (YYYY-MM-DD format or FA date format)
     * @param string      $endDate      End date (YYYY-MM-DD format or FA date format)
     * @param int|null    $status       Transaction status (0, 1, or 255 for all)
     * @param string|null $bankAccount  Bank account identifier or 'ALL'
     * 
     * @return string SQL WHERE clause (including WHERE keyword)
     * 
     * @throws \InvalidArgumentException If date format is invalid
     * 
     * @since 1.0.0
     */
    public function buildWhereClause($startDate, $endDate, $status = null, $bankAccount = null)
    {
        // Convert dates to SQL format if date2sql function is available
        $startDateSql = $startDate;
        $endDateSql = $endDate;
        
        if (function_exists('date2sql')) {
            $startDateSql = date2sql($startDate);
            $endDateSql = date2sql($endDate);
        } else {
            // Validate date formats if date2sql not available
            $this->validateDate($startDate, 'start date');
            $this->validateDate($endDate, 'end date');
        }
        
        $conditions = [];
        
        // Date range filter (always applied)
        $conditions[] = "t.valueTimestamp >= '" . db_escape($startDateSql) . "'";
        $conditions[] = "t.valueTimestamp < '" . db_escape($endDateSql) . "'";
        
        // Status filter (optional)
        if ($this->shouldFilterByStatus($status)) {
            $conditions[] = "t.status = '" . db_escape($status) . "'";
        }
        
        // Bank account filter (optional)
        if ($this->shouldFilterByBankAccount($bankAccount)) {
            $conditions[] = "s.account = '" . db_escape($bankAccount) . "'";
        }
        
        return ' WHERE ' . implode(' AND ', $conditions);
    }
    
    /**
     * Extract filter parameters from POST data
     * 
     * Extracts and normalizes filter parameters from $_POST array,
     * providing sensible defaults for missing values.
     * 
     * @param array $postData POST data array
     * 
     * @return array Associative array with keys: startDate, endDate, status, bankAccount
     * 
     * @since 1.0.0
     */
    public function extractFiltersFromPost($postData)
    {
        // Use begin_month and end_month if available from FA
        $defaultStartDate = isset($postData['TransAfterDate']) 
            ? $postData['TransAfterDate'] 
            : (function_exists('begin_month') && function_exists('Today') 
                ? begin_month(Today()) 
                : date('Y-m-01'));
        
        $defaultEndDate = isset($postData['TransToDate']) 
            ? $postData['TransToDate'] 
            : (function_exists('end_month') && function_exists('Today') 
                ? end_month(Today()) 
                : date('Y-m-t'));
        
        return [
            'startDate' => $defaultStartDate,
            'endDate' => $defaultEndDate,
            'status' => isset($postData['statusFilter']) ? (int)$postData['statusFilter'] : 0,
            'bankAccount' => isset($postData['bankAccountFilter']) 
                ? $postData['bankAccountFilter'] 
                : self::BANK_ACCOUNT_ALL
        ];
    }
    
    /**
     * Validate date format
     * 
     * Ensures date is in YYYY-MM-DD format
     * 
     * @param string $date      Date to validate
     * @param string $fieldName Field name for error messages
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If date format is invalid
     * 
     * @since 1.0.0
     */
    private function validateDate($date, $fieldName)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException(
                "Invalid {$fieldName} format. Expected YYYY-MM-DD, got: {$date}"
            );
        }
    }
    
    /**
     * Determine if bank account filter should be applied
     * 
     * Bank account filter is NOT applied if:
     * - Value is null
     * - Value is 'ALL'
     * - Value is empty string
     * 
     * @param string|null $bankAccount Bank account value
     * 
     * @return bool True if filter should be applied
     * 
     * @since 1.0.0
     */
    private function shouldFilterByBankAccount($bankAccount)
    {
        return $bankAccount !== null 
            && $bankAccount !== self::BANK_ACCOUNT_ALL 
            && $bankAccount !== '';
    }
    
    /**
     * Determine if status filter should be applied
     * 
     * Status filter is NOT applied if:
     * - Value is null
     * - Value is 255 (STATUS_ALL)
     * 
     * @param int|null $status Status value
     * 
     * @return bool True if filter should be applied
     * 
     * @since 1.0.0
     */
    private function shouldFilterByStatus($status)
    {
        return $status !== null && $status !== self::STATUS_ALL;
    }
    
    // ============================================================================
    // FUTURE ENHANCEMENTS - Scaffolded but not yet implemented
    // See TODOs in class.bi_transactions.php and header_table.php
    // ============================================================================
    
    /**
     * Build SQL condition for transaction amount range filter
     * 
     * Filters transactions by absolute value amount range. Automatically swaps
     * min/max if provided in wrong order (smaller becomes min, larger becomes max).
     * Uses ABS() to handle both positive and negative amounts uniformly.
     * 
     * Example use cases:
     * - Find all transactions over $1000 (large transactions)
     * - Find transactions between $100-$500 (specific range)
     * - Find small transactions under $50 (reconciliation)
     * 
     * @param float|null $minAmount Minimum transaction amount (optional)
     * @param float|null $maxAmount Maximum transaction amount (optional)
     * 
     * @return string SQL condition fragment (empty if no filter)
     * 
     * @throws InvalidArgumentException If amounts are not numeric
     * 
     * @since 1.0.0
     * 
     * Features:
     * - Uses ABS() for absolute value comparisons
     * - Auto-swaps min/max if wrong order provided
     * - Handles negative amounts (debits and credits)
     * 
     * Example:
     * <code>
     * $filter = $this->buildAmountCondition(100, 500);
     * // Returns: "ABS(t.transactionAmount) >= 100 AND ABS(t.transactionAmount) <= 500"
     * 
     * $filter = $this->buildAmountCondition(500, 100);  // Wrong order
     * // Auto-swaps: "ABS(t.transactionAmount) >= 100 AND ABS(t.transactionAmount) <= 500"
     * </code>
     */
    private function buildAmountCondition($minAmount = null, $maxAmount = null)
    {
        // If both are null, no filtering needed
        if ($minAmount === null && $maxAmount === null) {
            return '';
        }
        
        // Validate inputs are numeric
        if ($minAmount !== null && !is_numeric($minAmount)) {
            throw new \InvalidArgumentException("Minimum amount must be numeric");
        }
        
        if ($maxAmount !== null && !is_numeric($maxAmount)) {
            throw new \InvalidArgumentException("Maximum amount must be numeric");
        }
        
        // Auto-swap if min > max (make smaller value the min, larger the max)
        if ($minAmount !== null && $maxAmount !== null && $minAmount > $maxAmount) {
            $temp = $minAmount;
            $minAmount = $maxAmount;
            $maxAmount = $temp;
        }
        
        // Use ABS() to handle negative amounts (absolute value comparison)
        // This treats debits and credits uniformly by amount size
        $conditions = [];
        
        if ($minAmount !== null) {
            $conditions[] = "ABS(t.transactionAmount) >= " . db_escape(abs($minAmount));
        }
        
        if ($maxAmount !== null) {
            $conditions[] = "ABS(t.transactionAmount) <= " . db_escape(abs($maxAmount));
        }
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Build SQL condition for transaction text search filter
     * 
     * Advanced text search with support for:
     * - Multiple keywords (space-separated, all must match - AND logic)
     * - Exact phrase matching (quoted strings)
     * - Exclusion using - or ! prefix
     * - Multiple fields (transactionTitle, memo, merchant)
     * 
     * Example use cases:
     * - Simple: "Amazon" -> Finds "Amazon" in any field
     * - Multiple: "Amazon Prime" -> Finds both "Amazon" AND "Prime"
     * - Exact: '"Amazon Prime"' -> Finds exact phrase "Amazon Prime"
     * - Exclusion: "Amazon -Subscribe" -> Finds "Amazon" but NOT "Subscribe"
     * - Exclusion alt: "Amazon !Subscribe" -> Same as above
     * - Complex: "Amazon !Subscribe -Kindle" -> Amazon, but exclude Subscribe or Kindle
     * 
     * Fields searched (OR logic within field set):
     * - transactionTitle (primary field)
     * - memo (secondary field)
     * - merchant (tertiary field)
     * 
     * @param string|null $searchText Search text with optional keywords/phrases
     * 
     * @return string SQL condition fragment (empty if no filter)
     * 
     * @throws InvalidArgumentException If search text is not a string
     * 
     * @since 1.0.0
     * 
     * Implementation notes:
     * - All keywords must be found (AND logic between keywords)
     * - Each keyword searches across all fields (OR logic within fields)
     * - Exact phrases (quoted) must match completely
     * - Exclusions remove matching records
     * - Case-insensitive (MySQL LIKE default)
     * 
     * Example:
     * <code>
     * $filter = $this->buildTitleSearchCondition('Amazon Prime -Subscribe');
     * // Finds: Amazon AND Prime in any field, but NOT Subscribe
     * </code>
     */
    private function buildTitleSearchCondition($searchText = null)
    {
        if ($searchText === null || trim($searchText) === '') {
            return '';
        }
        
        if (!is_string($searchText)) {
            throw new \InvalidArgumentException("Search text must be a string");
        }
        
        $searchText = trim($searchText);
        
        // Parse search text into keywords, exact phrases, and exclusions
        $includeKeywords = [];
        $excludeKeywords = [];
        $exactPhrases = [];
        
        // Extract exact phrases (quoted strings)
        preg_match_all('/"([^"]+)"/', $searchText, $phraseMatches);
        if (!empty($phraseMatches[1])) {
            $exactPhrases = $phraseMatches[1];
            // Remove phrases from search text
            $searchText = preg_replace('/"[^"]+"/', '', $searchText);
        }
        
        // Split remaining text into keywords
        $words = preg_split('/\s+/', trim($searchText), -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($words as $word) {
            // Check for exclusion prefix (- or !)
            if (preg_match('/^[-!](.+)$/', $word, $matches)) {
                $excludeKeywords[] = $matches[1];
            } else {
                $includeKeywords[] = $word;
            }
        }
        
        // If no keywords or phrases, return empty
        if (empty($includeKeywords) && empty($exactPhrases)) {
            return '';
        }
        
        $conditions = [];
        $fields = ['t.transactionTitle', 't.memo', 't.merchant'];
        
        // Build conditions for included keywords (each keyword must appear in at least one field)
        foreach ($includeKeywords as $keyword) {
            $escapedKeyword = db_escape($keyword);
            $fieldConditions = [];
            foreach ($fields as $field) {
                $fieldConditions[] = "$field LIKE '%" . $escapedKeyword . "%'";
            }
            // At least one field must match this keyword
            $conditions[] = '(' . implode(' OR ', $fieldConditions) . ')';
        }
        
        // Build conditions for exact phrases
        foreach ($exactPhrases as $phrase) {
            $escapedPhrase = db_escape($phrase);
            $fieldConditions = [];
            foreach ($fields as $field) {
                $fieldConditions[] = "$field LIKE '%" . $escapedPhrase . "%'";
            }
            // At least one field must match this exact phrase
            $conditions[] = '(' . implode(' OR ', $fieldConditions) . ')';
        }
        
        // Build exclusion conditions (NOT logic)
        foreach ($excludeKeywords as $keyword) {
            $escapedKeyword = db_escape($keyword);
            $fieldConditions = [];
            foreach ($fields as $field) {
                // None of the fields should contain this keyword
                $fieldConditions[] = "$field NOT LIKE '%" . $escapedKeyword . "%'";
            }
            // ALL fields must NOT match (use AND for exclusions)
            $conditions[] = '(' . implode(' AND ', $fieldConditions) . ')';
        }
        
        // Combine all conditions with AND (all must be true)
        return implode(' AND ', $conditions);
    }
    
    /**
     * Example of how buildWhereClause() would be extended (FUTURE)
     * 
     * This is a documentation example showing how the main buildWhereClause()
     * method would be extended to support the new filters.
     * 
     * DO NOT IMPLEMENT YET - this is for planning purposes only
     * 
     * @since 1.1.0 (planned)
     */
    /*
    public function buildWhereClauseWithFutureFilters(
        $startDate, 
        $endDate, 
        $status = null, 
        $bankAccount = null,
        $minAmount = null,        // NEW
        $maxAmount = null,        // NEW
        $searchTitle = null       // NEW
    ) {
        // Existing validation...
        
        $conditions = [];
        
        // Date range (always applied)
        $conditions[] = "t.valueTimestamp >= '" . db_escape($startDateSql) . "'";
        $conditions[] = "t.valueTimestamp < '" . db_escape($endDateSql) . "'";
        
        // Status filter (optional)
        if ($this->shouldFilterByStatus($status)) {
            $conditions[] = "t.status = '" . db_escape($status) . "'";
        }
        
        // Bank account filter (optional)
        if ($this->shouldFilterByBankAccount($bankAccount)) {
            $conditions[] = "s.account = '" . db_escape($bankAccount) . "'";
        }
        
        // Amount range filter (NEW - optional)
        $amountCondition = $this->buildAmountCondition($minAmount, $maxAmount);
        if (!empty($amountCondition)) {
            $conditions[] = $amountCondition;
        }
        
        // Title search filter (NEW - optional)
        $titleCondition = $this->buildTitleSearchCondition($searchTitle);
        if (!empty($titleCondition)) {
            $conditions[] = $titleCondition;
        }
        
        return ' WHERE ' . implode(' AND ', $conditions);
    }
    */
}
