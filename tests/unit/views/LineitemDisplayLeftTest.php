<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

// Load bi_lineitem class for mocking
require_once __DIR__ . '/../../../class.bi_lineitem.php';

// Load the shim (which loads the namespaced version)
require_once __DIR__ . '/../../../Views/LineitemDisplayLeft.php';

/**
 * Test LineitemDisplayLeft view component
 * 
 * @package Tests\Unit\Views
 * @since 20251019
 */
class LineitemDisplayLeftTest extends TestCase
{
    /**
     * Create a mock bi_lineitem object for testing
     */
    private function createMockLineitem(): object
    {
        $mock = $this->createMock(\bi_lineitem::class);
        $mock->method('getValueTimestamp')->willReturn('2025-10-19');
        $mock->method('getEntryTimestamp')->willReturn('2025-10-19 10:00:00');
        $mock->method('getTransactionTypeLabel')->willReturn('Deposit');
        $mock->method('getTransactionDC')->willReturn('D');
        $mock->method('getOurAccount')->willReturn('ACC-001');
        $mock->method('getOtherBankAccount')->willReturn('ACC-002');
        $mock->method('getOtherBankAccountName')->willReturn('Other Bank');
        $mock->method('getAmount')->willReturn(1000.00);
        $mock->method('getCharge')->willReturn(5.00);
        $mock->method('getTransactionTitle')->willReturn('Test Transaction');
        $mock->method('getOurBankDetails')->willReturn(['bank_name' => 'Test Bank', 'account_name' => 'Test Account']);
        $mock->method('getCurrency')->willReturn('USD');
        
        return $mock;
    }
    
    /**
     * Test that class can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $lineitem = $this->createMockLineitem();
        $display = new \LineitemDisplayLeft($lineitem);
        
        $this->assertInstanceOf(\LineitemDisplayLeft::class, $display);
    }
    
    /**
     * Test that getHtml returns string
     */
    public function testGetHtmlReturnsString(): void
    {
        $lineitem = $this->createMockLineitem();
        $display = new \LineitemDisplayLeft($lineitem);
        
        $html = $display->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * Test that getHtml contains table tags
     */
    public function testGetHtmlContainsTableTags(): void
    {
        $lineitem = $this->createMockLineitem();
        $display = new \LineitemDisplayLeft($lineitem);
        
        $html = $display->getHtml();
        
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }
    
    /**
     * Test that toHtml outputs directly
     */
    public function testToHtmlOutputsDirectly(): void
    {
        $lineitem = $this->createMockLineitem();
        $display = new \LineitemDisplayLeft($lineitem);
        
        ob_start();
        $display->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<table', $output);
        $this->assertNotEmpty($output);
    }
}