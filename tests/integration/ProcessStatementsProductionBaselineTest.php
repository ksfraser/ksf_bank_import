<?php
/**
 * Production Baseline Test for process_statements.php
 * 
 * This test documents the KNOWN-GOOD state of process_statements.php
 * from the prod-bank-import-2025 branch (pre-Command Pattern refactoring).
 * 
 * PROD BASELINE CHARACTERISTICS:
 * - Direct inline POST handling with bi_controller methods
 * - Hardcoded $optypes array with 6 partner types
 * - NO Command Pattern (no command_bootstrap.php)
 * - NO HTML library imports (no use statements for Ksfraser\HTML)
 * - NO service classes (PairedTransferProcessor, BankTransferFactory, TransactionUpdater)
 * - NO PartnerTypeConstants class
 * - Direct switch statement for transaction type processing (SP, CU, QE, BT, MA, ZZ)
 * - Inline bi_controller method calls: unsetTrans(), addCustomer(), addVendor()
 * - TODO comments in single block format (not restructured)
 * - NO ProcessBothSides handler
 * - Uses each() function (deprecated but still present)
 * - File size: ~622 lines
 * 
 * CHANGES IN MAIN (detected as test failures):
 * - Added command_bootstrap.php include with Command Pattern infrastructure
 * - Added HTML library use statements (14 imports)
 * - Replaced hardcoded $optypes array with PartnerTypeConstants::getAll()
 * - Added conditional USE_COMMAND_PATTERN flag
 * - Added new ProcessBothSides POST handler with service classes
 * - Restructured TODO comments (INT01, INT02, INT03 tags)
 * - Added extensive comments about Command Pattern architecture
 * - Added new service layer dependencies (6+ new classes)
 * - File size: expanded significantly (330 insertions, 185 deletions)
 * 
 * TEST STRATEGY:
 * Test for ABSENCE of Command Pattern and PRESENCE of legacy inline processing.
 * 
 * @package Ksfraser\FaBankImport\Tests\Integration
 * @group ProductionBaseline
 * @group RegressionTest
 */

use PHPUnit\Framework\TestCase;

class ProcessStatementsProductionBaselineTest extends TestCase
{
    private $filePath;
    private $fileContent;

    protected function setUp(): void
    {
        $this->filePath = __DIR__ . '/../../process_statements.php';
        $this->assertTrue(file_exists($this->filePath), "File must exist: {$this->filePath}");
        $this->fileContent = file_get_contents($this->filePath);
    }

    /**
     * Test 1: File exists and is readable
     */
    public function testFileExists(): void
    {
        $this->assertFileExists($this->filePath);
        $this->assertFileIsReadable($this->filePath);
    }

    /**
     * Test 2: PROD does NOT include command_bootstrap.php
     */
    public function testNoCommandBootstrap(): void
    {
        $this->assertStringNotContainsString('command_bootstrap.php', $this->fileContent,
            'PROD does not use Command Pattern bootstrap');
        $this->assertStringNotContainsString('CommandDispatcher', $this->fileContent,
            'PROD does not use CommandDispatcher');
    }

    /**
     * Test 3: PROD does NOT have HTML library use statements
     */
    public function testNoHtmlLibraryImports(): void
    {
        $this->assertStringNotContainsString('use Ksfraser\HTML\Elements\HtmlForm', $this->fileContent,
            'PROD does not import HtmlForm');
        $this->assertStringNotContainsString('use Ksfraser\HTML\Elements\HtmlDiv', $this->fileContent,
            'PROD does not import HtmlDiv');
        $this->assertStringNotContainsString('use Ksfraser\HTML\Elements\HtmlTable', $this->fileContent,
            'PROD does not import HtmlTable');
        $this->assertStringNotContainsString('use Ksfraser\HTML\Elements\HtmlString', $this->fileContent,
            'PROD does not import HtmlString');
        
        // Count total use statements - should be 0 in PROD
        $useCount = substr_count($this->fileContent, 'use Ksfraser\HTML');
        $this->assertEquals(0, $useCount, 'PROD has no HTML library use statements');
    }

