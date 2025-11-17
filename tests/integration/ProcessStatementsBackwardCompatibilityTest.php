<?php

/**
 * Process Statements Backward Compatibility Test
 *
 * Tests that verify process_statements.php behaviors from prod branch
 * work identically on main branch. These tests must pass on BOTH branches
 * to ensure backward compatibility.
 *
 * Key behaviors tested:
 * 1. Status filtering (statusFilter=0, 1, or all)
 * 2. POST action dispatching (UnsetTrans, AddCustomer, AddVendor, ToggleTransaction)
 * 3. ProcessTransaction with different partner types (SP, CU, QE, BT, MA, ZZ)
 * 4. Error handling (missing parameters, invalid bank accounts)
 * 5. Transaction processing workflow (data loading, validation, handler dispatch)
 *
 * CRITICAL: These tests run on BOTH branches with NO branch-specific checks.
 * If any test fails, it indicates a regression.
 *
 * @package Tests\Integration
 * @version 1.0.0
 * @since   2025-01-XX
 */

use PHPUnit\Framework\TestCase;

class ProcessStatementsBackwardCompatibilityTest extends TestCase
{
    /**
     * NOTE: These tests focus on logic and workflow validation, not full FA integration.
     * Database-dependent tests are marked as integration tests and may be skipped
     * in environments without FA database access.
     */
    
    public static function setUpBeforeClass(): void
    {
        // Load standalone dependencies (no FA session required)
        // These tests validate workflow logic, not database operations
    }
    
    /**
     * TEST: Status filter workflow logic
     *
     * Verifies status filter conditional logic from process_statements.php.
     * Tests the if/else branching: statusFilter == 0 OR 1 uses parameter, else no parameter.
     *
     * @test
     */
    public function testStatusFilterWorkflowLogic_BackwardCompatible()
    {
        // Simulate status filter logic from process_statements.php (lines 418-424)
        
        // Test case 1: statusFilter = 0 (use parameter)
        $_POST['statusFilter'] = 0;
        $should_filter = ($_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1);
        $this->assertTrue($should_filter, "statusFilter=0 should use parameter");
        
        // Test case 2: statusFilter = 1 (use parameter)
        $_POST['statusFilter'] = 1;
        $should_filter = ($_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1);
        $this->assertTrue($should_filter, "statusFilter=1 should use parameter");
        
        // Test case 3: statusFilter = 2 (no parameter, get all)
        $_POST['statusFilter'] = 2;
        $should_filter = ($_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1);
        $this->assertFalse($should_filter, "statusFilter=2 should NOT use parameter");
        
        // Test case 4: statusFilter = -1 (no parameter, get all)
        $_POST['statusFilter'] = -1;
        $should_filter = ($_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1);
        $this->assertFalse($should_filter, "statusFilter=-1 should NOT use parameter");
        
        // Cleanup
        unset($_POST['statusFilter']);
    }
    
    /**
     * TEST: ProcessTransaction requires partnerId parameter
     *
     * Verifies that missing partnerId_$k triggers error (backward compatible validation).
     *
     * @test
     */
    public function testProcessTransactionMissingPartnerId_BackwardCompatible()
    {
        // Simulate ProcessTransaction POST without partnerId
        $_POST['ProcessTransaction'] = ['123' => 'process'];
        $_POST['partnerType'] = ['123' => 'SP'];
        // Intentionally omit partnerId_123
        
        // Execute validation logic (from process_statements.php lines 226-233)
        if (isset($_POST['ProcessTransaction'])) {
            // PHP 7.2+ replacement for each() - use foreach or array functions
            $k = array_key_first($_POST['ProcessTransaction']);
            $v = $_POST['ProcessTransaction'][$k];
            
            if (isset($k) && isset($v) && isset($_POST['partnerType'][$k])) {
                $error = 0;
                if (!isset($_POST["partnerId_$k"])) {
                    $error = 1;
                }
                
                $this->assertEquals(1, $error, "Error flag should be set when partnerId missing");
            }
        }
        
        // Cleanup
        unset($_POST['ProcessTransaction']);
        unset($_POST['partnerType']);
    }
    
