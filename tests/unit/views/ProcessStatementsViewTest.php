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
        $this->assertStringContainsString('<div id="doc_tbl">', $html);
        $this->assertStringContainsString('<table', $html);
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

        // Should contain document table div
        $this->assertStringContainsString('<div id="doc_tbl">', $html);
        $this->assertStringContainsString('</div>', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    /**
     * Test that transaction table is rendered
     */
    public function testRendersTransactionTable(): void
    {
        $transactions = [
            'test_transaction' => [
                [
                    'id' => 1,
                    'amount' => 100.00,
                    'transactionDC' => 'D',
                    'memo' => 'Test memo',
                    'our_account' => 'Test Account',
                    'valueTimestamp' => '2026-01-01',
                    'entryTimestamp' => '2026-01-01',
                    'accountName' => 'Test Bank',
                    'transactionTitle' => 'Test Title',
                    'transactionCode' => 'TEST',
                    'transactionCodeDesc' => 'Test Code',
                    'currency' => 'CAD',
                    'status' => 'pending',
                    'fa_trans_type' => 0,
                    'fa_trans_no' => 0,
                    'transactionAmount' => 100.00,
                    'transactionType' => 'TRN',
                ]
            ]
        ];
        $operationTypes = ['SP' => 'Supplier', 'CU' => 'Customer'];
        $vendorList = ['vendor1' => 'Test Vendor'];

        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $html = $view->render();

        // Should contain table structure
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('TABLESTYLE', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
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

        $this->assertStringContainsString('Transaction Details', $html);
        $this->assertStringContainsString('Operation/Status', $html);
    }
}