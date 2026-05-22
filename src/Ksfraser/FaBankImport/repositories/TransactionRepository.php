<?php
/**
 * Transaction Repository
 * 
 * Data access layer for bi_transactions table.
 * Implements Repository pattern with dependency injection of QueryBuilder.
 * Separates data access from business logic (Single Responsibility Principle).
 * 
 * @package    Ksfraser\FaBankImport\Repositories
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251104
 * @version    20251104.1
 * 
 * @example
 * ```php
 * $builder = new TransactionQueryBuilder();
 * $repo = new TransactionRepository($builder);
 * $transactions = $repo->findByFilters(['status' => 1]);
 * ```
 */

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;
use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;

/**
 * Repository for bi_transactions table
 * 
 * Provides data access methods using QueryBuilder for SQL generation.
 * Executes queries using FrontAccounting's db_query() function.
 * 
 * @since 20251104
 * @version 20251104.1
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @var TransactionQueryBuilder Query builder for SQL generation
     */
    private $queryBuilder;
    
    /**
     * Constructor with dependency injection
     * 
     * @param TransactionQueryBuilder $queryBuilder The query builder to use
     * 
     * @since 20251104
     */
    public function __construct(TransactionQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
    
    /**
     * Find all transactions
     * 
     * @return array Array of transaction records
     * 
     * @since 20251104
     */
    public function findAll(): array
    {
        $query = $this->queryBuilder->buildGetTransactionsQuery([]);
        $result = db_query($query['sql']);
        
        $transactions = [];
        while ($row = db_fetch($result)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }

    public function findByStatus(string $status): array
    {
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        $escapedStatus = function_exists('db_escape') ? db_escape($status) : addslashes($status);
        $sql = "SELECT * FROM {$prefix}bi_transactions WHERE status = '{$escapedStatus}'";
        $result = db_query($sql, 'unable to get transactions by status');
        $transactions = [];
        while ($row = db_fetch($result)) {
            $transactions[] = $row;
        }
        return $transactions;
    }

    public function save(array $transaction): bool
    {
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        if (isset($transaction['id']) && $transaction['id'] > 0) {
            $id = (int)$transaction['id'];
            unset($transaction['id']);
            return $this->update($id, $transaction);
        }
        $fields = implode(', ', array_keys($transaction));
        $values = implode(', ', array_map(function ($v) {
            if (is_null($v)) return 'NULL';
            if (is_bool($v)) return $v ? '1' : '0';
            if (is_numeric($v)) return $v;
            return "'" . (function_exists('db_escape') ? db_escape((string)$v) : addslashes((string)$v)) . "'";
        }, array_values($transaction)));
        $sql = "INSERT INTO {$prefix}bi_transactions ({$fields}) VALUES ({$values})";
        $result = @db_query($sql, 'unable to save transaction');
        return $result !== false;
    }
    
    /**
     * Find transaction by ID
     * 
     * @param int $id Transaction ID
     * 
     * @return array|null Transaction record or null if not found
     * 
     * @since 20251104
     */
    public function findById(int $id): ?array
    {
        $query = $this->queryBuilder->buildGetTransactionQuery($id);
        $result = db_query($query['sql'], 'unable to get transaction');
        
        $row = db_fetch($result);
        return $row ? $row : null;
    }
    
    /**
     * Find transactions by filters
     * 
     * @param array $filters Associative array of filters (see QueryBuilder::buildGetTransactionsQuery)
     * 
     * @return array Array of transaction records
     * 
     * @since 20251104
     * 
     * @example
     * ```php
     * $transactions = $repo->findByFilters([
     *     'status' => 1,
     *     'dateFrom' => '2025-01-01',
     *     'dateTo' => '2025-12-31',
     *     'limit' => 100
     * ]);
     * ```
     */
    public function findByFilters(array $filters): array
    {
        $query = $this->queryBuilder->buildGetTransactionsQuery($filters);
        
        // Build parameters for prepared statement
        $sql = $query['sql'];
        $params = $query['params'];
        
        // Replace placeholders with actual values for FA's db_query
        // Note: FA uses a different parameter binding approach
        foreach ($params as $param) {
            $escapedParam = is_numeric($param) ? $param : "'" . db_escape($param) . "'";
            $sql = preg_replace('/\?/', $escapedParam, $sql, 1);
        }
        
        $result = db_query($sql, 'unable to get transactions');
        
        $transactions = [];
        while ($row = db_fetch($result)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Update specific transaction fields
     * 
     * Generic update method for arbitrary field updates per REGRESSION_TESTING_SESSION_2025-11-14.md.
     * Builds SET clause dynamically, supports single/multiple fields, handles edge cases (ID=0),
     * preserves data types, uses parameterized UPDATE queries.
     * 
     * @param int   $id     Transaction ID to update
     * @param array $data   Associative array of fields to update: ['status' => 'value', ...]
     * 
     * @return bool True on success, false on failure
     * 
     * @since 20251114 (per regression testing spec)
     */
    public function update(int $id, array $data): bool
    {
        // Edge case: ID=0 is invalid
        if ($id === 0) {
            return false;
        }
        
        // Edge case: empty data array → nothing to update
        if (empty($data)) {
            return true; // Vacuous truth: successfully updated nothing
        }
        
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        
        // Build SET clause dynamically from data array
        $setClauses = [];
        $params = [];
        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = ?";
            $params[] = $value;
        }
        $params[] = $id; // ID is last parameter
        
        $setClause = implode(', ', $setClauses);
        $sql = "UPDATE {$prefix}bi_transactions SET {$setClause} WHERE id = ?";
        
        // Replace placeholders with escaped values for FA's db_query
        foreach ($params as $param) {
            if (is_null($param)) {
                $escapedParam = 'NULL';
            } elseif (is_bool($param)) {
                $escapedParam = $param ? '1' : '0';
            } elseif (is_numeric($param)) {
                $escapedParam = $param;
            } else {
                $escapedParam = "'" . db_escape((string)$param) . "'";
            }
            $sql = preg_replace('/\?/', $escapedParam, $sql, 1);
        }
        
        $result = @db_query($sql, 'unable to update transaction');
        return $result !== false;
    }
    
    /**
     * Update transactions with FA GL information
     * 
     * FA-integration specific update for complex transaction state including GL linkage.
     * 
     * @param array  $transactionIds Array of transaction IDs to update
     * @param int    $status         New status value
     * @param int    $faTransNo      FA transaction number
     * @param int    $faTransType    FA transaction type
     * @param bool   $matched        Whether transaction is matched
     * @param bool   $created        Whether GL entry was created
     * @param string|null $partnerType   Partner type (customer/supplier)
     * @param string $partnerOption  Partner option/classification
     * 
     * @return int Number of rows affected
     * 
     * @since 20251104
     */
    public function updateFaIntegration(
        array $transactionIds,
        int $status,
        int $faTransNo,
        int $faTransType,
        bool $matched = false,
        bool $created = false,
        ?string $partnerType = null,
        string $partnerOption = ''
    ): int {
        $query = $this->queryBuilder->buildUpdateTransactionsQuery(
            $transactionIds,
            $status,
            $faTransNo,
            $faTransType,
            $matched,
            $created,
            $partnerType,
            $partnerOption
        );
        
        // Replace placeholders with actual values for FA
        $sql = $query['sql'];
        $params = $query['params'];
        
        foreach ($params as $param) {
            if (is_null($param)) {
                $escapedParam = 'NULL';
            } elseif (is_bool($param)) {
                $escapedParam = $param ? '1' : '0';
            } elseif (is_numeric($param)) {
                $escapedParam = $param;
            } else {
                $escapedParam = "'" . db_escape($param) . "'";
            }
            $sql = preg_replace('/\?/', $escapedParam, $sql, 1);
        }
        
        db_query($sql, 'unable to update transactions');
        
        return db_affected_rows();
    }
    
    /**
     * Reset transactions (for void operations)
     * 
     * @param array $transactionIds Array of transaction IDs to reset
     * @param int   $faTransNo      FA transaction number that was voided
     * @param int   $faTransType    FA transaction type that was voided
     * 
     * @return int Number of rows affected
     * 
     * @since 20251104
     */
    public function reset(
        array $transactionIds,
        int $faTransNo,
        int $faTransType
    ): int {
        $query = $this->queryBuilder->buildResetTransactionsQuery(
            $transactionIds,
            $faTransNo,
            $faTransType
        );
        
        // Replace placeholders with actual values for FA
        $sql = $query['sql'];
        $params = $query['params'];
        
        foreach ($params as $param) {
            $escapedParam = is_numeric($param) ? $param : "'" . db_escape($param) . "'";
            $sql = preg_replace('/\?/', $escapedParam, $sql, 1);
        }
        
        db_query($sql, 'unable to reset transactions');
        
        return db_affected_rows();
    }
    
    /**
     * Prevoid hook - called when FA transaction is being voided
     * 
     * @param int $faTransNo   FA transaction number
     * @param int $faTransType FA transaction type
     * 
     * @return int Number of rows affected
     * 
     * @since 20251104
     */
    public function prevoid(int $faTransNo, int $faTransType): int
    {
        $query = $this->queryBuilder->buildPrevoidQuery($faTransNo, $faTransType);
        
        // Replace placeholders with actual values for FA
        $sql = $query['sql'];
        $params = $query['params'];
        
        foreach ($params as $param) {
            $escapedParam = is_numeric($param) ? $param : "'" . db_escape($param) . "'";
            $sql = preg_replace('/\?/', $escapedParam, $sql, 1);
        }
        
        db_query($sql, 'unable to prevoid transaction');
        
        return db_affected_rows();
    }
    
    /**
     * Find transactions by status
     * 
     * Retrieves all transactions with a specific status value.
     * Per REGRESSION_TESTING_SESSION_2025-11-14.md: handles status='1' (processed),
     * status='0' (unprocessed), empty string edge case, parameterized queries.
     * 
     * @param string $status Transaction status filter (e.g., '0'=unprocessed, '1'=processed)
     * 
     * @return array Array of transaction records, empty array if no matches
     * 
     * @since 20251114 (per regression testing spec)
     */
    public function findByStatus(string $status): array
    {
        // Handle edge case: empty string → return empty array
        if ($status === '') {
            return [];
        }
        
        // Build parameterized query with status filter
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        $sql = "SELECT * FROM {$prefix}bi_transactions WHERE status = ?";
        
        // Replace placeholder with escaped value for FA's db_query
        $escapedStatus = db_escape($status);
        $sql = preg_replace('/\?/', "'" . $escapedStatus . "'", $sql, 1);
        
        $result = db_query($sql, 'unable to get transactions by status');
        
        $transactions = [];
        while ($row = db_fetch($result)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Save a new transaction
     * 
     * Inserts a complete transaction record into the bi_transactions table.
     * Per REGRESSION_TESTING_SESSION_2025-11-14.md: handles zero amount (valid),
     * negative amount (debits), empty memo, returns false on failure,
     * uses parameterized INSERT with 4 placeholders.
     * 
     * @param array $transactionData Transaction data with keys:
     *                               - 'amount': float (handles zero and negative)
     *                               - 'valueTimestamp': string (YYYY-MM-DD)
     *                               - 'memo': string (can be empty)
     *                               - 'status': string (default: 'pending')
     * 
     * @return bool True on success, false on failure
     * 
     * @since 20251114 (per regression testing spec)
     */
    public function save(array $transactionData): bool
    {
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        
        // Extract and prepare parameters
        $amount = (float)($transactionData['amount'] ?? 0);
        $valueTimestamp = (string)($transactionData['valueTimestamp'] ?? date('Y-m-d'));
        $memo = (string)($transactionData['memo'] ?? '');
        $status = (string)($transactionData['status'] ?? 'pending');
        
        // Build parameterized INSERT query with 4 placeholders
        $sql = "INSERT INTO {$prefix}bi_transactions (amount, valueTimestamp, memo, status) 
                VALUES (?, ?, ?, ?)";
        
        // Replace placeholders with escaped values for FA's db_query
        $escapedAmount = is_numeric($amount) ? $amount : "'" . db_escape((string)$amount) . "'";
        $escapedTimestamp = "'" . db_escape($valueTimestamp) . "'";
        $escapedMemo = "'" . db_escape($memo) . "'";
        $escapedStatus = "'" . db_escape($status) . "'";
        
        $sql = preg_replace('/\?/', $escapedAmount, $sql, 1);
        $sql = preg_replace('/\?/', $escapedTimestamp, $sql, 1);
        $sql = preg_replace('/\?/', $escapedMemo, $sql, 1);
        $sql = preg_replace('/\?/', $escapedStatus, $sql, 1);
        
        $result = @db_query($sql, 'unable to insert transaction');
        
        // Return false if query failed
        return $result !== false;
    }
    
    /**
     * Find normal pairing patterns
     * 
     * Groups transactions by account, g_option, and g_partner to find common patterns.
     * Used for automated transaction matching.
     * 
     * @param string|null $account Optional bank account to filter by
     * 
     * @return array Array of pairing pattern records
     * 
     * @since 20240729 (original function date)
     * @version 20251104.1 (moved to repository)
     */
    public function findNormalPairing(?string $account = null): array
    {
        $query = $this->queryBuilder->buildNormalPairingQuery($account);
        
        // Replace placeholders with actual values for FA
        $sql = $query['sql'];
        $params = $query['params'];
        
        foreach ($params as $param) {
            $escapedParam = is_numeric($param) ? $param : "'" . db_escape($param) . "'";
            $sql = preg_replace('/\?/', $escapedParam, $sql, 1);
        }
        
        $result = db_query($sql, 'unable to get normal pairing patterns');
        
        $pairings = [];
        while ($row = db_fetch($result)) {
            $pairings[] = $row;
        }
        
        return $pairings;
    }
}
