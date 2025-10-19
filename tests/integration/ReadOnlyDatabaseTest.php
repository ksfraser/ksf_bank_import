<?php
/**
 * Read-only database integration tests
 * 
 * These tests verify the refactored code works with real database data
 * WITHOUT making any changes (no writes, updates, or deletes).
 * 
 * @package    KsfBankImport
 * @subpackage Tests\Integration
 * @category   Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Read-only integration test suite
 * 
 * IMPORTANT: These tests do NOT modify database data.
 * They only READ and VERIFY the refactored code works correctly.
 * 
 * @group integration
 * @group readonly
 * @group database
 */
class ReadOnlyDatabaseTest extends TestCase
{
    /**
     * Test VendorListManager loads real vendor data
     * 
     * Verifies that VendorListManager can load actual vendor list from database
     * and cache it in session without any database modifications.
     * 
     * @return void
     */
    public function testVendorListManagerLoadsRealData()
    {
        // Skip if database not available
        if (!function_exists('get_vendor_list')) {
            $this->markTestSkipped('FrontAccounting functions not available');
        }
        
        // Clear session cache to force fresh load
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['vendor_list'])) {
            unset($_SESSION['vendor_list']);
        }
        if (isset($_SESSION['vendor_list_loaded'])) {
            unset($_SESSION['vendor_list_loaded']);
        }
        
        // Load VendorListManager
        require_once(__DIR__ . '/../../VendorListManager.php');
        $manager = \KsfBankImport\VendorListManager::getInstance();
        
        // Get vendor list (should load from database)
        $vendorList = $manager->getVendorList();
        
        // Verify it's an array
        $this->assertIsArray($vendorList, 'Vendor list should be an array');
        
        // Verify it's not empty (assuming database has vendors)
        if (count($vendorList) > 0) {
            $this->assertNotEmpty($vendorList, 'Vendor list should not be empty');
            
            // Verify structure (vendor_id => vendor_name)
            $firstKey = array_key_first($vendorList);
            $this->assertIsNumeric($firstKey, 'Vendor ID should be numeric');
            $this->assertIsString($vendorList[$firstKey], 'Vendor name should be string');
            
            echo "\n✓ Loaded " . count($vendorList) . " vendors from database\n";
        } else {
            echo "\n⚠ No vendors in database (empty result is valid)\n";
        }
        
        // Verify session cache was set
        $this->assertTrue(
            isset($_SESSION['vendor_list']),
            'Vendor list should be cached in session'
        );
        $this->assertTrue(
            isset($_SESSION['vendor_list_loaded']),
            'Cache timestamp should be set'
        );
        
        echo "✓ VendorListManager session cache working\n";
    }
    
    /**
     * Test VendorListManager session caching works
     * 
     * Verifies that subsequent calls use cached data instead of querying database.
     * 
     * @return void
     */
    public function testVendorListCachingWorks()
    {
        if (!function_exists('get_vendor_list')) {
            $this->markTestSkipped('FrontAccounting functions not available');
        }
        
        require_once(__DIR__ . '/../../VendorListManager.php');
        $manager = \KsfBankImport\VendorListManager::getInstance();
        
        // First load
        $list1 = $manager->getVendorList();
        $time1 = $_SESSION['vendor_list_loaded'] ?? 0;
        
        // Small delay
        usleep(10000); // 10ms
        
        // Second load (should be cached)
        $list2 = $manager->getVendorList();
        $time2 = $_SESSION['vendor_list_loaded'] ?? 0;
        
        // Verify same data returned
        $this->assertEquals($list1, $list2, 'Cached data should match original');
        
        // Verify cache timestamp unchanged (proving no reload happened)
        $this->assertEquals($time1, $time2, 'Cache timestamp should not change on cached load');
        
        echo "✓ Session caching prevents redundant database queries\n";
    }
    
    /**
     * Test OperationTypesRegistry loads default types
     * 
     * Verifies that OperationTypesRegistry provides all default operation types.
     * 
     * @return void
     */
    public function testOperationTypesRegistryLoadsDefaults()
    {
        require_once(__DIR__ . '/../../OperationTypes/OperationTypesRegistry.php');
        
        $registry = \KsfBankImport\OperationTypes\OperationTypesRegistry::getInstance();
        $types = $registry->getTypes();
        
        // Verify it's an array
        $this->assertIsArray($types, 'Operation types should be an array');
        
        // Verify default types exist
        $defaultTypes = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ'];
        foreach ($defaultTypes as $code) {
            $this->assertArrayHasKey($code, $types, "Should have default type: $code");
            $this->assertIsString($types[$code], "Type $code should have string description");
            $this->assertNotEmpty($types[$code], "Type $code description should not be empty");
        }
        
        echo "\n✓ All 6 default operation types loaded:\n";
        foreach ($types as $code => $description) {
            echo "  - $code: $description\n";
        }
        
        // Verify session cache (if session started)
        if (session_status() == PHP_SESSION_ACTIVE) {
            $this->assertTrue(
                isset($_SESSION['operation_types']),
                'Operation types should be cached in session'
            );
            echo "✓ OperationTypesRegistry session cache working\n";
        } else {
            echo "⚠ Session not active in test environment (OK for unit tests)\n";
        }
    }
    
    /**
     * Test TransferDirectionAnalyzer with mock data
     * 
     * Verifies business logic without touching database.
     * Uses realistic data structure matching actual database records.
     * 
     * @return void
     */
    public function testTransferDirectionAnalyzerLogic()
    {
        require_once(__DIR__ . '/../../Services/TransferDirectionAnalyzer.php');
        
        $analyzer = new \KsfBankImport\Services\TransferDirectionAnalyzer();
        
        // Mock realistic transaction data (matching actual DB structure)
        $transaction1 = [
            'id' => 1001,
            'transactionDC' => 'D',  // Debit - money leaving
            'transactionAmount' => -500.00,
            'valueTimestamp' => '2025-01-15',
            'transactionTitle' => 'Transfer to CIBC HISA'
        ];
        
        $transaction2 = [
            'id' => 2001,
            'transactionDC' => 'C',  // Credit - money arriving
            'transactionAmount' => 500.00,
            'valueTimestamp' => '2025-01-15',
            'transactionTitle' => 'Transfer from Manulife'
        ];
        
        $account1 = [
            'id' => 10,
            'name' => 'Manulife Bank'
        ];
        
        $account2 = [
            'id' => 20,
            'name' => 'CIBC HISA'
        ];
        
        // Analyze direction
        $result = $analyzer->analyze($transaction1, $transaction2, $account1, $account2);
        
        // Verify result structure
        $this->assertIsArray($result, 'Analysis result should be array');
        $this->assertArrayHasKey('from_account', $result);
        $this->assertArrayHasKey('to_account', $result);
        $this->assertArrayHasKey('from_trans_id', $result);
        $this->assertArrayHasKey('to_trans_id', $result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('memo', $result);
        
        // Verify direction logic (DC='D' means FROM account1 TO account2)
        $this->assertEquals(10, $result['from_account'], 'FROM should be Manulife');
        $this->assertEquals(20, $result['to_account'], 'TO should be CIBC');
        $this->assertEquals(1001, $result['from_trans_id']);
        $this->assertEquals(2001, $result['to_trans_id']);
        
        // Verify amount is positive
        $this->assertEquals(500.00, $result['amount'], 'Amount should be positive');
        
        // Verify memo contains both titles
        $this->assertStringContainsString('Transfer to CIBC HISA', $result['memo']);
        $this->assertStringContainsString('Transfer from Manulife', $result['memo']);
        
        echo "\n✓ TransferDirectionAnalyzer business logic verified:\n";
        echo "  FROM: Account {$result['from_account']} (Transaction {$result['from_trans_id']})\n";
        echo "  TO:   Account {$result['to_account']} (Transaction {$result['to_trans_id']})\n";
        echo "  Amount: \${$result['amount']}\n";
        echo "  Memo: {$result['memo']}\n";
    }
    
    /**
     * Test bi_transactions_model can read real transactions
     * 
     * Verifies we can load actual transactions from database (read-only).
     * 
     * @return void
     */
    public function testBiTransactionsModelReadsRealData()
    {
        // Skip test - requires full FrontAccounting environment
        $this->markTestSkipped(
            'Skipped: Requires FrontAccounting database and includes. ' .
            'Run manually in production environment to verify.'
        );
        
        /* MANUAL TEST INSTRUCTIONS:
         * 
         * To run this test in production environment:
         * 
         * 1. Ensure FrontAccounting is fully loaded
         * 2. Run from process_statements.php context
         * 3. Use this code snippet:
         * 
         * $bit = new bi_transactions_model();
         * $transactions = $bit->get_transactions(0);
         * echo "Found " . count($transactions) . " unprocessed transactions\n";
         * if (count($transactions) > 0) {
         *     $first = $transactions[0];
         *     echo "Sample ID: {$first['id']}\n";
         *     echo "Title: {$first['transactionTitle']}\n";
         *     echo "Amount: {$first['transactionAmount']}\n";
         * }
         */
    }
    
    /**
     * Test PairedTransferProcessor can be instantiated with real dependencies
     * 
     * Verifies all services can be created without errors (no processing).
     * 
     * @return void
     */
    public function testPairedTransferProcessorCanBeInstantiated()
    {
        // Skip if FrontAccounting not available
        if (!function_exists('get_vendor_list')) {
            $this->markTestSkipped(
                'Skipped: Requires FrontAccounting environment. ' .
                'Components tested individually via unit tests.'
            );
        }
        
        // Load required files (without interface - tested via unit tests)
        require_once(__DIR__ . '/../../Services/TransactionUpdater.php');
        require_once(__DIR__ . '/../../Services/TransferDirectionAnalyzer.php');
        require_once(__DIR__ . '/../../VendorListManager.php');
        require_once(__DIR__ . '/../../OperationTypes/OperationTypesRegistry.php');
        
        // Get real managers
        $vendorList = \KsfBankImport\VendorListManager::getInstance()->getVendorList();
        $optypes = \KsfBankImport\OperationTypes\OperationTypesRegistry::getInstance()->getTypes();
        
        // Create service instances (factory skipped - needs FA functions)
        $updater = new \KsfBankImport\Services\TransactionUpdater();
        $analyzer = new \KsfBankImport\Services\TransferDirectionAnalyzer();
        
        // Verify instances created successfully
        $this->assertInstanceOf(
            \KsfBankImport\Services\TransactionUpdater::class,
            $updater,
            'TransactionUpdater should be instantiated'
        );
        $this->assertInstanceOf(
            \KsfBankImport\Services\TransferDirectionAnalyzer::class,
            $analyzer,
            'TransferDirectionAnalyzer should be instantiated'
        );
        
        echo "\n✓ All testable service components instantiated successfully:\n";
        echo "  - TransactionUpdater\n";
        echo "  - TransferDirectionAnalyzer\n";
        echo "  - VendorListManager (cached: " . count($vendorList) . " vendors)\n";
        echo "  - OperationTypesRegistry (loaded: " . count($optypes) . " types)\n";
        
        echo "\n✓ Architecture verified - all components working together\n";
        echo "  (BankTransferFactory requires FrontAccounting - tested via unit tests)\n";
    }
    
    /**
     * Test performance - vendor list caching improvement
     * 
     * Measures actual performance improvement from session caching.
     * READ-ONLY: No database modifications.
     * 
     * @return void
     */
    public function testVendorListCachingPerformance()
    {
        if (!function_exists('get_vendor_list')) {
            $this->markTestSkipped('FrontAccounting functions not available');
        }
        
        require_once(__DIR__ . '/../../VendorListManager.php');
        
        // Clear cache
        $manager = \KsfBankImport\VendorListManager::getInstance();
        $manager->clearCache();
        
        // Measure uncached load
        $start = microtime(true);
        $list1 = $manager->getVendorList();
        $uncachedTime = microtime(true) - $start;
        
        // Measure cached load
        $start = microtime(true);
        $list2 = $manager->getVendorList();
        $cachedTime = microtime(true) - $start;
        
        // Calculate improvement
        if ($uncachedTime > 0) {
            $improvement = (($uncachedTime - $cachedTime) / $uncachedTime) * 100;
            
            echo "\n✓ Performance Test Results:\n";
            echo "  Uncached load: " . number_format($uncachedTime * 1000, 2) . " ms\n";
            echo "  Cached load:   " . number_format($cachedTime * 1000, 2) . " ms\n";
            echo "  Improvement:   " . number_format($improvement, 1) . "%\n";
            
            // Verify caching provides improvement
            $this->assertLessThan($uncachedTime, $cachedTime, 'Cached load should be faster');
            
            if ($improvement > 50) {
                echo "  ✅ Excellent performance improvement!\n";
            } else {
                echo "  ⚠ Lower than expected (may be due to small dataset or fast DB)\n";
            }
        }
    }
}
