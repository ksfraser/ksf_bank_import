<?php
/**
 * Modern Architecture Guard for class.transactions_table.php
 *
 * INVERSE of the former ProductionBaselineTest: that file pinned the
 * pre-HTML-abstraction state (hardcoded FA HTML functions). The abstraction
 * refactor has landed, so this guard now asserts the CURRENT contract:
 *
 * - Uses Ksfraser\HTML\Elements / Composites classes
 * - Uses data-provider select generators (no FA list functions)
 * - No regression to inline procedural HTML functions
 *
 * If this test fails with "uses label_row()" style messages, someone has
 * reverted the HTML abstraction refactor.
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
        // Strip comments so legacy calls kept as documentation don't trip
        // the regression guards.
        $raw = file_get_contents($this->filePath);
        $this->fileContent = preg_replace('/^\s*\/\/.*$/m', '', $raw);
    }

    /** Guard: file exists and is readable */
    public function testFileExists(): void
    {
        $this->assertNotEmpty($this->fileContent);
    }

    /** Guard: core table classes still present */
    public function testHasCoreClasses(): void
    {
        foreach (['transaction_table', 'transaction_table_row', 'ttr_table', 'ttr_label_row'] as $class) {
            $this->assertMatchesRegularExpression(
                "/class\\s+$class/",
                $this->fileContent,
                "Core class $class must remain"
            );
        }
    }

    /** Guard: uses the Elements namespace */
    public function testUsesHtmlElementsNamespace(): void
    {
        $this->assertStringContainsString('Ksfraser\\HTML\\Elements', $this->fileContent,
            'Should use the HTML Elements abstraction');
    }

    /** Guard: uses the Composites namespace */
    public function testUsesHtmlCompositesNamespace(): void
    {
        $this->assertStringContainsString('Ksfraser\\HTML\\Composites', $this->fileContent,
            'Should use the HTML Composites abstraction');
    }

    /** Guard: no regression to hardcoded FA HTML functions */
    public function testNoLegacyInlineHtmlFunctions(): void
    {
        foreach (['label_row(', 'start_table(', 'end_table(', 'hidden(', 'submit_center'] as $fn) {
            $count = substr_count($this->fileContent, $fn);
            $this->assertSame(
                0,
                $count,
                "Reverted to legacy '$fn' ($count occurrences) - HTML abstraction regression"
            );
        }
    }

    /** Guard: no regression to FA list selector functions */
    public function testNoLegacyFaListFunctions(): void
    {
        foreach (['supplier_list(', 'customer_list(', 'bank_accounts_list(', 'quick_entries_list('] as $fn) {
            $count = substr_count($this->fileContent, $fn);
            $this->assertSame(
                0,
                $count,
                "Reverted to legacy list function '$fn' - use DataProvider generators"
            );
        }
    }

    /** Guard: uses data provider select generators */
    public function testUsesDataProviderSelectGenerators(): void
    {
        foreach (['SupplierDataProvider', 'CustomerDataProvider', 'BankAccountDataProvider'] as $provider) {
            $this->assertStringContainsString($provider, $this->fileContent,
                "Should use $provider for select generation");
        }
    }

    /** Guard: no direct echo of raw <td>/<tr> markup */
    public function testNoInlineTableCellEchoes(): void
    {
        $this->assertSame(0, substr_count($this->fileContent, "echo '<td"),
            'Reverted to inline <td> echoes - use HtmlTableRow/HtmlTableCell');
        $this->assertSame(0, substr_count($this->fileContent, "echo \"<td"),
            'Reverted to inline <td> echoes - use HtmlTableRow/HtmlTableCell');
    }
}
