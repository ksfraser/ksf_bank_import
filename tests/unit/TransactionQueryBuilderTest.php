<?php
/**
 * Unit tests for TransactionQueryBuilder
 * 
 * Tests SQL query generation without database dependency.
 * Verifies proper handling of TB_PREF for multi-company support.
 * 
 * @package    KSF_BankImport
 * @subpackage Tests\Unit
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251104
 * @version    20251104.1
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;

/**
 * Unit tests for TransactionQueryBuilder
 * 
 * @since 20251104
 * @version 20251104.1
 */
class TransactionQueryBuilderTest extends TestCase
{
    /** @var TransactionQueryBuilder */
    private $builder;
    
    /**
     * Set up test environment
     * 
     * @return void
     * @since 20251104
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create builder with explicit table names
        $this->builder = new TransactionQueryBuilder(
            'test_bi_transactions',
            'test_bi_statements'
        );
    }
    
    /**
     * Test constructor with explicit table names
     * 
     * @return void
     * @since 20251104
     */
    public function testConstructorWithExplicitTableNames(): void
    {
        $builder = new TransactionQueryBuilder('custom_transactions', 'custom_statements');
        
        $this->assertEquals('custom_transactions', $builder->getTableName());
        $this->assertEquals('custom_statements', $builder->getStatementsTableName());
    }
    
    /**
     * Test constructor uses TB_PREF if defined
     * 
     * @return void
     * @since 20251104
     */
    public function testConstructorUsesTbPrefIfDefined(): void
    {
        // TB_PREF should be defined by FrontAccounting
        if (!defined('TB_PREF')) {
            define('TB_PREF', '0_');
        }
        
        $builder = new TransactionQueryBuilder();
        
        $this->assertEquals(TB_PREF . 'bi_transactions', $builder->getTableName());
        $this->assertEquals(TB_PREF . 'bi_statements', $builder->getStatementsTableName());
    }
    