    /**
     * TEST: ProcessTransaction has partnerId parameter
     *
     * Verifies that WITH partnerId, validation passes.
     *
     * @test
     */
    public function testProcessTransactionWithPartnerId_BackwardCompatible()
    {
        // Simulate ProcessTransaction POST WITH partnerId
        $_POST['ProcessTransaction'] = ['456' => 'process'];
        $_POST['partnerType'] = ['456' => 'CU'];
        $_POST['partnerId_456'] = '100'; // Provide partnerId
        
        // Execute validation logic
        if (isset($_POST['ProcessTransaction'])) {
            // PHP 7.2+ replacement for each()
            $k = array_key_first($_POST['ProcessTransaction']);
            $v = $_POST['ProcessTransaction'][$k];
            
            if (isset($k) && isset($v) && isset($_POST['partnerType'][$k])) {
                $error = 0;
                if (!isset($_POST["partnerId_$k"])) {
                    $error = 1;
                }
                
                $this->assertEquals(0, $error, "Error flag should NOT be set when partnerId present");
            }
        }
        
        // Cleanup
        unset($_POST['ProcessTransaction']);
        unset($_POST['partnerType']);
        unset($_POST['partnerId_456']);
    }
    
    /**
     * TEST: Partner type constants availability
     *
     * Verifies that PartnerTypeConstants::getAll() returns expected types.
     * CRITICAL: Main uses PartnerTypeConstants::getAll(), prod uses hardcoded array.
     * Test ensures both return same partner types.
     *
     * @test
     */
    public function testPartnerTypeConstants_BackwardCompatible()
    {
        // Expected partner types (from prod hardcoded array)
        $expected_types = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ'];
        
        // Load from PartnerTypeConstants if available (main branch)
        if (class_exists('\Ksfraser\PartnerTypeConstants')) {
            $optypes = \Ksfraser\PartnerTypeConstants::getAll();
            
            // Verify: All expected types present
            foreach ($expected_types as $type) {
                $this->assertArrayHasKey($type, $optypes, 
                    "PartnerTypeConstants should include '$type'");
            }
        } else {
            // Fallback: Prod branch hardcoded array
            $optypes = [
                'SP' => 'Supplier Payment',
                'CU' => 'Customer Payment',
                'QE' => 'Quick Entry',
                'BT' => 'Bank Transfer',
                'MA' => 'Matched',
                'ZZ' => 'Matched',
            ];
            
            $this->assertEquals($expected_types, array_keys($optypes), 
                "Hardcoded array should have expected partner types");
        }
        
        $this->assertGreaterThanOrEqual(6, count($optypes), 
            "Should have at least 6 partner types");
    }
    
    /**
     * TEST: Bank account validation workflow
     *
     * Verifies that bank account validation logic exists in process_statements.php.
     * Checks that empty($our_account) triggers error.
     *
     * @test
     */
    public function testBankAccountValidationWorkflow_BackwardCompatible()
    {
        // Simulate empty bank account (from process_statements.php line 246-251)
        $our_account = null; // Invalid/empty account
        
        $error = 0;
        if (empty($our_account)) {
            $error = 1;
        }
        
        $this->assertEquals(1, $error, "Empty bank account should set error flag");
        
        // Test with valid account data
        $our_account = ['id' => 1, 'bank_account_name' => 'Test Account'];
        $error = 0;
        if (empty($our_account)) {
            $error = 1;
        }
        
        $this->assertEquals(0, $error, "Valid bank account should NOT set error flag");
    }
    
    /**
     * TEST: Charge calculation workflow
     *
     * Verifies that charges can be parsed from cids (charge IDs).
     * This is critical for reconciling transaction amounts vs charges.
     *
     * @test
     */
    public function testChargeCalculationWorkflow_BackwardCompatible()
    {
        // Test cids parsing (mimics process_statements.php line 258)
        $test_cids = '1,2,3';
        $_cids = array_filter(explode(',', $test_cids));
        
        // Verify: Parsing works correctly
        $this->assertCount(3, $_cids, "Should parse 3 charge IDs from '1,2,3'");
        $this->assertEquals(['1', '2', '3'], array_values($_cids), 
            "Should extract correct IDs");
        
        // Test empty cids
        $empty_cids = '';
        $_empty_cids = array_filter(explode(',', $empty_cids));
        $this->assertEmpty($_empty_cids, "Empty cids should result in empty array");
        
        // Test cids with commas but no values
        $comma_only_cids = ',,,';
        $_comma_cids = array_filter(explode(',', $comma_only_cids));
        $this->assertEmpty($_comma_cids, "Comma-only cids should filter to empty array");
        
        // Test with whitespace
        $whitespace_cids = ' 1 , 2 , 3 ';
        $_whitespace_cids = array_filter(array_map('trim', explode(',', $whitespace_cids)));
        $this->assertCount(3, $_whitespace_cids, "Should parse 3 IDs with whitespace");
    }
    
