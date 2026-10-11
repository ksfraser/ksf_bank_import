<?php
/**
 * Integration tests for TransactionRepository
 * 
 * Tests the repository pattern implementation for bi_transactions table,
 * ensuring proper handling of TB_PREF for multi-company support.
 * 
 * @package    KSF_BankImport
 * @subpackage Tests\Integration
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251104
 * @version    20251104.1
 */

namespace Tests\Integration;

use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;
use Ksfraser\FaBankImport\Database\TransactionRepository;
use Tests\Integration\DatabaseTestCase;

/**
 * Integration tests for TransactionRepository
 * 
 * @since 20251104
 * @version 20251104.1
 */
class TransactionRepositoryTest extends DatabaseTestCase
{
    /** @var TransactionRepository */
    private $repository;
    
    /** @var TransactionQueryBuilder */
    private $queryBuilder;
    
    /** @var string Table prefix from TB_PREF */
    private $tablePrefix;
    
    /**
     * Set up test environment
     * 
     * @return void
     * @since 20251104
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Get the actual TB_PREF from FrontAccounting
        $this->tablePrefix = defined('TB_PREF') ? TB_PREF : '0_';
        
        $this->queryBuilder = new TransactionQueryBuilder(
            $this->tablePrefix . 'bi_transactions',
            $this->tablePrefix . 'bi_statements'
        );
        
        $this->repository = new TransactionRepository($this->queryBuilder);
    }
    
    /**
     * Test repository can be instantiated with QueryBuilder
     * 
     * @return void
     * @since 20251104
     */
    public function testRepositoryInstantiation(): void
    {
        $this->assertInstanceOf(TransactionRepository::class, $this->repository);
    }
    
    /**
     * Test findAll returns all transactions
     * 
     * @return void
     * @since 20251104
     */
    public function testFindAllTransactions(): void
    {
        $transactions = $this->repository->findAll();
        
        $this->assertIsArray($transactions);
        // Database should have some test data
        $this->assertGreaterThan(0, count($transactions));
    }
    
    /**
     * Test findById returns single transaction
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByIdReturnsSingleTransaction(): void
    {
        // Get first transaction to test with
        $allTransactions = $this->repository->findAll();
        $this->assertNotEmpty($allTransactions, 'Need at least one transaction for testing');
        
        $firstTransaction = $allTransactions[0];
        $transactionId = $firstTransaction['id'];
        
        $transaction = $this->repository->findById($transactionId);
        
        $this->assertIsArray($transaction);
        $this->assertEquals($transactionId, $transaction['id']);
        $this->assertArrayHasKey('valueTimestamp', $transaction);
        $this->assertArrayHasKey('amount', $transaction);
    }
    
    /**
     * Test findById returns null for non-existent transaction
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByIdReturnsNullForNonExistent(): void
    {
        $transaction = $this->repository->findById(999999);
        
        $this->assertNull($transaction);
    }
    
    /**
     * Test findByFilters with status filter
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithStatus(): void
    {
        $transactions = $this->repository->findByFilters(['status' => 1]);
        
        $this->assertIsArray($transactions);
        
        // Verify all returned transactions have status = 1
        foreach ($transactions as $transaction) {
            $this->assertEquals(1, $transaction['status']);
        }
    }
    
    /**
     * Test findByFilters with date range
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithDateRange(): void
    {
        $transactions = $this->repository->findByFilters([
            'dateFrom' => '2024-01-01',
            'dateTo' => '2025-12-31'
        ]);
        
        $this->assertIsArray($transactions);
        
        // Verify all transactions are within date range
        foreach ($transactions as $transaction) {
            $valueDate = $transaction['valueTimestamp'];
            $this->assertGreaterThanOrEqual('2024-01-01', substr($valueDate, 0, 10));
            $this->assertLessThanOrEqual('2025-12-31', substr($valueDate, 0, 10));
        }
    }
    
    /**
     * Test findByFilters with amount range
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithAmountRange(): void
    {
        $transactions = $this->repository->findByFilters([
            'amountMin' => 100.00,
            'amountMax' => 1000.00
        ]);
        
        $this->assertIsArray($transactions);
        
        // Verify all transactions are within amount range
        foreach ($transactions as $transaction) {
            $amount = abs((float)$transaction['amount']);
            $this->assertGreaterThanOrEqual(100.00, $amount);
            $this->assertLessThanOrEqual(1000.00, $amount);
        }
    }
    
    /**
     * Test findByFilters with title search
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithTitleSearch(): void
    {
        // First get a transaction to know what title to search for
        $allTransactions = $this->repository->findAll();
        $this->assertNotEmpty($allTransactions);
        
        $searchTitle = $allTransactions[0]['title'];
        $searchWord = explode(' ', $searchTitle)[0]; // Get first word
        
        $transactions = $this->repository->findByFilters([
            'titleSearch' => $searchWord
        ]);
        
        $this->assertIsArray($transactions);
        $this->assertGreaterThan(0, count($transactions));
    }
    
    /**
     * Test findByFilters with bank account filter
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithBankAccount(): void
    {
        // Get all transactions to find a valid account
        $allTransactions = $this->repository->findAll();
        $this->assertNotEmpty($allTransactions);
        
        // Find a transaction with an account
        $account = null;
        foreach ($allTransactions as $transaction) {
            if (!empty($transaction['our_account'])) {
                $account = $transaction['our_account'];
                break;
            }
        }
        
        if ($account) {
            $transactions = $this->repository->findByFilters([
                'bankAccount' => $account
            ]);
            
            $this->assertIsArray($transactions);
            
            // Verify all have matching account
            foreach ($transactions as $transaction) {
                $this->assertEquals($account, $transaction['our_account']);
            }
        } else {
            $this->markTestSkipped('No transactions with bank account found');
        }
    }
    
    /**
     * Test findByFilters with limit
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithLimit(): void
    {
        $limit = 5;
        $transactions = $this->repository->findByFilters(['limit' => $limit]);
        
        $this->assertIsArray($transactions);
        $this->assertLessThanOrEqual($limit, count($transactions));
    }
    
    /**
     * Test findByFilters with multiple filters combined
     * 
     * @return void
     * @since 20251104
     */
    public function testFindByFiltersWithMultipleFilters(): void
    {
        $transactions = $this->repository->findByFilters([
            'status' => 1,
            'dateFrom' => '2024-01-01',
            'limit' => 10
        ]);
        
        $this->assertIsArray($transactions);
        $this->assertLessThanOrEqual(10, count($transactions));
        
        foreach ($transactions as $transaction) {
            $this->assertEquals(1, $transaction['status']);
            $this->assertGreaterThanOrEqual('2024-01-01', substr($transaction['valueTimestamp'], 0, 10));
        }
    }
    