    /**
     * Test buildGetTransactionsQuery with no filters
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithNoFilters(): void
    {
        $result = $this->builder->buildGetTransactionsQuery([]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('params', $result);
        
        $sql = $result['sql'];
        $this->assertStringContainsString('SELECT t.*, s.account AS our_account', $sql);
        $this->assertStringContainsString('FROM test_bi_transactions t', $sql);
        $this->assertStringContainsString('LEFT JOIN test_bi_statements', $sql);
        
        // No WHERE clause without filters
        $this->assertStringNotContainsString('WHERE', $sql);
    }
    
    /**
     * Test buildGetTransactionsQuery with status filter
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithStatus(): void
    {
        $result = $this->builder->buildGetTransactionsQuery(['status' => 1]);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('t.status = ?', $sql);
        $this->assertContains(1, $params);
    }
    
    /**
     * Test buildGetTransactionsQuery with date range
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithDateRange(): void
    {
        $result = $this->builder->buildGetTransactionsQuery([
            'dateFrom' => '2025-01-01',
            'dateTo' => '2025-12-31'
        ]);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('t.valueTimestamp >= ?', $sql);
        $this->assertStringContainsString('t.valueTimestamp < ?', $sql); // Uses < not <=
        $this->assertContains('2025-01-01', $params);
        $this->assertContains('2025-12-31', $params);
    }
    
    /**
     * Test buildGetTransactionsQuery with amount range
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithAmountRange(): void
    {
        $result = $this->builder->buildGetTransactionsQuery([
            'amountMin' => 100.00,
            'amountMax' => 1000.00
        ]);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('ABS(t.transactionAmount) >= ?', $sql); // Actual column name
        $this->assertStringContainsString('ABS(t.transactionAmount) <= ?', $sql);
        $this->assertContains(100.00, $params);
        $this->assertContains(1000.00, $params);
    }
    
    /**
     * Test buildGetTransactionsQuery with title search
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithTitleSearch(): void
    {
        $result = $this->builder->buildGetTransactionsQuery(['titleSearch' => 'payment']);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('t.transactionTitle LIKE ?', $sql); // Actual column name
        $this->assertContains('%payment%', $params);
    }
    
    /**
     * Test buildGetTransactionsQuery with bank account filter
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithBankAccount(): void
    {
        $result = $this->builder->buildGetTransactionsQuery(['bankAccount' => '1234567']);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('s.account = ?', $sql);
        $this->assertContains('1234567', $params);
    }
    
    /**
     * Test buildGetTransactionsQuery with limit
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithLimit(): void
    {
        $result = $this->builder->buildGetTransactionsQuery(['limit' => 50]);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('LIMIT ?', $sql);
        $this->assertContains(50, $params);
    }
    
    /**
     * Test buildGetTransactionsQuery with multiple filters
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionsQueryWithMultipleFilters(): void
    {
        $result = $this->builder->buildGetTransactionsQuery([
            'status' => 1,
            'dateFrom' => '2025-01-01',
            'dateTo' => '2025-12-31',
            'amountMin' => 100.00,
            'limit' => 100
        ]);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('t.status = ?', $sql);
        $this->assertStringContainsString('t.valueTimestamp >= ?', $sql);
        $this->assertStringContainsString('t.valueTimestamp < ?', $sql); // Uses < not <=
        $this->assertStringContainsString('ABS(t.transactionAmount) >= ?', $sql); // Actual column name
        $this->assertStringContainsString('LIMIT ?', $sql);
        
        $this->assertCount(5, $params);
    }
    
    /**
     * Test buildGetTransactionQuery returns single transaction query
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildGetTransactionQuery(): void
    {
        $result = $this->builder->buildGetTransactionQuery(123);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('SELECT t.*, s.account AS our_account', $sql);
        $this->assertStringContainsString('WHERE t.id = ?', $sql);
        $this->assertContains(123, $params);
        $this->assertCount(1, $params);
    }
    
    /**
     * Test buildResetTransactionsQuery
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildResetTransactionsQuery(): void
    {
        $result = $this->builder->buildResetTransactionsQuery([1, 2, 3], 123, 456);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('UPDATE test_bi_transactions', $sql);
        $this->assertStringContainsString('SET status = 0', $sql);
        $this->assertStringContainsString('fa_trans_no = ?', $sql);
        $this->assertStringContainsString('fa_trans_type = ?', $sql);
        $this->assertStringContainsString('WHERE id IN (?,?,?)', $sql); // No spaces in placeholders
        
        $this->assertContains(123, $params);
        $this->assertContains(456, $params);
        $this->assertContains(1, $params);
        $this->assertContains(2, $params);
        $this->assertContains(3, $params);
    }
    
    /**
     * Test buildUpdateTransactionsQuery
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildUpdateTransactionsQuery(): void
    {
        $result = $this->builder->buildUpdateTransactionsQuery(
            [1, 2],
            2,          // status
            789,        // faTransNo
            10,         // faTransType
            true,       // matched
            false,      // created - only one can be true (matched or created, not both)
            'customer', // partnerType
            'retail'    // partnerOption
        );
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('UPDATE test_bi_transactions', $sql);
        $this->assertStringContainsString('status = ?', $sql);
        $this->assertStringContainsString('fa_trans_no = ?', $sql);
        $this->assertStringContainsString('fa_trans_type = ?', $sql);
        $this->assertStringContainsString('matched = 1', $sql); // Hardcoded when matched=true
        $this->assertStringContainsString('g_partner = ?', $sql); // Mantis #2933 - actual column name
        $this->assertStringContainsString('WHERE id IN (?,?)', $sql);
        
        $this->assertContains(2, $params);   // status
        $this->assertContains(789, $params); // faTransNo
        $this->assertContains(10, $params);  // faTransType
        $this->assertContains('customer', $params);
        $this->assertContains('retail', $params);
    }
    
    /**
     * Test buildPrevoidQuery
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildPrevoidQuery(): void
    {
        $result = $this->builder->buildPrevoidQuery(123, 456);
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('UPDATE test_bi_transactions', $sql);
        $this->assertStringContainsString('status = 0', $sql);
        $this->assertStringContainsString('WHERE fa_trans_no = ?', $sql);
        $this->assertStringContainsString('AND fa_trans_type = ?', $sql);
        
        $this->assertContains(123, $params);
        $this->assertContains(456, $params);
    }
    
    /**
     * Test buildNormalPairingQuery without account
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildNormalPairingQueryWithoutAccount(): void
    {
        $result = $this->builder->buildNormalPairingQuery();
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('SELECT COUNT(*) as count', $sql); // Actual implementation
        $this->assertStringContainsString('GROUP BY', $sql);
        $this->assertStringContainsString('account, g_option, g_partner', $sql);
        $this->assertEmpty($params);
    }
    
    /**
     * Test buildNormalPairingQuery with account
     * 
     * @return void
     * @since 20251104
     */
    public function testBuildNormalPairingQueryWithAccount(): void
    {
        $result = $this->builder->buildNormalPairingQuery('1234567');
        
        $sql = $result['sql'];
        $params = $result['params'];
        
        $this->assertStringContainsString('WHERE account = ?', $sql); // No table alias
        $this->assertContains('1234567', $params);
        $this->assertCount(1, $params);
    }
    
