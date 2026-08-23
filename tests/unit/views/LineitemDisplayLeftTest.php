<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

// Load the class from views directory (global namespace)
require_once __DIR__ . '/../../../views/LineitemDisplayLeft.php';

// Load all the dependencies (TransDate, TransType, etc.)
require_once __DIR__ . '/../../../views/TransDate.php';
require_once __DIR__ . '/../../../views/TransType.php';
require_once __DIR__ . '/../../../views/OurBankAccount.php';
require_once __DIR__ . '/../../../views/OtherBankAccount.php';
require_once __DIR__ . '/../../../views/AmountCharges.php';
require_once __DIR__ . '/../../../views/TransTitle.php';

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
     *
     * Property names mirror the real bi_lineitem contract consumed by
     * the TransDate/TransType/OurBankAccount/OtherBankAccount/AmountCharges/
     * TransTitle view classes.
     */
    private function createMockLineitem(): object
    {
        $mock = new \stdClass();
        $mock->valueTimestamp = '2025-10-19';
        $mock->entryTimestamp = '2025-10-19 10:00:00';
        $mock->transactionDC = 'C';
        $mock->our_account = 'ACC-001';
        $mock->ourBankDetails = ['bank_name' => '', 'bank_account_name' => '', 'account_code' => ''];
        $mock->ourBankAccountName = 'Our Account Name';
        $mock->ourBankAccountCode = 'OA001';
        $mock->otherBankAccount = 'ACC-002';
        $mock->otherBankAccountName = 'Other Bank';
        $mock->amount = '1000.00';
        $mock->charge = '5.00';
        $mock->currency = 'CAD';
        $mock->transactionTitle = 'Test Transaction';
        
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
