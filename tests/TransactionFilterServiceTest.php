<?php
/**
 * Unit tests for TransactionFilterService
 * 
 * Mantis Bug #3188: Add bank account filter to process_statements
 * 
 * @package    KsfBankImport
 * @subpackage Tests
 * @category   Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 */

namespace KsfBankImport\Tests\Services {
    use PHPUnit\Framework\TestCase;
    
    // Load centralized FA function stubs
    require_once(__DIR__ . '/helpers/fa_functions.php');
    
    require_once(__DIR__ . '/../Services/TransactionFilterService.php');
    
    use KsfBankImport\Services\TransactionFilterService;
    
    /**
     * Test suite for TransactionFilterService
     * 
     * Tests the filtering logic for bank import transactions
     * including date range, status, and bank account filters.
     * 
     * @since 1.0.0
     */
    class TransactionFilterServiceTest extends TestCase
    {
        /**
         * Service instance
         * 
         * @var TransactionFilterService
         */
        private $service;
        
        /**
         * Set up test fixtures
         * 
         * @return void
         */
        protected function setUp(): void
        {
            $this->service = new TransactionFilterService();
        }
        
        /**
         * Test building SQL WHERE clause with all filters
         * 
         * @return void
         */
        public function testBuildWhereClauseWithAllFilters()
        {
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',  // startDate
                '2025-10-31',  // endDate
                0,             // status (unsettled)
                'ACC123'       // bankAccount
            );
            
            // Should include date range
            $this->assertStringContainsString("t.valueTimestamp >= '2025-10-01'", $whereClause);
            $this->assertStringContainsString("t.valueTimestamp < '2025-10-31'", $whereClause);
            
            // Should include status filter
            $this->assertStringContainsString("t.status = '0'", $whereClause);
            
            // Should include bank account filter
            $this->assertStringContainsString("s.account = 'ACC123'", $whereClause);
        }
        
        /**
         * Test building SQL WHERE clause without bank account filter (ALL)
         * 
         * @return void
         */
        public function testBuildWhereClauseWithoutBankAccountFilter()
        {
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',  // startDate
                '2025-10-31',  // endDate
                0,             // status
                'ALL'          // bankAccount - should not filter
            );
            
            // Should NOT include bank account filter when 'ALL'
            $this->assertStringNotContainsString("s.account =", $whereClause);
            