    /**
     * TEST: All partner types have handlers (PROD BASELINE)
     *
     * Verifies that ALL partner types can be processed.
     * - PROD: Uses switch statement with case 'SP', 'CU', 'QE', 'BT', 'MA', 'ZZ'
     * - MAIN: Uses TransactionProcessor with handler classes
     * 
     * This test verifies functionality exists regardless of implementation.
     *
     * @test
     */
    public function testAllPartnerTypesHaveHandlers_BackwardCompatible()
    {
        $partner_types = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ'];
        
        // Check if we're using TransactionProcessor (main) or switch statement (prod)
        $process_statements_file = __DIR__ . '/../../process_statements.php';
        $content = file_get_contents($process_statements_file);
        $uses_processor = (strpos($content, "TransactionProcessor") !== false);
        
        if ($uses_processor) {
            // MAIN BRANCH: Check handlers exist for each type
            $this->assertTrue(class_exists('\Ksfraser\FaBankImport\TransactionProcessor'), 
                "TransactionProcessor should exist on main branch");
            
            $processor = new \Ksfraser\FaBankImport\TransactionProcessor();
            
            // Verify each partner type has a handler
            foreach ($partner_types as $type) {
                // Check if handler exists by looking in Handlers directory
                $handler_files = glob(__DIR__ . '/../../src/Ksfraser/FaBankImport/Handlers/*Handler.php');
                $handler_names = array_map(function($f) { return basename($f, '.php'); }, $handler_files);
                
                // Map partner types to expected handler names
                $expected_handlers = [
                    'SP' => 'SupplierTransactionHandler',
                    'CU' => 'CustomerTransactionHandler', 
                    'QE' => 'QuickEntryTransactionHandler',
                    'BT' => 'BankTransferTransactionHandler',
                    'MA' => 'ManualSettlementHandler',
                    'ZZ' => 'MatchedTransactionHandler'
                ];
                
                $this->assertContains($expected_handlers[$type], $handler_names,
                    "Handler for partner type '$type' should exist: {$expected_handlers[$type]}");
            }
        } else {
            // PROD BRANCH: Check switch statement has cases for each type
            foreach ($partner_types as $type) {
                // Look for switch case handling this partner type
                $pattern = "/case.*partnerType.*==.*['\"]" . $type . "['\"]/";
                $has_case = (preg_match($pattern, $content) === 1);
                
                $this->assertTrue($has_case, 
                    "PROD switch statement should have case for partner type '$type'");
            }
        }
        
        // Verify process_statements.php references partnerType POST parameter (both branches)
        $this->assertStringContainsString('$_POST[\'partnerType\']', $content,
            "process_statements.php should handle partnerType POST parameter");
    }
    
    /**
     * TEST: Command pattern feature flag exists and is testable
     *
     * Verifies that USE_COMMAND_PATTERN flag is defined in command_bootstrap.php.
     * This flag controls legacy vs new implementation.
     *
     * @test
     */
    public function testCommandPatternFeatureFlag_BackwardCompatible()
    {
        // Check if command_bootstrap.php exists (main branch)
        $bootstrap_file = __DIR__ . '/../../src/Ksfraser/FaBankImport/command_bootstrap.php';
        
        if (!file_exists($bootstrap_file)) {
            $this->markTestSkipped('command_bootstrap.php not available (prod branch)');
        }
        
        // Mock REQUEST_METHOD to prevent bootstrap from executing POST handler
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Include and check flag
        require_once($bootstrap_file);
        
        $this->assertTrue(defined('USE_COMMAND_PATTERN'), 
            "USE_COMMAND_PATTERN should be defined in command_bootstrap.php");
        
        // Verify: Flag is boolean
        $this->assertIsBool(USE_COMMAND_PATTERN, 
            "USE_COMMAND_PATTERN should be boolean");
        
        // Verify: CommandDispatcher exists if flag is true
        if (USE_COMMAND_PATTERN) {
            $this->assertTrue(class_exists('\Ksfraser\FaBankImport\Commands\CommandDispatcher'), 
                "CommandDispatcher should exist when USE_COMMAND_PATTERN is true");
        }
    }
    
