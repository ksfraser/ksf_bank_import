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

/**
 * Repository for bi_transactions table
 * 
 * Provides data access methods using QueryBuilder for SQL generation.
 * Executes queries using FrontAccounting's db_query() function.
 * 
 * @since 20251104
 * @version 20251104.1
 */
class TransactionRepository
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
    public function update(
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
