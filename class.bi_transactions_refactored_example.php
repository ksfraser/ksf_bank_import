<?php
/**
 * Refactored bi_transactions_model using SOLID principles
 * 
 * Demonstrates how to inject and use TransactionQueryBuilder and TransactionRepository
 * to follow Dependency Injection and Single Responsibility principles.
 * 
 * This is a demonstration/migration guide. The actual refactoring should be done
 * incrementally in class.bi_transactions.php
 * 
 * @package    KSF_BankImport
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251104
 * @version    20251104.1
 */

require_once(__DIR__ . '/src/Ksfraser/FaBankImport/database/TransactionQueryBuilder.php');
require_once(__DIR__ . '/src/Ksfraser/FaBankImport/database/TransactionRepository.php');

use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;
use Ksfraser\FaBankImport\Database\TransactionRepository;

/**
 * Example: Refactored bi_transactions_model with Dependency Injection
 * 
 * @since 20251104
 * @version 20251104.1
 */
class bi_transactions_model_refactored extends generic_fa_interface_model
{
    /** @var TransactionQueryBuilder */
    private $queryBuilder;
    
    /** @var TransactionRepository */
    private $repository;
    
    /**
     * Constructor with Dependency Injection
     * 
     * @param TransactionQueryBuilder|null $queryBuilder Optional query builder (for testing)
     * @param TransactionRepository|null $repository Optional repository (for testing)
     * 
     * @since 20251104
     */
    public function __construct(
        ?TransactionQueryBuilder $queryBuilder = null,
        ?TransactionRepository $repository = null
    ) {
        parent::__construct();
        
        // Create dependencies if not injected (for backward compatibility)
        if ($queryBuilder === null) {
            $queryBuilder = new TransactionQueryBuilder();
        }
        if ($repository === null) {
            $repository = new TransactionRepository($queryBuilder);
        }
        
        $this->queryBuilder = $queryBuilder;
        $this->repository = $repository;
    }
    
    /**
     * Get transactions with filters - REFACTORED VERSION
     * 
     * Before: Mixed SQL generation, execution, and business logic
     * After: Uses Repository for data access, follows SRP
     * 
     * @param int|null $status Transaction status filter
     * @param string|null $transAfterDate Start date filter
     * @param string|null $transToDate End date filter
     * @param float|null $transactionAmount Amount filter
     * @param string|null $transactionTitle Title search filter
     * @param int|null $limit Result limit
     * @param string|null $bankAccount Bank account filter
     * 
     * @return array Grouped transactions by transaction code
     * 
     * @since 20251104
     * @version 20251104.1
     * 
     * @example
     * ```php
     * $model = new bi_transactions_model_refactored();
     * $transactions = $model->get_transactions(
     *     1,              // status
     *     '2025-01-01',   // dateFrom
     *     '2025-12-31',   // dateTo
     *     null,           // amount
     *     'payment',      // title
     *     100,            // limit
     *     '1234567'       // account
     * );
     * ```
     */
    public function get_transactions(
        $status = null,
        $transAfterDate = null,
        $transToDate = null,
        $transactionAmount = null,
        $transactionTitle = null,
        $limit = null,
        $bankAccount = null
    ) {
        // Handle POST defaults (legacy behavior)
        if ($transAfterDate === null) {
            $transAfterDate = $_POST['TransAfterDate'] ?? null;
        }
        if ($transToDate === null) {
            $transToDate = $_POST['TransToDate'] ?? null;
        }
        if ($bankAccount === null) {
            $bankAccount = $_POST['bankAccountFilter'] ?? 'ALL';
        }
        
        // Build filters array for Repository
        $filters = [];
        
        if ($status !== null) {
            $filters['status'] = $status;
        }
        if ($transAfterDate !== null) {
            $filters['dateFrom'] = $transAfterDate;
        }
        if ($transToDate !== null) {
            $filters['dateTo'] = $transToDate;
        }
        if ($transactionTitle !== null) {
            $filters['titleSearch'] = $transactionTitle;
        }
        if ($bankAccount !== null && $bankAccount !== 'ALL') {
            $filters['bankAccount'] = $bankAccount;
        }
        if ($limit !== null) {
            $filters['limit'] = $limit;
        } elseif (isset($this->limit)) {
            $filters['limit'] = $this->limit;
        }
        
        // Use Repository to fetch data (SRP - Repository handles data access)
        $rows = $this->repository->findByFilters($filters);
        
        // Business logic: Group by transaction code (kept in model)
        $trzs = [];
        foreach ($rows as $myrow) {
            $trz_code = $myrow['transactionCode'];
            if (!isset($trzs[$trz_code])) {
                $trzs[$trz_code] = [];
            }
            $trzs[$trz_code][] = $myrow;
        }
        
        return $trzs;
    }
    
