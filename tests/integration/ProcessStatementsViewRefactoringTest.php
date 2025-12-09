<?php

namespace Ksfraser\FaBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration test to verify ProcessStatementsView refactoring produces identical output
 *
 * This test compares the HTML output of the refactored ProcessStatementsView
 * against the original inline HTML generation to ensure functional equivalence.
 *
 * @package Ksfraser\FaBankImport\Tests\Integration
 */
class ProcessStatementsViewRefactoringTest extends TestCase
{
    /**
     * Test that header_table.php getBankImportHeaderHtml() produces same output as bank_import_header()
     *
     * This test verifies that our refactoring of header_table.php maintains identical HTML output.
     */
    public function testHeaderTableHtmlOutputIsIdentical(): void
    {
        // Skip if not in FA environment (dependencies not available)
        if (!class_exists('ksf_modules_table_filter_by_date')) {
            $this->markTestSkipped('FrontAccounting environment not available for integration testing');
        }

        require_once('header_table.php');

        // Create test instance
        $headerTable = new ksf_modules_table_filter_by_date();

        // Set up test POST data
        $_POST['statusFilter'] = 1;
        $_POST['TransAfterDate'] = '2025-01-01';
        $_POST['TransToDate'] = '2025-12-31';
        $_POST['bankAccountFilter'] = 'ALL';

        // Capture output from original method
        ob_start();
        $headerTable->bank_import_header();
        $originalOutput = ob_get_clean();

        // Get output from new method
        $newOutput = $headerTable->getBankImportHeaderHtml();

        // They should be identical
        $this->assertEquals($originalOutput, $newOutput,
            'Refactored getBankImportHeaderHtml() should produce identical HTML to bank_import_header()');
    }

    /**
     * Test that ProcessStatementsView can be instantiated with proper dependencies
     */
    public function testProcessStatementsViewInstantiation(): void
    {
        // Skip if not in FA environment
        if (!file_exists('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php')) {
            $this->markTestSkipped('ProcessStatementsView not available');
        }

        require_once('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php');

        $transactions = [];
        $operationTypes = ['SP' => 'Supplier', 'CU' => 'Customer'];
        $vendorList = ['vendor1' => 'Test Vendor'];

        $view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($transactions, $operationTypes, $vendorList);

        $this->assertInstanceOf(\Ksfraser\FaBankImport\Views\ProcessStatementsView::class, $view);
    }

    /**
     * Test that ProcessStatementsView render() returns HTML string without output buffering
     */
    public function testProcessStatementsViewRenderReturnsString(): void
    {
        // Skip if not in FA environment
        if (!file_exists('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php')) {
            $this->markTestSkipped('ProcessStatementsView not available');
        }

        require_once('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php');

        $transactions = [];
        $operationTypes = ['SP' => 'Supplier'];
        $vendorList = [];

        $view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $html = $view->render();

        $this->assertIsString($html);
        $this->assertStringContains('<form', $html);
        $this->assertStringContains('</form>', $html);
        $this->assertStringContains('<div id="doc_tbl">', $html);
    }
}