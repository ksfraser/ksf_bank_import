<?php
/**
 * Production Baseline Test for class.transactions_table.php
 * 
 * This test documents the KNOWN-GOOD state of class.transactions_table.php
 * from the prod-bank-import-2025 branch (pre-HTML abstraction refactoring).
 * 
 * PROD BASELINE CHARACTERISTICS:
 * - Uses FrontAccounting's hardcoded HTML functions directly:
 *   - start_table(), end_table(), start_row(), end_row()
 *   - label_row(), hidden(), submit()
 *   - array_selector(), supplier_list(), customer_list(), bank_accounts_list(), quick_entries_list()
 * - Three classes: transaction_table, transaction_table_row, ttr_table, ttr_label_row
 * - transaction_table::display() uses start_table(TABLESTYLE, "width='100%'")
 * - ttr_table::display() returns start_table() directly
 * - Inline HTML echo statements: echo '<td width="50%">', echo "</td>"
 * - NO use of Ksfraser\HTML\Elements namespace
 * - NO use of Ksfraser\HTML\Composites namespace
 * - NO data provider classes (SupplierDataProvider, CustomerDataProvider, etc.)
 * - File size: ~600 lines with inline procedural HTML generation
 * 
 * CHANGES IN MAIN (detected as test failures):
 * - Replaced start_table() with new \Ksfraser\HTML\Elements\HtmlTable()
 * - Replaced label_row() with new \Ksfraser\HTML\Composites\HtmlLabelRow()
 * - Replaced hidden() with new \Ksfraser\HTML\Elements\HtmlHidden()
 * - Replaced submit() with new \Ksfraser\HTML\Elements\HtmlSubmit()
 * - Replaced inline <td> with new \Ksfraser\HTML\Elements\HtmlTableCell()
 * - Replaced inline <tr> with new \Ksfraser\HTML\Elements\HtmlTableRow()
 * - Replaced array_selector() with HtmlSelect()->addOptionsFromArray()
 * - Replaced supplier_list() with SupplierDataProvider()->generateSelectHtml()
 * - Replaced customer_list() with CustomerDataProvider()->generateCustomerSelectHtml()
 * - Replaced bank_accounts_list() with BankAccountDataProvider()->generateSelectHtml()
 * - Replaced quick_entries_list() with QuickEntryDataProvider()->generateSelectHtml()
 * - File size: ~900 lines (68 insertions, 283 deletions = net -215 lines of simplified code)
 * 
 * TEST STRATEGY:
 * Test for ABSENCE of HTML abstraction classes and PRESENCE of hardcoded functions.
 * 
 * @package Ksfraser\FaBankImport\Tests\Integration
 * @group ProductionBaseline
 * @group RegressionTest
 */

use PHPUnit\Framework\TestCase;

class TransactionsTableProductionBaselineTest extends TestCase
{
    private string $filePath;
    private string $fileContent;