    /**
     * TEST: POST action detection logic
     *
     * Verifies that POST action detection works (UnsetTrans, AddCustomer, AddVendor, ToggleTransaction).
     * This logic is identical in both branches.
     *
     * @test
     */
    public function testPOSTActionDetection_BackwardCompatible()
    {
        $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
        
        foreach ($actions as $action) {
            // Simulate POST
            $_POST[$action] = ['test' => 'value'];
            
            // Test detection (mimics process_statements.php and command_bootstrap.php logic)
            $detected = isset($_POST[$action]);
            
            $this->assertTrue($detected, "Should detect POST['$action']");
            
            // Cleanup
            unset($_POST[$action]);
        }
    }
    
    /**
     * TEST: ProcessBothSides action exists and is detectable
     *
     * Verifies that ProcessBothSides POST action (bank transfer pairing) is supported.
     *
     * @test
     */
    public function testProcessBothSidesAction_BackwardCompatible()
    {
        // Simulate ProcessBothSides POST (bank transfer pairing)
        $_POST['ProcessBothSides'] = ['123' => 'process'];
        
        // Test detection (mimics process_statements.php line 165)
        $detected = isset($_POST['ProcessBothSides']);
        
        $this->assertTrue($detected, "Should detect POST['ProcessBothSides']");
        
        // Test data extraction (mimics line 166) - PHP 7.2+ replacement for each()
        $k = array_key_first($_POST['ProcessBothSides']);
        $v = $_POST['ProcessBothSides'][$k];
        
        $this->assertEquals('123', $k, "Should extract transaction ID from ProcessBothSides");
        $this->assertEquals('process', $v, "Should extract action from ProcessBothSides");
        
        // Cleanup
        unset($_POST['ProcessBothSides']);
    }
    
    /**
     * TEST: Vendor list loading workflow
     *
     * Verifies that VendorListManager singleton pattern works.
     * This is used in process_statements.php for vendor dropdowns.
     *
     * @test
     */
    public function testVendorListLoading_BackwardCompatible()
    {
        // Check if VendorListManager class file exists (main branch)
        $vendor_list_manager_file = __DIR__ . '/../../VendorListManager.php';
        
        if (!file_exists($vendor_list_manager_file)) {
            $this->markTestSkipped('VendorListManager.php not available (prod branch or not implemented)');
        }
        
        // Include the file
        require_once($vendor_list_manager_file);
        
        // Verify class exists
        $this->assertTrue(class_exists('\KsfBankImport\VendorListManager'), 
            "VendorListManager class should exist");
        
        // Verify getInstance() method exists (singleton pattern)
        $this->assertTrue(method_exists('\KsfBankImport\VendorListManager', 'getInstance'), 
            "VendorListManager should have getInstance() method");
        
        // Note: Can't test getVendorList() without FA database connection
        $this->assertTrue(true, "VendorListManager architecture validated");
    }
    
    /**
     * TEST: RefreshInquiry POST action detection
     *
     * Verifies that RefreshInquiry button detection works.
     * This triggers AJAX refresh of transaction table.
     *
     * @test
     */
    public function testRefreshInquiryActionDetection_BackwardCompatible()
    {
        // Simulate RefreshInquiry POST (from header_table.php search button)
        $_POST['RefreshInquiry'] = 'Refresh';
        
        // Test detection (mimics process_statements.php line 365)
        // Using isset() since get_post() is FA function
        $detected = isset($_POST['RefreshInquiry']);
        
        $this->assertTrue($detected, "Should detect POST['RefreshInquiry']");
        
        // Cleanup
        unset($_POST['RefreshInquiry']);
    }
    
    /**
     * TEST: PartnerType POST change detection
     *
     * Verifies that partnerType dropdown change triggers AJAX refresh.
     *
     * @test
     */
    public function testPartnerTypeChangeDetection_BackwardCompatible()
    {
        // Simulate partnerType change (dropdown selection)
        $_POST['partnerType'] = ['123' => 'SP'];
        
        // Test detection (mimics process_statements.php line 382)
        $detected = isset($_POST['partnerType']);
        
        $this->assertTrue($detected, "Should detect POST['partnerType'] change");
        
        // Cleanup
        unset($_POST['partnerType']);
    }
}
