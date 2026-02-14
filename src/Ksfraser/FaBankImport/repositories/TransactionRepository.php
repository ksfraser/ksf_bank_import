<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionRepository [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionRepository.
 */
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
     * @var string
     */
    private $tableName;
    
    /**
     * Constructor with optional dependency injection.
     *
     * If no QueryBuilder is provided (legacy usage), a default instance is created.
     *
     * @param TransactionQueryBuilder|null $queryBuilder The query builder to use
     *
     * @since 20251104
     */
    public function __construct(?TransactionQueryBuilder $queryBuilder = null)
    {
        $prefix = defined('TB_PREF') ? TB_PREF : '0_';
        $this->tableName = $prefix . 'bi_transactions';

        // Keep for legacy/batch operations; core CRUD methods below do not rely on it.
        $this->queryBuilder = $queryBuilder ?: new TransactionQueryBuilder();
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
        $sql = "SELECT * FROM {$this->tableName}";
        $result = db_query($sql, 'unable to get transactions');
        
        $transactions = [];
        while ($row = db_fetch($result)) {
            $transactions[] = $row;
        }
        
        return $transactions;
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
        $sql = "SELECT * FROM {$this->tableName} WHERE id = " . (int)$id;
        $result = db_query($sql, 'unable to get transaction');
        $row = db_fetch($result);
        return $row ? $row : null;
    }

    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE status = " . db_escape($status);
        $result = db_query($sql, 'unable to get transactions by status');

        $transactions = [];
        while ($row = db_fetch($result)) {
            $transactions[] = $row;
        }

        return $transactions;
    }

    public function save(array $transaction): bool
    {
        if (empty($transaction)) {
            return false;
        }

        $columns = [];
        $values = [];

        foreach ($transaction as $column => $value) {
            $columns[] = $column;

            if ($value === null) {
                $values[] = 'NULL';
            } elseif (is_bool($value)) {
                $values[] = $value ? '1' : '0';
            } elseif (is_numeric($value)) {
                $values[] = (string)$value;
            } else {
                $values[] = db_escape((string)$value);
            }
        }

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        db_query($sql, 'unable to save transaction');
        return true;
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $sets = [];
        foreach ($data as $column => $value) {
            if ($value === null) {
                $sets[] = $column . " = NULL";
            } elseif (is_bool($value)) {
                $sets[] = $column . " = " . ($value ? '1' : '0');
            } elseif (is_numeric($value)) {
                $sets[] = $column . " = " . (string)$value;
            } else {
                $sets[] = $column . " = " . db_escape((string)$value);
            }
        }

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $sets) . " WHERE id = " . (int)$id;
        db_query($sql, 'unable to update transaction');
        return true;
    }

    /**
     * Reset a single transaction back to an unprocessed state.
     *
     * Used by UnsetTransactionCommand.
     */
    public function reset(int $id): bool
    {
        $sql = "UPDATE {$this->tableName} SET status = " . db_escape('pending') . " WHERE id = " . (int)$id;
        db_query($sql, 'unable to reset transaction');
        return true;
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
     * Update transactions with FA GL information
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
    public function updateTransactionsWithFaInfo(
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
        
        return function_exists('db_affected_rows') ? db_affected_rows() : 0;
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
    public function resetTransactionsWithFaInfo(
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
        
        return function_exists('db_affected_rows') ? db_affected_rows() : 0;
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
        
        return function_exists('db_affected_rows') ? db_affected_rows() : 0;
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
