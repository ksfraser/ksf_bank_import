<?php
namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

// Load the classes from views directory (global namespace)
require_once __DIR__ . '/../../../Views/TransType.php';
// Load bi_lineitem class for mocking
require_once __DIR__ . '/../../../class.bi_lineitem.php';

/**
 * Test TransType view component
 * 
 * @package Tests\Unit\Views
 * @since 20251019
 */
class TransTypeTest extends TestCase
{
    /**
     * Create a mock bi_lineitem object for testing
     */
    private function createMockLineitem(string $transactionTypeLabel = 'Debit'): object
    {
        $mock = $this->createMock(\bi_lineitem::class);
        $mock->method('getTransactionTypeLabel')
            ->willReturn($transactionTypeLabel);
        
        return $mock;
    }
    
    /**
     * Test that class can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $lineitem = $this->createMockLineitem();
        $transType = new \TransType($lineitem);
        
        $this->assertInstanceOf(\TransType::class, $transType);
    }
    
    /**
     * Test debit transaction type
     */
    public function testDebitType(): void
    {
        $lineitem = $this->createMockLineitem('Debit');
        $transType = new \TransType($lineitem);
        
        $html = $transType->getHtml();
        
        $this->assertStringContainsString('Debit', $html);
$this->assertStringContainsString('Trans type:', $html);
    }
    
    /**
     * Test credit transaction type
     */
    public function testCreditType(): void
    {
        $lineitem = $this->createMockLineitem('Credit');
        $transType = new \TransType($lineitem);
        
        $html = $transType->getHtml();
        
        $this->assertStringContainsString('Credit', $html);
        $this->assertStringContainsString('Trans type:', $html);
    }
    
    /**
     * Test bank transfer transaction type
     */
    public function testBankTransferType(): void
    {
        $lineitem = $this->createMockLineitem('Bank Transfer');
        $transType = new \TransType($lineitem);
        
        $html = $transType->getHtml();
        
        $this->assertStringContainsString('Bank Transfer', $html);
        $this->assertStringContainsString('Trans type:', $html);
    }
    
    /**
     * Test that getHtml returns string
     */
    public function testGetHtmlReturnsString(): void
    {
        $lineitem = $this->createMockLineitem();
        $transType = new \TransType($lineitem);
        
        $html = $transType->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * Test that toHtml outputs directly
     */
    public function testToHtmlOutputsDirectly(): void
    {
        $lineitem = $this->createMockLineitem('Credit');
        $transType = new \TransType($lineitem);
        
        ob_start();
        $transType->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Credit', $output);
        $this->assertStringContainsString('Trans type:', $output);
        $this->assertNotEmpty($output);
    }
}