    /**
     * Get single transaction by ID - REFACTORED VERSION
     * 
     * Before: Inline SQL with db_escape
     * After: Uses Repository, cleaner and more testable
     * 
     * @param int|null $tid Transaction ID
     * @param bool $bSetInternal Whether to set internal object properties
     * 
     * @return array|null Transaction data or null if not found
     * 
     * @throws Exception If no ID provided
     * 
     * @since 20251104
     */
    public function get_transaction($tid = null, $bSetInternal = false)
    {
        // Handle default ID from object property
        if ($tid === null) {
            if (isset($this->id)) {
                $tid = $this->id;
            } else {
                throw new Exception("No ID set to search for");
            }
        }
        
        // Use Repository to fetch transaction (SRP - Repository handles data access)
        $res = $this->repository->findById($tid);
        
        // Business logic: Optionally set internal properties
        if ($bSetInternal && $res !== null) {
            $this->arr2obj($res);
        }
        
        return $res;
    }
    
    /**
     * Get normal pairing patterns - REFACTORED VERSION
     * 
     * Before: Hardcoded table prefix '0_', inline SQL
     * After: Uses Repository with TB_PREF support
     * 
     * @param string|null $account Optional account filter
     * 
     * @return array Pairing patterns with counts
     * 
     * @since 20240729 (original)
     * @version 20251104.1 (refactored)
     */
    public function get_normal_pairing($account = null)
    {
        // Use Repository (handles TB_PREF automatically)
        return $this->repository->findNormalPairing($account);
    }
    
    /**
     * Update transactions with FA GL information - REFACTORED VERSION
     * 
     * Before: Inline SQL UPDATE statements
     * After: Uses Repository for data access
     * 
     * @param array $transactionIds Array of transaction IDs to update
     * @param int $status New status
     * @param int $faTransNo FA transaction number
     * @param int $faTransType FA transaction type
     * @param bool $matched Whether matched
     * @param bool $created Whether created
     * @param string|null $partnerType Partner type (Mantis #2933)
     * @param string $partnerOption Partner option
     * 
     * @return int Number of rows updated
     * 
     * @since 20251104
     */
    public function update_transactions(
        array $transactionIds,
        int $status,
        int $faTransNo,
        int $faTransType,
        bool $matched = false,
        bool $created = false,
        ?string $partnerType = null,
        string $partnerOption = ''
    ): int {
        // Use Repository (SRP - Repository handles data access)
        return $this->repository->update(
            $transactionIds,
            $status,
            $faTransNo,
            $faTransType,
            $matched,
            $created,
            $partnerType,
            $partnerOption
        );
    }
    
    /**
     * Reset transactions - REFACTORED VERSION
     * 
     * @param array $transactionIds Transaction IDs to reset
     * @param int $faTransNo FA transaction number
     * @param int $faTransType FA transaction type
     * 
     * @return int Number of rows affected
     * 
     * @since 20251104
     */
    public function reset_transactions(
        array $transactionIds,
        int $faTransNo,
        int $faTransType
    ): int {
        return $this->repository->reset($transactionIds, $faTransNo, $faTransType);
    }
    
    /**
     * Prevoid hook for FA - REFACTORED VERSION
     * 
     * @param int $faTransNo FA transaction number
     * @param int $faTransType FA transaction type
     * 
     * @return int Number of rows affected
     * 
     * @since 20251104
     */
    public function db_prevoid(int $faTransNo, int $faTransType): int
    {
        return $this->repository->prevoid($faTransNo, $faTransType);
    }
}

/*******************************************************************************
 * BENEFITS OF REFACTORED VERSION:
 * 
 * 1. SINGLE RESPONSIBILITY PRINCIPLE (SRP):
 *    - QueryBuilder: Only builds SQL queries
 *    - Repository: Only executes queries and returns data
 *    - Model: Only handles business logic (grouping, validation)
 * 
 * 2. DEPENDENCY INJECTION:
 *    - Constructor accepts QueryBuilder and Repository
 *    - Can inject mocks for testing (no database needed!)
 *    - Can swap implementations without changing model code
 * 
 * 3. OPEN/CLOSED PRINCIPLE:
 *    - Can extend QueryBuilder with new query types
 *    - Model doesn't need to change when adding new queries
 * 
 * 4. DRY (Don't Repeat Yourself):
 *    - SQL generation centralized in QueryBuilder
 *    - No duplicate query logic across methods
 * 
 * 5. TESTABILITY:
 *    - Can unit test QueryBuilder without database (20 tests passing!)
 *    - Can test model with mocked Repository
 *    - Integration tests use real database
 * 
 * 6. TB_PREF SUPPORT:
 *    - Automatically handles different company prefixes
 *    - No hardcoded '0_' table prefixes
 *    - Works with any FrontAccounting installation
 * 
 * 7. MAINTAINABILITY:
 *    - Changes to SQL logic happen in one place (QueryBuilder)
 *    - Clear separation of concerns
 *    - Easier to understand and modify
 * 
 * MIGRATION PATH:
 * 
 * Option 1: Gradual Migration (Recommended)
 * - Add QueryBuilder and Repository as optional dependencies
 * - Refactor one method at a time
 * - Keep old methods working during transition
 * - Add @deprecated tags to old methods
 * 
 * Option 2: Feature Flag
 * - Add a flag to enable new architecture
 * - Run both implementations in parallel
 * - Verify results match
 * - Switch to new implementation when confident
 * 
 * Option 3: Complete Rewrite
 * - Create new class (bi_transactions_model_v2)
 * - Update all calling code
 * - Remove old class when complete
 ******************************************************************************/