    /**
     * Test getTableName returns correct table name
     * 
     * @return void
     * @since 20251104
     */
    public function testGetTableName(): void
    {
        $this->assertEquals('test_bi_transactions', $this->builder->getTableName());
    }
    
    /**
     * Test getStatementsTableName returns correct table name
     * 
     * @return void
     * @since 20251104
     */
    public function testGetStatementsTableName(): void
    {
        $this->assertEquals('test_bi_statements', $this->builder->getStatementsTableName());
    }
    
    /**
     * Test builder works with different TB_PREF values
     * 
     * @return void
     * @since 20251104
     */
    public function testBuilderWithDifferentPrefixes(): void
    {
        $prefixes = ['0_', '1_', '2_', 'company1_'];
        
        foreach ($prefixes as $prefix) {
            $builder = new TransactionQueryBuilder(
                $prefix . 'bi_transactions',
                $prefix . 'bi_statements'
            );
            
            $this->assertEquals($prefix . 'bi_transactions', $builder->getTableName());
            $this->assertEquals($prefix . 'bi_statements', $builder->getStatementsTableName());
            
            // Verify queries use the correct prefix
            $result = $builder->buildGetTransactionsQuery([]);
            $this->assertStringContainsString($prefix . 'bi_transactions', $result['sql']);
            $this->assertStringContainsString($prefix . 'bi_statements', $result['sql']);
        }
    }
    
    /**
     * Test all query methods return proper structure
     * 
     * @return void
     * @since 20251104
     */
    public function testAllQueryMethodsReturnProperStructure(): void
    {
        $methods = [
            ['buildGetTransactionsQuery', [[]]],
            ['buildGetTransactionQuery', [1]],
            ['buildResetTransactionsQuery', [[1], 123, 456]],
            ['buildUpdateTransactionsQuery', [[1], 1, 123, 456, false, false, null, '']],
            ['buildPrevoidQuery', [123, 456]],
            ['buildNormalPairingQuery', [null]]
        ];
        
        foreach ($methods as [$method, $args]) {
            $result = $this->builder->$method(...$args);
            
            $this->assertIsArray($result, "Method $method should return array");
            $this->assertArrayHasKey('sql', $result, "Method $method should have 'sql' key");
            $this->assertArrayHasKey('params', $result, "Method $method should have 'params' key");
            $this->assertIsString($result['sql'], "Method $method 'sql' should be string");
            $this->assertIsArray($result['params'], "Method $method 'params' should be array");
        }
    }
}