            // Should still include date and status
            $this->assertStringContainsString("t.valueTimestamp", $whereClause);
            $this->assertStringContainsString("t.status", $whereClause);
        }
        
        /**
         * Test building SQL WHERE clause with null bank account (ALL)
         * 
         * @return void
         */
        public function testBuildWhereClauseWithNullBankAccount()
        {
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',
                '2025-10-31',
                0,
                null  // null means ALL
            );
            
            // Should NOT include bank account filter when null
            $this->assertStringNotContainsString("s.account =", $whereClause);
        }
        
        /**
         * Test building WHERE clause without status filter (255 = ALL)
         * 
         * @return void
         */
        public function testBuildWhereClauseWithoutStatusFilter()
        {
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',
                '2025-10-31',
                255,  // status = ALL
                'ACC123'
            );
            
            // Should NOT include status filter when 255
            $this->assertStringNotContainsString("t.status =", $whereClause);
            
            // Should include date and bank account
            $this->assertStringContainsString("t.valueTimestamp", $whereClause);
            $this->assertStringContainsString("s.account", $whereClause);
        }
        
        /**
         * Test building WHERE clause with status null (show all)
         * 
         * @return void
         */
        public function testBuildWhereClauseWithNullStatus()
        {
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',
                '2025-10-31',
                null,  // status = null means ALL
                'ACC123'
            );
            
            // Should NOT include status filter when null
            $this->assertStringNotContainsString("t.status", $whereClause);
        }
        
        /**
         * Test sanitization of bank account input
         * 
         * @return void
         */
        public function testSanitizesBankAccountInput()
        {
            // Test with potential SQL injection attempt
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',
                '2025-10-31',
                0,
                "ACC123'; DROP TABLE bi_transactions; --"
            );
            
            // Should escape the single quote
            // The mock db_escape uses addslashes, so the quote should be escaped
            $this->assertStringContainsString("\\'", $whereClause);
        }
        
        /**
         * Test WHERE clause starts with WHERE keyword
         * 
         * @return void
         */
        public function testWhereClauseStartsWithWhereKeyword()
        {
            $whereClause = $this->service->buildWhereClause(
                '2025-10-01',
                '2025-10-31',
                0,
                'ACC123'
            );
            
            $this->assertStringStartsWith(' WHERE ', $whereClause);
        }
        
        /**
         * Test extracting filter parameters from POST data
         * 
         * @return void
         */
        public function testExtractFiltersFromPost()
        {
            $_POST = [
                'TransAfterDate' => '2025-10-01',
                'TransToDate' => '2025-10-31',
                'statusFilter' => 0,
                'bankAccountFilter' => 'ACC123'
            ];
            
            $filters = $this->service->extractFiltersFromPost($_POST);
            
            $this->assertEquals('2025-10-01', $filters['startDate']);
            $this->assertEquals('2025-10-31', $filters['endDate']);
            $this->assertEquals(0, $filters['status']);
            $this->assertEquals('ACC123', $filters['bankAccount']);
        }
        
        /**
         * Test extracting filters with defaults when POST is empty
         * 
         * @return void
         */
        public function testExtractFiltersWithDefaults()
        {
            $_POST = [];
            
            $filters = $this->service->extractFiltersFromPost($_POST);
            
            // Should have default values
            $this->assertArrayHasKey('startDate', $filters);
            $this->assertArrayHasKey('endDate', $filters);
            $this->assertEquals(0, $filters['status']); // Default unsettled
            $this->assertEquals('ALL', $filters['bankAccount']); // Default ALL
        }
        
        /**
         * Test that service validates date format
         * 
         * @return void
         */
        public function testValidatesDateFormat()
        {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid start date format');
            
            $this->service->buildWhereClause(
                'invalid-date',
                '2025-10-31',
                0,
                'ACC123'
            );
        }
        
        /**
         * Test that service validates end date format
         * 
         * @return void
         */
        public function testValidatesEndDateFormat()
        {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid end date format');
            
            $this->service->buildWhereClause(
                '2025-10-01',
                'bad-date',
                0,
                'ACC123'
            );
        }
        
        // ========================================================================
        // Tests for FUTURE filter methods (scaffolded but not yet in main flow)
        // ========================================================================
        
        /**
         * Test amount range filter with minimum amount only
         * 
         * @return void
         */
        public function testBuildAmountConditionWithMinOnly()
        {
            // Use reflection to test private method
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildAmountCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 100.00, null);
            
            $this->assertStringContainsString('ABS(t.transactionAmount) >=', $condition);
            $this->assertStringContainsString('100', $condition);
        }
        
        /**
         * Test amount range filter with maximum amount only
         * 
         * @return void
         */
        public function testBuildAmountConditionWithMaxOnly()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildAmountCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, null, 500.00);
            
            $this->assertStringContainsString('ABS(t.transactionAmount) <=', $condition);
            $this->assertStringContainsString('500', $condition);
        }
        
        /**
         * Test amount range filter with both min and max (FUTURE)
         * 
         * @return void
         */
        public function testBuildAmountConditionWithBothMinMax()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildAmountCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 100.00, 500.00);
            
            $this->assertStringContainsString('>=', $condition);
            $this->assertStringContainsString('<=', $condition);
            $this->assertStringContainsString('100', $condition);
            $this->assertStringContainsString('500', $condition);
        }
        
        /**
         * Test amount range filter returns empty when no amounts provided (FUTURE)
         * 
         * @return void
         */
        public function testBuildAmountConditionReturnsEmptyWhenNoAmounts()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildAmountCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, null, null);
            
            $this->assertEmpty($condition);
        }
        
        /**
         * Test amount range filter auto-swaps when min > max
         * 
         * @return void
         */
        public function testBuildAmountConditionAutoSwapsWhenMinGreaterThanMax()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildAmountCondition');
            $method->setAccessible(true);
            
            // Should auto-swap: 500 and 100 -> min=100, max=500
            $condition = $method->invoke($this->service, 500.00, 100.00);
            
            $this->assertStringContainsString('>=', $condition);
            $this->assertStringContainsString('<=', $condition);
            $this->assertStringContainsString('100', $condition);
            $this->assertStringContainsString('500', $condition);
            // Should use ABS
            $this->assertStringContainsString('ABS', $condition);
        }
        
        /**
         * Test amount range filter uses ABS for absolute values
         * 
         * @return void
         */
        public function testBuildAmountConditionUsesAbsoluteValues()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildAmountCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, -100.00, 500.00);
            
            // Should use ABS() in SQL
            $this->assertStringContainsString('ABS(t.transactionAmount)', $condition);
            // Should use absolute values in comparison
            $this->assertStringContainsString('100', $condition);
            $this->assertStringContainsString('500', $condition);
        }
        
        /**
         * Test title search filter with search text (FUTURE)
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionWithText()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 'Amazon');
            
            $this->assertStringContainsString('t.transactionTitle LIKE', $condition);
            $this->assertStringContainsString('Amazon', $condition);
            $this->assertStringContainsString('%', $condition); // Wildcards
        }
        
        /**
         * Test title search filter returns empty when no search text (FUTURE)
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionReturnsEmptyWhenNoText()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, null);
            
            $this->assertEmpty($condition);
        }
        
        /**
         * Test title search filter handles empty string (FUTURE)
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionReturnsEmptyForEmptyString()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, '');
            
            $this->assertEmpty($condition);
        }
        
        /**
         * Test title search filter trims whitespace
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionTrimsWhitespace()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, '  Amazon  ');
            
            $this->assertStringContainsString('Amazon', $condition);
            // Should search across multiple fields
            $this->assertStringContainsString('transactionTitle', $condition);
            $this->assertStringContainsString('memo', $condition);
            $this->assertStringContainsString('merchant', $condition);
        }
        
        /**
         * Test title search with multiple keywords (AND logic)
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionMultipleKeywords()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 'Amazon Prime');
            
            // Should contain both keywords
            $this->assertStringContainsString('Amazon', $condition);
            $this->assertStringContainsString('Prime', $condition);
            // Should use AND logic (both must match)
            $this->assertStringContainsString(' AND ', $condition);
            // Should search across all fields
            $this->assertStringContainsString('transactionTitle', $condition);
            $this->assertStringContainsString('memo', $condition);
            $this->assertStringContainsString('merchant', $condition);
        }
        
        /**
         * Test title search with exact phrase (quoted)
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionExactPhrase()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, '"Amazon Prime"');
            
            // Should search for exact phrase
            $this->assertStringContainsString('Amazon Prime', $condition);
            // Should search across all fields
            $this->assertStringContainsString('transactionTitle', $condition);
            $this->assertStringContainsString('memo', $condition);
            $this->assertStringContainsString('merchant', $condition);
        }
        
        /**
         * Test title search with exclusion using minus sign
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionExclusionMinus()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 'Amazon -Subscribe');
            
            // Should include Amazon
            $this->assertStringContainsString('Amazon', $condition);
            // Should exclude Subscribe using NOT LIKE
            $this->assertStringContainsString('Subscribe', $condition);
            $this->assertStringContainsString('NOT LIKE', $condition);
        }
        
        /**
         * Test title search with exclusion using exclamation mark
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionExclusionExclamation()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 'Amazon !Subscribe');
            
            // Should include Amazon
            $this->assertStringContainsString('Amazon', $condition);
            // Should exclude Subscribe using NOT LIKE
            $this->assertStringContainsString('Subscribe', $condition);
            $this->assertStringContainsString('NOT LIKE', $condition);
        }
        
        /**
         * Test title search with complex query (keywords + exact + exclusions)
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionComplexQuery()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 'Amazon "Prime Video" -Subscribe !Kindle');
            
            // Should include Amazon keyword
            $this->assertStringContainsString('Amazon', $condition);
            // Should include exact phrase
            $this->assertStringContainsString('Prime Video', $condition);
            // Should exclude both Subscribe and Kindle
            $this->assertStringContainsString('Subscribe', $condition);
            $this->assertStringContainsString('Kindle', $condition);
            $this->assertStringContainsString('NOT LIKE', $condition);
            // Should use AND logic
            $this->assertStringContainsString(' AND ', $condition);
        }
        
        /**
         * Test title search with only exclusions returns empty
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionOnlyExclusionsReturnsEmpty()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, '-Subscribe !Kindle');
            
            // Should return empty (no positive criteria)
            $this->assertEmpty($condition);
        }
        
        /**
         * Test title search searches all three fields
         * 
         * @return void
         */
        public function testBuildTitleSearchConditionSearchesAllFields()
        {
            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('buildTitleSearchCondition');
            $method->setAccessible(true);
            
            $condition = $method->invoke($this->service, 'Amazon');
            
            // Should search transactionTitle
            $this->assertStringContainsString('t.transactionTitle', $condition);
            // Should search memo
            $this->assertStringContainsString('t.memo', $condition);
            // Should search merchant
            $this->assertStringContainsString('t.merchant', $condition);
            // Should use OR logic for fields (keyword can match any field)
            $this->assertStringContainsString(' OR ', $condition);
        }
    }
}  // End of namespace KsfBankImport\Tests\Services
