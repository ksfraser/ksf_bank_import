<?php

namespace Ksfraser\FaBankImport\Database;

/**
 * Transaction Query Builder
 * 
 * Responsible for building SQL queries for bi_transactions table.
 * Follows Single Responsibility Principle (SRP) - only builds queries.
 * 
 * @package    Ksfraser\FaBankImport\Database
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251104
 * @version    20251104.2
 * 
 * @example
 * ```php
 * $builder = new TransactionQueryBuilder('0_bi_transactions');
 * $sql = $builder->buildGetTransactionsQuery([
 *     'status' => 1,
 *     'dateFrom' => '2025-01-01',
 *     'dateTo' => '2025-12-31'
 * ]);
 * ```
 * 
 * SOLID Principles Applied:
 * - Single Responsibility: Only builds SQL queries
 * - Open/Closed: Can extend with new query types without modifying existing
 * - Dependency Inversion: Depends on abstractions (parameters) not concrete implementations
 */
class TransactionQueryBuilder
{
    /**
     * @var string Database table name with prefix
     */
    private $tableName;
    
    /**
     * @var string Statements table name with prefix
     */
    private $statementsTable;
    
    /**
     * Constructor
     * 
     * Note: In FrontAccounting, each company has its own table prefix defined by TB_PREF constant.
     * If no table names provided, will use TB_PREF if defined, otherwise defaults to '0_'.
     * 
     * @param string|null $tableName The transactions table name (default: TB_PREF.'bi_transactions')
     * @param string|null $statementsTable The statements table name (default: TB_PREF.'bi_statements')
     * 
     * @since 20251104
     * @version 20251104.2
     */
    public function __construct(?string $tableName = null, ?string $statementsTable = null)
    {
        // Get the table prefix from FrontAccounting (TB_PREF) or default to '0_'
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        
        $this->tableName = $tableName ?? ($prefix . 'bi_transactions');
        $this->statementsTable = $statementsTable ?? ($prefix . 'bi_statements');
    }
    
    /**
     * Build SELECT query for transactions with filters
     * 
     * Builds a parameterized query for fetching transactions based on various filters.
     * Uses placeholders for safe parameter binding (prevents SQL injection).
     * 
     * @param array $filters Associative array of filters:
     *                       - 'status' (int|null): Transaction status (null = all)
     *                       - 'dateFrom' (string|null): Start date (YYYY-MM-DD)
     *                       - 'dateTo' (string|null): End date (YYYY-MM-DD)
     *                       - 'amountMin' (float|null): Minimum amount
     *                       - 'amountMax' (float|null): Maximum amount
     *                       - 'titleSearch' (string|null): Search in transaction title
     *                       - 'bankAccount' (string|null): Filter by bank account
     *                       - 'limit' (int|null): SQL LIMIT clause
     * 
     * @return array ['sql' => string, 'params' => array] Query and parameters for binding
     * 
     * @since 20251104
     * @version 20251104.1
     * 
     * @example
     * ```php
     * $result = $builder->buildGetTransactionsQuery([
     *     'status' => 1,
     *     'dateFrom' => '2025-01-01',
     *     'dateTo' => '2025-12-31',
     *     'limit' => 100
     * ]);
     * // Returns: ['sql' => '...', 'params' => [...]]
     * ```
     */
    public function buildGetTransactionsQuery(array $filters = []): array
    {
        $sql = "SELECT t.*, s.account AS our_account, s.currency 
                FROM {$this->tableName} t 
                LEFT JOIN {$this->statementsTable} s ON t.smt_id = s.id";
        
        $whereClauses = [];
        $params = [];
        
        // Date range filter
        if (!empty($filters['dateFrom'])) {
            $whereClauses[] = "t.valueTimestamp >= ?";
            $params[] = $filters['dateFrom'];
        }
        
        if (!empty($filters['dateTo'])) {
            $whereClauses[] = "t.valueTimestamp < ?";
            $params[] = $filters['dateTo'];
        }
        
        // Status filter (255 = all statuses)
        if (isset($filters['status']) && $filters['status'] !== 255 && $filters['status'] !== null) {
            $whereClauses[] = "t.status = ?";
            $params[] = (int)$filters['status'];
        }
        
        // Amount range filter
        if (isset($filters['amountMin'])) {
            $whereClauses[] = "ABS(t.transactionAmount) >= ?";
            $params[] = (float)$filters['amountMin'];
        }
        
        if (isset($filters['amountMax'])) {
            $whereClauses[] = "ABS(t.transactionAmount) <= ?";
            $params[] = (float)$filters['amountMax'];
        }
        
        // Title search filter (wildcards for LIKE)
        if (!empty($filters['titleSearch'])) {
            $whereClauses[] = "t.transactionTitle LIKE ?";
            $params[] = '%' . $filters['titleSearch'] . '%';
        }
        
        // Bank account filter
        if (!empty($filters['bankAccount']) && $filters['bankAccount'] !== 'ALL') {
            $whereClauses[] = "s.account = ?";
            $params[] = $filters['bankAccount'];
        }
        
        // Add WHERE clause if we have filters
        if (count($whereClauses) > 0) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        // Order by date and ID
        $sql .= " ORDER BY t.valueTimestamp ASC, t.id ASC";
        
        // Limit clause
        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
        }
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Build SELECT query for single transaction by ID
     * 
     * @param int $transactionId The transaction ID
     * 
     * @return array ['sql' => string, 'params' => array]
     * 
     * @since 20251104
     */
    public function buildGetTransactionQuery(int $transactionId): array
    {
        $sql = "SELECT t.*, s.account AS our_account, s.currency 
                FROM {$this->tableName} t 
                LEFT JOIN {$this->statementsTable} s ON t.smt_id = s.id
                WHERE t.id = ?";
        
        return [
            'sql' => $sql,
            'params' => [$transactionId]
        ];
    }
    