    /**
     * Test 4: PROD has hardcoded $optypes array
     */
    public function testHasHardcodedOptypesArray(): void
    {
        $this->assertMatchesRegularExpression('/\$optypes\s*=\s*array\s*\(/', $this->fileContent,
            'PROD uses hardcoded $optypes array');
        $this->assertStringContainsString("'SP' => 'Supplier'", $this->fileContent,
            'PROD has SP => Supplier in array');
        $this->assertStringContainsString("'CU' => 'Customer'", $this->fileContent,
            'PROD has CU => Customer in array');
        $this->assertStringContainsString("'QE' => 'Quick Entry'", $this->fileContent,
            'PROD has QE => Quick Entry in array');
        $this->assertStringContainsString("'BT' => 'Bank Transfer'", $this->fileContent,
            'PROD has BT => Bank Transfer in array');
        $this->assertStringContainsString("'MA' => 'Manual settlement'", $this->fileContent,
            'PROD has MA => Manual settlement in array');
        $this->assertStringContainsString("'ZZ' => 'Matched'", $this->fileContent,
            'PROD has ZZ => Matched in array');
    }

    /**
     * Test 5: PROD does NOT use PartnerTypeConstants class
     */
    public function testNoPartnerTypeConstants(): void
    {
        $this->assertStringNotContainsString('PartnerTypeConstants::getAll()', $this->fileContent,
            'PROD does not use PartnerTypeConstants class');
        $this->assertStringNotContainsString('PartnerTypeConstants', $this->fileContent,
            'PROD has no reference to PartnerTypeConstants');
    }

