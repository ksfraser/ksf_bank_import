<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

// Load the class from views directory (global namespace)
require_once __DIR__ . '/../../../views/TransDate.php';

/**
 * Test TransDate view component
 * 
 * @package Tests\Unit\Views
 * @since 20251019
 */
class TransDateTest extends TestCase
{
    /**
     * Create a mock bi_lineitem object for testing
     */
    private function createMockLineitem(): object
    {
        $mock = new \stdClass();
        $mock->valueTimestamp = '2025-10-19';
        $mock->entryTimestamp = '2025-10-19 14:30:00';
        
        return $mock;
    }
    
    /**
     * Test that class can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $lineitem = $this->createMockLineitem();
        $transDate = new \TransDate($lineitem);
        
        $this->assertInstanceOf(\TransDate::class, $transDate);
    }
    
    /**
     * Test that getHtml returns string
     */
    public function testGetHtmlReturnsString(): void
    {
        $lineitem = $this->createMockLineitem();
        $transDate = new \TransDate($lineitem);
        
        $html = $transDate->getHtml();
        
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }
    
    /**
     * Test that getHtml contains the date values
     */
    public function testGetHtmlContainsDateValues(): void
    {
        $lineitem = $this->createMockLineitem();
        $transDate = new \TransDate($lineitem);
        
        $html = $transDate->getHtml();
        
        $this->assertStringContainsString('2025-10-19', $html);
        $this->assertStringContainsString('2025-10-19 14:30:00', $html);
        $this->assertStringContainsString('::', $html); // The separator
    }
    
    /**
     * Test that getHtml contains the label
     */
    public function testGetHtmlContainsLabel(): void
    {
        $lineitem = $this->createMockLineitem();
        $transDate = new \TransDate($lineitem);
        
        $html = $transDate->getHtml();
        
        $this->assertStringContainsString('Trans Date (Event Date)', $html);
    }
    
    /**
     * Test that getHtml contains table row tags
     */
    public function testGetHtmlContainsTableRow(): void
    {
        $lineitem = $this->createMockLineitem();
        $transDate = new \TransDate($lineitem);
        
        $html = $transDate->getHtml();
        
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('</tr>', $html);
        $this->assertStringContainsString('<td', $html); // Table cells
    }
    
    /**
     * Test that toHtml outputs directly
     */
    public function testToHtmlOutputsDirectly(): void
    {
        $lineitem = $this->createMockLineitem();
        $transDate = new \TransDate($lineitem);
        
        ob_start();
        $transDate->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('2025-10-19', $output);
        $this->assertNotEmpty($output);
    }
}