    /**
     * Build UPDATE query to reset transaction status
     * 
     * Used when voiding FA transactions - resets bi_transactions status.
     * 
     * @param array $transactionIds Array of transaction IDs to reset
     * @param int $faTransNo FA transaction number to record
     * @param int $faTransType FA transaction type to record
     * 
     * @return array ['sql' => string, 'params' => array]
     * 
     * @since 20251104
     */
    public function buildResetTransactionsQuery(array $transactionIds, int $faTransNo, int $faTransType): array
    {
        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        
        $sql = "UPDATE {$this->tableName} 
                SET status = 0,
                    fa_trans_no = ?,
                    fa_trans_type = ?,
                    matched = 0,
                    created = 0
                WHERE id IN ($placeholders)";
        
        $params = array_merge([$faTransNo, $faTransType], $transactionIds);
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Build UPDATE query to update transaction with FA GL info
     * 
     * Links bi_transactions to FA general ledger transactions.
     * 
     * @param array $transactionIds Array of transaction IDs to update
     * @param int $status New status (1 = settled, 0 = unsettled)
     * @param int $faTransNo FA transaction number
     * @param int $faTransType FA transaction type
     * @param bool $matched Whether transaction was matched
     * @param bool $created Whether transaction was created
     * @param string|null $partnerType Partner type code (SP/CU/BT/QE)
     * @param string $partnerOption Partner option/ID
     * 
     * @return array ['sql' => string, 'params' => array]
     * 
     * @since 20251104
     * @version 20251104.1
     */
    public function buildUpdateTransactionsQuery(
        array $transactionIds,
        int $status,
        int $faTransNo,
        int $faTransType,
        bool $matched = false,
        bool $created = false,
        ?string $partnerType = null,
        string $partnerOption = ''
    ): array {
        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        
        $sql = "UPDATE {$this->tableName} 
                SET status = ?,
                    fa_trans_no = ?,
                    fa_trans_type = ?";
        
        $params = [$status, $faTransNo, $faTransType];
        
        // Add matched or created flag
        if ($matched) {
            $sql .= ", matched = 1";
        } elseif ($created) {
            $sql .= ", created = 1";
        }
        
        // Add partner type information (Mantis #2933)
        if ($partnerType !== null) {
            $sql .= ", g_partner = ?, g_option = ?";
            $params[] = $partnerType;
            $params[] = $partnerOption;
        }
        
        $sql .= " WHERE id IN ($placeholders)";
        $params = array_merge($params, $transactionIds);
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Build UPDATE query for prevoid hook
     * 
     * Resets transactions when FA GL entry is being voided.
     * 
     * @param int $faTransNo FA transaction number
     * @param int $faTransType FA transaction type
     * 
     * @return array ['sql' => string, 'params' => array]
     * 
     * @since 20251104
     */
    public function buildPrevoidQuery(int $faTransNo, int $faTransType): array
    {
        $sql = "UPDATE {$this->tableName} 
                SET status = 0,
                    fa_trans_no = 0,
                    fa_trans_type = 0,
                    created = 0,
                    matched = 0,
                    g_partner = '',
                    g_option = ''
                WHERE fa_trans_no = ? 
                  AND fa_trans_type = ? 
                  AND status = 1";
        
        return [
            'sql' => $sql,
            'params' => [$faTransNo, $faTransType]
        ];
    }
    
    /**
     * Build SELECT query for normal pairing patterns
     * 
     * Gets aggregated transaction patterns for counterparty matching.
     * 
     * @param string|null $account Optional account filter
     * 
     * @return array ['sql' => string, 'params' => array]
     * 
     * @since 20240729 (original function date)
     * @version 20251104.1 (refactored into QueryBuilder)
     */
    public function buildNormalPairingQuery(?string $account = null): array
    {
        $sql = "SELECT COUNT(*) as count, account, g_option, g_partner 
                FROM {$this->tableName} 
                GROUP BY account, g_option, g_partner";
        
        $params = [];
        
        if ($account !== null) {
            $sql .= " WHERE account = ?";
            $params[] = $account;
        }
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Get table name
     * 
     * @return string
     * 
     * @since 20251104
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
    
    /**
     * Get statements table name
     * 
     * @return string
     * 
     * @since 20251104
     */
    public function getStatementsTableName(): string
    {
        return $this->statementsTable;
    }
}