    /**
     * Test 6: PROD has direct UnsetTrans POST handler
     */
    public function testHasDirectUnsetTransHandler(): void
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'UnsetTrans\'\]\s*\)\s*\)/', 
            $this->fileContent,
            'PROD has direct UnsetTrans handler');
        $this->assertStringContainsString('$bi_controller->unsetTrans()', $this->fileContent,
            'PROD calls bi_controller->unsetTrans() directly');
    }

    /**
     * Test 7: PROD has direct AddCustomer POST handler
     */
    public function testHasDirectAddCustomerHandler(): void
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'AddCustomer\'\]\s*\)\s*\)/',
            $this->fileContent,
            'PROD has direct AddCustomer handler');
        $this->assertStringContainsString('$bi_controller->addCustomer()', $this->fileContent,
            'PROD calls bi_controller->addCustomer() directly');
    }

    /**
     * Test 8: PROD has direct AddVendor POST handler
     */
    public function testHasDirectAddVendorHandler(): void
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'AddVendor\'\]\s*\)\s*\)/',
            $this->fileContent,
            'PROD has direct AddVendor handler');
        $this->assertStringContainsString('$bi_controller->addVendor()', $this->fileContent,
            'PROD calls bi_controller->addVendor() directly');
    }

    /**
     * Test 9: PROD does NOT have USE_COMMAND_PATTERN constant checks
     */
    public function testNoCommandPatternFlag(): void
    {
        $this->assertStringNotContainsString('USE_COMMAND_PATTERN', $this->fileContent,
            'PROD does not have USE_COMMAND_PATTERN flag');
        $this->assertStringNotContainsString('defined(\'USE_COMMAND_PATTERN\')', $this->fileContent,
            'PROD does not check for USE_COMMAND_PATTERN constant');
    }

    /**
     * Test 10: PROD does NOT have ProcessBothSides handler
     */
    public function testNoProcessBothSidesHandler(): void
    {
        $this->assertStringNotContainsString('ProcessBothSides', $this->fileContent,
            'PROD does not have ProcessBothSides POST handler');
        $this->assertStringNotContainsString('PairedTransferProcessor', $this->fileContent,
            'PROD does not use PairedTransferProcessor service');
    }

    /**
     * Test 11: PROD does NOT use service classes
     */
    public function testNoServiceClasses(): void
    {
        $this->assertStringNotContainsString('BankTransferFactory', $this->fileContent,
            'PROD does not use BankTransferFactory');
        $this->assertStringNotContainsString('TransactionUpdater', $this->fileContent,
            'PROD does not use TransactionUpdater');
        $this->assertStringNotContainsString('TransferDirectionAnalyzer', $this->fileContent,
            'PROD does not use TransferDirectionAnalyzer');
        $this->assertStringNotContainsString('Services/', $this->fileContent,
            'PROD does not require_once any Services/ files');
    }

    /**
     * Test 12: PROD has simple TODO comment block (not restructured)
     */
    public function testSimpleTodoCommentBlock(): void
    {
        // PROD has simple TODO block without INT01, INT02, INT03 tags
        $this->assertStringNotContainsString('(INT01)', $this->fileContent,
            'PROD does not have INT01 TODO tag');
        $this->assertStringNotContainsString('(INT02)', $this->fileContent,
            'PROD does not have INT02 TODO tag');
        $this->assertStringNotContainsString('(INT03)', $this->fileContent,
            'PROD does not have INT03 TODO tag');
        
        // PROD has original TODO structure
        $this->assertStringContainsString('//TODO:', $this->fileContent,
            'PROD has TODO comments');
        $this->assertStringContainsString('Audit routine to ensure', $this->fileContent,
            'PROD has audit TODO');
    }

    /**
     * Test 13: PROD has ProcessTransaction with switch statement
     */
    public function testHasProcessTransactionWithSwitch(): void
    {
        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'ProcessTransaction\'\]\s*\)\s*\)/',
            $this->fileContent,
            'PROD has ProcessTransaction handler');
        $this->assertMatchesRegularExpression('/switch\s*\(\s*true\s*\)/',
            $this->fileContent,
            'PROD uses switch(true) for transaction processing');
        $this->assertMatchesRegularExpression('/case\s*\(\s*\$_POST\[\'partnerType\'\].*==\s*\'SP\'\s*\):/',
            $this->fileContent,
            'PROD has case for SP (Supplier) in switch');
        $this->assertMatchesRegularExpression('/case\s*\(\s*\$_POST\[\'partnerType\'\].*==\s*\'CU\'/',
            $this->fileContent,
            'PROD has case for CU (Customer) in switch');
    }

    /**
     * Test 14: PROD calls processSupplierTransaction method
     */
    public function testCallsProcessSupplierTransaction(): void
    {
        $this->assertStringContainsString('$bi_controller->processSupplierTransaction()', $this->fileContent,
            'PROD calls bi_controller->processSupplierTransaction()');
    }

    /**
     * Test 15: PROD has inline customer payment code with ST_CUSTPAYMENT
     */
    public function testHasInlineCustomerPaymentCode(): void
    {
        $this->assertStringContainsString('ST_BANKDEPOSIT', $this->fileContent,
            'PROD references ST_BANKDEPOSIT');
        $this->assertStringContainsString('ST_CUSTPAYMENT', $this->fileContent,
            'PROD references ST_CUSTPAYMENT');
        $this->assertStringContainsString('$trans_type = ST_CUSTPAYMENT', $this->fileContent,
            'PROD sets trans_type to ST_CUSTPAYMENT');
    }

    /**
     * Test 16: PROD uses each() function (deprecated)
     */
    public function testUsesEachFunction(): void
    {
        $this->assertMatchesRegularExpression('/list\s*\(\s*\$k\s*,\s*\$v\s*\)\s*=\s*each\s*\(/',
            $this->fileContent,
            'PROD uses each() function (deprecated but present)');
    }

    /**
     * Test 17: PROD does NOT have extensive Command Pattern comments
     */
    public function testNoCommandPatternComments(): void
    {
        $this->assertStringNotContainsString('The command_bootstrap.php file', $this->fileContent,
            'PROD does not have Command Pattern architecture comments');
        $this->assertStringNotContainsString('Initializes the DI container', $this->fileContent,
            'PROD does not reference DI container');
        $this->assertStringNotContainsString('Legacy fallback handlers', $this->fileContent,
            'PROD does not have legacy fallback comments');
    }

    /**
     * Test 18: PROD has bi_controller instantiation
     */
    public function testHasBiControllerInstantiation(): void
    {
        $this->assertStringContainsString('$bi_controller = new bank_import_controller()', $this->fileContent,
            'PROD instantiates bank_import_controller');
        $this->assertStringContainsString('require_once( \'class.bank_import_controller.php\' )', $this->fileContent,
            'PROD requires bank_import_controller.php');
    }

    /**
     * Test 19: PROD does NOT use VendorListManager singleton
     */
    public function testNoVendorListManager(): void
    {
        $this->assertStringNotContainsString('VendorListManager::getInstance()', $this->fileContent,
            'PROD does not use VendorListManager singleton');
        $this->assertStringNotContainsString('VendorListManager', $this->fileContent,
            'PROD has no reference to VendorListManager');
    }

    /**
     * Test 20: PROD does NOT use OperationTypesRegistry
     */
    public function testNoOperationTypesRegistry(): void
    {
        $this->assertStringNotContainsString('OperationTypesRegistry', $this->fileContent,
            'PROD does not use OperationTypesRegistry');
        $this->assertStringNotContainsString('getTypes()', $this->fileContent,
            'PROD does not call getTypes() method');
    }

    /**
     * Test 21: PROD file size is within expected range (pre-expansion)
     */
    public function testFileSizeRange(): void
    {
        $lineCount = count(file($this->filePath));
        $this->assertGreaterThan(600, $lineCount,
            'PROD file should be over 600 lines');
        $this->assertLessThan(700, $lineCount,
            'PROD file should be under 700 lines (before expansion)');
    }

    /**
     * Test 22: PROD has inline TODO about recurring payment duplication
     */
    public function testHasRecurringPaymentTodo(): void
    {
        $this->assertStringContainsString('recurring payments aren\'t matched to the same payment', $this->fileContent,
            'PROD has TODO about recurring payment duplication');
        $this->assertStringContainsString('Audit that no 2 transactions point to the same type+number', $this->fileContent,
            'PROD has TODO about transaction duplication audit');
    }

    /**
     * Test 23: PROD has comment about unset($k, $v)
     */
    public function testHasUnsetKVComment(): void
    {
        $this->assertMatchesRegularExpression('/unset\s*\(\s*\$k\s*,\s*\$v\s*\)/',
            $this->fileContent,
            'PROD has unset($k, $v) statement');
        $this->assertStringContainsString('// actions', $this->fileContent,
            'PROD has "// actions" comment');
    }

    /**
     * Test 24: PROD does NOT reference DI container or dependency injection
     */
    public function testNoDependencyInjection(): void
    {
        $this->assertStringNotContainsString('$container', $this->fileContent,
            'PROD does not reference DI container variable');
        $this->assertStringNotContainsString('dependency injection', $this->fileContent,
            'PROD does not mention dependency injection');
        $this->assertStringNotContainsString('Initializes the DI container', $this->fileContent,
            'PROD does not have DI container initialization comments');
    }

    /**
     * Test 25: PROD has ModuleMenuView rendering
     */
    public function testHasModuleMenuView(): void
    {
        $this->assertStringContainsString('include_once "Views/module_menu_view.php"', $this->fileContent,
            'PROD includes module_menu_view.php');
        $this->assertStringContainsString('$menu = new \Views\ModuleMenuView()', $this->fileContent,
            'PROD instantiates ModuleMenuView');
        $this->assertStringContainsString('$menu->renderMenu()', $this->fileContent,
            'PROD calls renderMenu()');
    }
}
