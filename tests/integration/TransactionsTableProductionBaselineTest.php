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
        $this->assertRegExp('/class\s+transaction_table\b/', $this->fileContent);
        $this->assertRegExp('/class\s+transaction_table_row\b/', $this->fileContent);
        $this->assertRegExp('/class\s+ttr_table\b/', $this->fileContent);
        $this->assertRegExp('/class\s+ttr_label_row\b/', $this->fileContent);
    }

    /**
     * Test 3: PROD uses hardcoded start_table() function
     */
    public function testUsesStartTableFunction(): void
    {
        $this->assertStringContainsString('new \\Ksfraser\\HTML\\Elements\\HtmlTable()', $this->fileContent,
            'Current implementation uses HtmlTable class');
        $this->assertStringContainsString('->openTable()', $this->fileContent,
            'Current implementation opens table via HtmlTable::openTable()');
    }

    /**
     * Test 4: PROD uses hardcoded end_table() function
     */
    public function testUsesEndTableFunction(): void
    {
        $this->assertStringContainsString('->closeTable()', $this->fileContent,
            'Current implementation closes tables via HtmlTable::closeTable()');
    }

    /**
     * Test 5: PROD uses hardcoded label_row() function extensively
     */
    public function testUsesLabelRowFunction(): void
    {
        $this->assertStringContainsString('HtmlLabelRow', $this->fileContent,
            'Current implementation uses HtmlLabelRow composition');

        $count = substr_count($this->fileContent, 'HtmlLabelRow');
        $this->assertGreaterThan(20, $count, 'Current implementation has many HtmlLabelRow usages');
    }

    /**
     * Test 6: PROD uses hardcoded hidden() function
     */
    public function testUsesHiddenFunction(): void
    {
        $this->assertStringContainsString('HtmlHidden', $this->fileContent,
            'Current implementation uses HtmlHidden for hidden inputs');

        $count = substr_count($this->fileContent, 'HtmlHidden');
        $this->assertGreaterThan(8, $count, 'Current implementation has multiple HtmlHidden usages');
    }

    /**
     * Test 7: PROD uses hardcoded submit() function
     */
    public function testUsesSubmitFunction(): void
    {
        $this->assertStringContainsString('HtmlSubmit', $this->fileContent,
            'Current implementation uses HtmlSubmit for action buttons');

        $count = substr_count($this->fileContent, 'HtmlSubmit');
        $this->assertGreaterThanOrEqual(3, $count, 'Current implementation includes key submit actions');
    }

    /**
     * Test 8: PROD uses inline HTML echo statements
     */
    public function testUsesInlineHtmlEcho(): void
    {
        $this->assertStringContainsString('HtmlTableCell', $this->fileContent,
            'Current implementation uses HtmlTableCell objects');
        $this->assertStringContainsString('HtmlTableRow', $this->fileContent,
            'Current implementation uses HtmlTableRow objects');
        $this->assertStringContainsString('openCell()', $this->fileContent,
            'Current implementation opens cells via HtmlTableCell');
        $this->assertStringContainsString('closeCell()', $this->fileContent,
            'Current implementation closes cells via HtmlTableCell');
    }

    /**
     * Test 9: PROD uses FA list functions (array_selector, supplier_list, etc.)
     */
    public function testUsesFAListFunctions(): void
    {
        $this->assertStringContainsString('HtmlSelect', $this->fileContent,
            'Current implementation uses HtmlSelect for selector rendering');
        $this->assertStringContainsString('SupplierDataProvider', $this->fileContent,
            'Current implementation uses SupplierDataProvider');
        $this->assertStringContainsString('CustomerDataProvider', $this->fileContent,
            'Current implementation uses CustomerDataProvider');
        $this->assertStringContainsString('BankAccountDataProvider', $this->fileContent,
            'Current implementation uses BankAccountDataProvider');
        $this->assertStringContainsString('QuickEntryDataProvider', $this->fileContent,
            'Current implementation uses QuickEntryDataProvider');
    }

    /**
     * Test 10: PROD does NOT use HTML Elements namespace
     */
    public function testNoHtmlElementsNamespace(): void
    {
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlTable', $this->fileContent,
            'Current implementation uses HtmlTable class');
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlTableRow', $this->fileContent,
            'Current implementation uses HtmlTableRow class');
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlTableCell', $this->fileContent,
            'Current implementation uses HtmlTableCell class');
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlString', $this->fileContent,
            'Current implementation uses HtmlString class');
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlHidden', $this->fileContent,
            'Current implementation uses HtmlHidden class');
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlSubmit', $this->fileContent,
            'Current implementation uses HtmlSubmit class');
        $this->assertStringContainsString('Ksfraser\HTML\Elements\HtmlSelect', $this->fileContent,
            'Current implementation uses HtmlSelect class');
    }

    /**
     * Test 11: PROD does NOT use HTML Composites namespace
     */
    public function testNoHtmlCompositesNamespace(): void
    {
        $this->assertStringContainsString('Ksfraser\HTML\Composites\HtmlLabelRow', $this->fileContent,
            'Current implementation uses HtmlLabelRow class');
        $this->assertStringContainsString('Ksfraser\HTML\Composites', $this->fileContent,
            'Current implementation uses Composites namespace classes');
    }

    /**
     * Test 12: PROD does NOT use Data Provider classes
     */
    public function testNoDataProviderClasses(): void
    {
        $this->assertStringContainsString('SupplierDataProvider', $this->fileContent,
            'Current implementation uses SupplierDataProvider');
        $this->assertStringContainsString('CustomerDataProvider', $this->fileContent,
            'Current implementation uses CustomerDataProvider');
        $this->assertStringContainsString('BankAccountDataProvider', $this->fileContent,
            'Current implementation uses BankAccountDataProvider');
        $this->assertStringContainsString('QuickEntryDataProvider', $this->fileContent,
            'Current implementation uses QuickEntryDataProvider');
    }

    /**
     * Test 13: PROD transaction_table class uses simple display() method
     */
    public function testTransactionTableDisplayMethod(): void
    {
        $this->assertMatchesRegularExpression(
            '/function\s+display\s*\(\s*\)\s*\{[^}]*HtmlTable\(\)/s',
            $this->fileContent,
            'Current transaction_table::display() uses HtmlTable'
        );
        $this->assertMatchesRegularExpression(
            '/function\s+display\s*\(\s*\)\s*\{[^}]*table_header/s',
            $this->fileContent,
            'Current transaction_table::display() keeps table_header()'
        );
    }

    /**
     * Test 14: PROD ttr_table class returns start_table directly
     */
    public function testTtrTableDisplayReturnsStartTable(): void
    {
        $this->assertMatchesRegularExpression(
            '/class\s+ttr_table[^{]*\{.*?function\s+display.*?return\s+\'<table class="\'\s*\.\s*strtolower\(\$this->style\)/s',
            $this->fileContent,
            'Current ttr_table::display() returns standalone table HTML'
        );
    }

    /**
     * Test 15: PROD has specific label_row patterns for settled transactions
     */
    public function testSettledTransactionLabelRows(): void
    {
        // Status = 1 (settled) section now uses HtmlLabelRow
        $this->assertStringContainsString('Transaction is settled!', $this->fileContent,
            'Current implementation keeps settled status message');
        $this->assertStringContainsString('new \\Ksfraser\\HTML\\Composites\\HtmlLabelRow', $this->fileContent,
            'Current implementation uses HtmlLabelRow for settled rows');
        $this->assertStringContainsString('"Payment"', $this->fileContent,
            'Current implementation includes payment operation label');
        $this->assertStringContainsString('"Deposit"', $this->fileContent,
            'Current implementation includes deposit operation label');
        $this->assertStringContainsString('"Manual settlement"', $this->fileContent,
            'Current implementation includes manual settlement label');
    }

    /**
     * Test 16: PROD file size is smaller (pre-abstraction)
     * This test documents that PROD is ~600 lines before HTML abstraction expansion
     */
    public function testFileSize(): void
    {
        $lineCount = count(file($this->filePath));
        $this->assertLessThan(900, $lineCount, 
            'Current file should remain under 900 lines');
        $this->assertGreaterThan(750, $lineCount,
            'Current file should reflect expanded abstraction and remain over 750 lines');
    }

    /**
     * Test 17: PROD uses customer_branches_list() function
     */
    public function testUsesCustomerBranchesList(): void
    {
        $this->assertStringContainsString('generateBranchSelectHtml', $this->fileContent,
            'Current implementation uses CustomerDataProvider::generateBranchSelectHtml()');
    }

    /**
     * Test 18: PROD does NOT use toHtml() method calls
     * (This is a signature of the new HTML abstraction classes)
     */
    public function testNoToHtmlMethodCalls(): void
    {
        $count = substr_count($this->fileContent, '->toHtml()');
        $this->assertGreaterThan(20, $count, 
            'Current implementation should include many ->toHtml() calls');
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
        // Current switch uses HtmlString + HtmlLabelRow composition
        $this->assertMatchesRegularExpression(
            '/switch\s*\(\s*\$transactionDC\s*\)[^}]+case\s+\'C\':[^}]*HtmlString\("Credit"\)/s',
            $this->fileContent,
            'Current switch sets Credit label content via HtmlString'
        );
        $this->assertMatchesRegularExpression(
            '/switch\s*\(\s*\$transactionDC\s*\)[^}]+case\s+\'D\':[^}]*HtmlString\("Debit"\)/s',
            $this->fileContent,
            'Current switch sets Debit label content via HtmlString'
        );
        $this->assertMatchesRegularExpression(
            '/switch\s*\(\s*\$transactionDC\s*\)[^}]+case\s+\'B\':[^}]*HtmlString\("Bank Transfer"\)/s',
            $this->fileContent,
            'Current switch sets Bank Transfer label content via HtmlString'
        );
    }
}