    protected function setUp(): void
    {
        $this->filePath = __DIR__ . '/../../class.transactions_table.php';
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
     * Test 2: Three main classes exist
     */
    public function testHasThreeClasses(): void
    {
        $this->assertMatchesRegularExpression('/class\s+transaction_table\b/', $this->fileContent);
        $this->assertMatchesRegularExpression('/class\s+transaction_table_row\b/', $this->fileContent);
        $this->assertMatchesRegularExpression('/class\s+ttr_table\b/', $this->fileContent);
        $this->assertMatchesRegularExpression('/class\s+ttr_label_row\b/', $this->fileContent);
    }

    /**
     * Test 3: PROD uses hardcoded start_table() function
     */
    public function testUsesStartTableFunction(): void
    {
        $this->assertStringContainsString("start_table(TABLESTYLE, \"width='100%'\")", $this->fileContent,
            'PROD uses direct start_table() calls');
        $this->assertStringContainsString("start_table(TABLESTYLE2, \"width='100%'\")", $this->fileContent,
            'PROD uses TABLESTYLE2 variant');
    }

    /**
     * Test 4: PROD uses hardcoded end_table() function
     */
    public function testUsesEndTableFunction(): void
    {
        $this->assertStringContainsString('end_table();', $this->fileContent,
            'PROD uses direct end_table() calls');
    }

    /**
     * Test 5: PROD uses hardcoded label_row() function extensively
     */
    public function testUsesLabelRowFunction(): void
    {
        // Check for multiple label_row() patterns
        $this->assertMatchesRegularExpression('/label_row\s*\(\s*"Trans Date/', $this->fileContent,
            'PROD uses label_row() for Trans Date');
        $this->assertMatchesRegularExpression('/label_row\s*\(\s*"Trans Type/', $this->fileContent,
            'PROD uses label_row() for Trans Type');
        $this->assertMatchesRegularExpression('/label_row\s*\(\s*"Our Bank Account/', $this->fileContent,
            'PROD uses label_row() for bank account');
        $this->assertMatchesRegularExpression('/label_row\s*\(\s*"Other account/', $this->fileContent,
            'PROD uses label_row() for other account');
        $this->assertMatchesRegularExpression('/label_row\s*\(\s*"Amount/', $this->fileContent,
            'PROD uses label_row() for amount');
        
        // Count occurrences - should be many in PROD
        $count = substr_count($this->fileContent, 'label_row(');
        $this->assertGreaterThan(20, $count, 'PROD has 20+ label_row() calls');
    }

    /**
     * Test 6: PROD uses hardcoded hidden() function
     */
    public function testUsesHiddenFunction(): void
    {
        $this->assertMatchesRegularExpression('/hidden\s*\(\s*"vendor_short_/', $this->fileContent,
            'PROD uses hidden() for vendor_short');
        $this->assertMatchesRegularExpression('/hidden\s*\(\s*"vendor_long_/', $this->fileContent,
            'PROD uses hidden() for vendor_long');
        $this->assertMatchesRegularExpression('/hidden\s*\(\s*"partnerId_/', $this->fileContent,
            'PROD uses hidden() for partnerId');
        
        // Count occurrences
        $count = substr_count($this->fileContent, 'hidden(');
        $this->assertGreaterThan(10, $count, 'PROD has 10+ hidden() calls');
    }

    /**
     * Test 7: PROD uses hardcoded submit() function
     */
    public function testUsesSubmitFunction(): void
    {
        $this->assertMatchesRegularExpression('/submit\s*\(\s*"AddVendor/', $this->fileContent,
            'PROD uses submit() for AddVendor button');
        $this->assertMatchesRegularExpression('/submit\s*\(\s*"ProcessTransaction/', $this->fileContent,
            'PROD uses submit() for ProcessTransaction button');
        $this->assertMatchesRegularExpression('/submit\s*\(\s*"UnsetTrans/', $this->fileContent,
            'PROD uses submit() for UnsetTrans button');
    }

    /**
     * Test 8: PROD uses inline HTML echo statements
     */
    public function testUsesInlineHtmlEcho(): void
    {
        $this->assertStringContainsString('echo \'<td width="50%">\';', $this->fileContent,
            'PROD uses inline <td> echo');
        $this->assertStringContainsString('echo "</td>";', $this->fileContent,
            'PROD uses inline </td> echo');
        $this->assertStringContainsString('start_row();', $this->fileContent,
            'PROD uses start_row() function');
        $this->assertStringContainsString('end_row();', $this->fileContent,
            'PROD uses end_row() function');
    }

    /**
     * Test 9: PROD uses FA list functions (array_selector, supplier_list, etc.)
     */
    public function testUsesFAListFunctions(): void
    {
        $this->assertMatchesRegularExpression('/array_selector\s*\(\s*"partnerType/', $this->fileContent,
            'PROD uses array_selector() for partnerType');
        $this->assertMatchesRegularExpression('/supplier_list\s*\(/', $this->fileContent,
            'PROD uses supplier_list()');
        $this->assertMatchesRegularExpression('/customer_list\s*\(/', $this->fileContent,
            'PROD uses customer_list()');
        $this->assertMatchesRegularExpression('/bank_accounts_list\s*\(/', $this->fileContent,
            'PROD uses bank_accounts_list()');
        $this->assertMatchesRegularExpression('/quick_entries_list\s*\(/', $this->fileContent,
            'PROD uses quick_entries_list()');
    }

    /**
     * Test 10: PROD does NOT use HTML Elements namespace
     */
    public function testNoHtmlElementsNamespace(): void
    {
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlTable', $this->fileContent,
            'PROD does not use HtmlTable class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlTableRow', $this->fileContent,
            'PROD does not use HtmlTableRow class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlTableCell', $this->fileContent,
            'PROD does not use HtmlTableCell class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlString', $this->fileContent,
            'PROD does not use HtmlString class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlHidden', $this->fileContent,
            'PROD does not use HtmlHidden class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlSubmit', $this->fileContent,
            'PROD does not use HtmlSubmit class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Elements\HtmlSelect', $this->fileContent,
            'PROD does not use HtmlSelect class');
    }

    /**
     * Test 11: PROD does NOT use HTML Composites namespace
     */
    public function testNoHtmlCompositesNamespace(): void
    {
        $this->assertStringNotContainsString('Ksfraser\HTML\Composites\HtmlLabelRow', $this->fileContent,
            'PROD does not use HtmlLabelRow class');
        $this->assertStringNotContainsString('Ksfraser\HTML\Composites', $this->fileContent,
            'PROD does not use any Composites classes');
    }

