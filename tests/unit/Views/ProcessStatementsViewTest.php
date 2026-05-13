<?php

namespace Ksfraser\FaBankImport\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\ProcessStatementsView;

/**
 * Unit tests for ProcessStatementsView
 *
 * @package Ksfraser\FaBankImport\Tests\Unit\Views
 */
class ProcessStatementsViewTest extends TestCase
{
    /**
     * Test that ProcessStatementsView can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $transactions = [];
        $operationTypes = [];
        $vendorList = [];

        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);

        $this->assertInstanceOf(ProcessStatementsView::class, $view);
    }

    /**
     * Test that render method returns HTML string
     */
    public function testRenderReturnsHtmlString(): void
    {
        $transactions = [];
        $operationTypes = [];
        $vendorList = [];

        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $html = $view->render();

        $this->assertIsString($html);
        $this->assertStringContains('<form', $html);
        $this->assertStringContains('<div id="doc_tbl">', $html);
        $this->assertStringContains('</form>', $html);
    }

    /**
     * Test that render includes form structure
     */
    public function testRenderIncludesFormStructure(): void
    {
        $transactions = [];
        $operationTypes = [];
        $vendorList = [];

        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $html = $view->render();

        // Should contain form tags
        $this->assertStringContains('<form', $html);
        $this->assertStringContains('</form>', $html);

        // Should contain document table div
        $this->assertStringContains('<div id="doc_tbl">', $html);
        $this->assertStringContains('</div>', $html);
    }

    /**
     * Test that transaction table is rendered
     */
    public function testRendersTransactionTable(): void
    {
        $transactions = [
            'test_transaction' => [
                ['id' => 1, 'amount' => 100.00] // Mock transaction data
            ]
        ];
        $operationTypes = ['SP' => 'Supplier', 'CU' => 'Customer'];
        $vendorList = ['vendor1' => 'Test Vendor'];

        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $html = $view->render();

        // Should contain table structure
        $this->assertStringContains('<table', $html);
        $this->assertStringContains('TABLESTYLE', $html);
        $this->assertStringContains('<thead>', $html);
        $this->assertStringContains('<tbody>', $html);
    }

    /**
     * Test that table headers are correct
     */
    public function testRendersCorrectTableHeaders(): void
    {
        $transactions = [];
        $operationTypes = [];
        $vendorList = [];

        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $html = $view->render();

        $this->assertStringContains('Transaction Details', $html);
        $this->assertStringContains('Operation/Status', $html);
    }
}