    /**
     * Test update method updates transaction
     * 
     * @return void
     * @since 20251104
     */
    public function testUpdateTransaction(): void
    {
        // Get a transaction to update
        $transactions = $this->repository->findAll();
        $this->assertNotEmpty($transactions);
        
        $transaction = $transactions[0];
        $originalStatus = $transaction['status'];
        $newStatus = $originalStatus == 1 ? 2 : 1; // Toggle status
        
        // Update the transaction
        $affected = $this->repository->update(
            [$transaction['id']],
            $newStatus,
            0,
            0,
            false,
            false,
            null,
            ''
        );
        
        $this->assertGreaterThan(0, $affected);
        
        // Verify the update
        $updated = $this->repository->findById($transaction['id']);
        $this->assertEquals($newStatus, $updated['status']);
        
        // Restore original status
        $this->repository->update(
            [$transaction['id']],
            $originalStatus,
            0,
            0,
            false,
            false,
            null,
            ''
        );
    }
    
    /**
     * Test reset method resets transactions
     * 
     * @return void
     * @since 20251104
     */
    public function testResetTransactions(): void
    {
        // Get a transaction to reset
        $transactions = $this->repository->findByFilters([
            'status' => 1,
            'limit' => 1
        ]);
        
        if (empty($transactions)) {
            $this->markTestSkipped('No status=1 transactions available for testing');
            return;
        }
        
        $transaction = $transactions[0];
        
        // Reset the transaction
        $affected = $this->repository->reset(
            [$transaction['id']],
            123, // Test FA trans no
            456  // Test FA trans type
        );
        
        $this->assertGreaterThan(0, $affected);
        
        // Verify the reset (status should be 0, FA fields cleared)
        $reset = $this->repository->findById($transaction['id']);
        $this->assertEquals(0, $reset['status']);
        $this->assertEquals(123, $reset['fa_trans_no']);
        $this->assertEquals(456, $reset['fa_trans_type']);
    }
    
    /**
     * Test repository handles TB_PREF correctly for different company prefixes
     * 
     * @return void
     * @since 20251104
     */
    public function testRepositoryHandlesDifferentTablePrefixes(): void
    {
        // Test with different prefixes
        $prefixes = ['0_', '1_', 'test_'];
        
        foreach ($prefixes as $prefix) {
            $builder = new TransactionQueryBuilder(
                $prefix . 'bi_transactions',
                $prefix . 'bi_statements'
            );
            
            $repo = new TransactionRepository($builder);
            
            // Should not throw error even if tables don't exist
            $this->assertInstanceOf(TransactionRepository::class, $repo);
            
            // Verify the table names are correct
            $this->assertEquals($prefix . 'bi_transactions', $builder->getTableName());
            $this->assertEquals($prefix . 'bi_statements', $builder->getStatementsTableName());
        }
    }
    
    /**
     * Test findNormalPairing returns pairing patterns
     * 
     * @return void
     * @since 20251104
     */
    public function testFindNormalPairing(): void
    {
        $pairings = $this->repository->findNormalPairing();
        
        $this->assertIsArray($pairings);
        
        // Each pairing should have required fields
        foreach ($pairings as $pairing) {
            $this->assertArrayHasKey('our_account', $pairing);
            $this->assertArrayHasKey('g_option', $pairing);
            $this->assertArrayHasKey('g_partner', $pairing);
            $this->assertArrayHasKey('transaction_count', $pairing);
        }
    }
    
    /**
     * Test findNormalPairing with specific account
     * 
     * @return void
     * @since 20251104
     */
    public function testFindNormalPairingWithAccount(): void
    {
        // Get an account to test with
        $transactions = $this->repository->findAll();
        $this->assertNotEmpty($transactions);
        
        $account = null;
        foreach ($transactions as $transaction) {
            if (!empty($transaction['our_account'])) {
                $account = $transaction['our_account'];
                break;
            }
        }
        
        if ($account) {
            $pairings = $this->repository->findNormalPairing($account);
            
            $this->assertIsArray($pairings);
            
            // All pairings should be for the specified account
            foreach ($pairings as $pairing) {
                $this->assertEquals($account, $pairing['our_account']);
            }
        } else {
            $this->markTestSkipped('No transactions with account found');
        }
    }
    
    /**
     * Test prevoid method handles FA transaction voids
     * 
     * @return void
     * @since 20251104
     */
    public function testPrevoidTransaction(): void
    {
        // This is a hook for FA, test that it executes without error
        $affected = $this->repository->prevoid(123, 456);
        
        // Should return number of affected rows (could be 0 if no matching transactions)
        $this->assertIsInt($affected);
        $this->assertGreaterThanOrEqual(0, $affected);
    }
}