    /**
     * Test 12: PROD does NOT use Data Provider classes
     */
    public function testNoDataProviderClasses(): void
    {
        $this->assertStringNotContainsString('SupplierDataProvider', $this->fileContent,
            'PROD does not use SupplierDataProvider');
        $this->assertStringNotContainsString('CustomerDataProvider', $this->fileContent,
            'PROD does not use CustomerDataProvider');
        $this->assertStringNotContainsString('BankAccountDataProvider', $this->fileContent,
            'PROD does not use BankAccountDataProvider');
        $this->assertStringNotContainsString('QuickEntryDataProvider', $this->fileContent,
            'PROD does not use QuickEntryDataProvider');
    }

    /**
     * Test 13: PROD transaction_table class uses simple display() method
     */
    public function testTransactionTableDisplayMethod(): void
    {
        $this->assertMatchesRegularExpression(
            '/function\s+display\s*\(\s*\)\s*\{[^}]*start_table\s*\(\s*TABLESTYLE/s',
            $this->fileContent,
            'PROD transaction_table::display() uses start_table(TABLESTYLE)'
        );
        $this->assertMatchesRegularExpression(
            '/function\s+display\s*\(\s*\)\s*\{[^}]*table_header/s',
            $this->fileContent,
            'PROD transaction_table::display() uses table_header()'
        );
    }

    /**
     * Test 14: PROD ttr_table class returns start_table directly
     */
    public function testTtrTableDisplayReturnsStartTable(): void
    {
        $this->assertMatchesRegularExpression(
            '/class\s+ttr_table[^{]*\{.*?function\s+display.*?return\s+start_table/s',
            $this->fileContent,
            'PROD ttr_table::display() returns start_table() directly'
        );
    }

    /**
     * Test 15: PROD has specific label_row patterns for settled transactions
     */
    public function testSettledTransactionLabelRows(): void
    {
        // Status = 1 (settled) section
        $this->assertStringContainsString('label_row("Status:", "<b>Transaction is settled!</b>"', $this->fileContent,
            'PROD uses label_row() for settled status');
        $this->assertStringContainsString('label_row("Operation:", "Payment")', $this->fileContent,
            'PROD uses label_row() for payment operation');
        $this->assertStringContainsString('label_row("Operation:", "Deposit")', $this->fileContent,
            'PROD uses label_row() for deposit operation');
        $this->assertStringContainsString('label_row("Operation:", "Manual settlement")', $this->fileContent,
            'PROD uses label_row() for manual settlement');
    }

    /**
     * Test 16: PROD file size is smaller (pre-abstraction)
     * This test documents that PROD is ~600 lines before HTML abstraction expansion
     */
    public function testFileSize(): void
    {
        $lineCount = count(file($this->filePath));
        $this->assertLessThan(650, $lineCount, 
            'PROD file should be under 650 lines (before HTML abstraction expansion)');
        $this->assertGreaterThan(550, $lineCount,
            'PROD file should be over 550 lines (sanity check)');
    }

    /**
     * Test 17: PROD uses customer_branches_list() function
     */
    public function testUsesCustomerBranchesList(): void
    {
        $this->assertMatchesRegularExpression('/customer_branches_list\s*\(/', $this->fileContent,
            'PROD uses customer_branches_list() function');
    }

    /**
     * Test 18: PROD does NOT use toHtml() method calls
     * (This is a signature of the new HTML abstraction classes)
     */
    public function testNoToHtmlMethodCalls(): void
    {
        $count = substr_count($this->fileContent, '->toHtml()');
        $this->assertEquals(0, $count, 
            'PROD should not have any ->toHtml() method calls (used in new HTML classes)');
    }

    /**
     * Test 19: PROD uses text_input() for existing entry field
     */
    public function testUsesTextInputFunction(): void
    {
        $this->assertMatchesRegularExpression('/text_input\s*\(\s*"Existing_Entry"/', $this->fileContent,
            'PROD uses text_input() function for manual entry');
    }

    /**
     * Test 20: PROD switch statement for transactionDC uses label_row directly
     */
    public function testTransactionDCSwitchUsesLabelRow(): void
    {
        // Find the switch statement for transactionDC and verify it uses label_row
        $this->assertMatchesRegularExpression(
            '/switch\s*\(\s*\$transactionDC\s*\)[^}]+case\s+\'C\':[^}]*label_row/s',
            $this->fileContent,
            'PROD uses label_row() inside transactionDC switch for Credit'
        );
        $this->assertMatchesRegularExpression(
            '/switch\s*\(\s*\$transactionDC\s*\)[^}]+case\s+\'D\':[^}]*label_row/s',
            $this->fileContent,
            'PROD uses label_row() inside transactionDC switch for Debit'
        );
        $this->assertMatchesRegularExpression(
            '/switch\s*\(\s*\$transactionDC\s*\)[^}]+case\s+\'B\':[^}]*label_row/s',
            $this->fileContent,
            'PROD uses label_row() inside transactionDC switch for Bank Transfer'
        );
    }
}
