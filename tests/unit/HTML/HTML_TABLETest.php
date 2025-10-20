<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HTML_TABLE;
use Ksfraser\HTML\HTML_ROW;

/**
 * Test HTML_TABLE backward-compatible wrapper
 * 
 * @package Tests\Unit\HTML
 * @since 20251019
 */
class HTML_TABLETest extends TestCase
{
    /**
     * Test that constructor sets default style and width
     */
    public function testConstructorSetsDefaults(): void
    {
        $table = new HTML_TABLE();
        
        // Verify table can be instantiated
        $this->assertInstanceOf(HTML_TABLE::class, $table);
        
        // Get HTML and check for table tag with default style
        $html = $table->getHtml();
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('tablestyle2', $html);
        $this->assertStringContainsString('width=\'100%\'', $html);
    }
    
    /**
     * Test that constructor accepts custom style and width
     */
    public function testConstructorSetsCustomValues(): void
    {
        $table = new HTML_TABLE(1, 80);
        
        $html = $table->getHtml();
        $this->assertStringContainsString('tablestyle1', $html);
        $this->assertStringContainsString('width=\'80%\'', $html);
    }
    
    /**
     * Test appending HTML_ROW object
     */
    public function testAppendRowObject(): void
    {
        $table = new HTML_TABLE();
        $row = new HTML_ROW('Test Content');
        
        $table->appendRow($row);
        
        $html = $table->getHtml();
        $this->assertStringContainsString('Test Content', $html);
    }
    
    /**
     * Test appending string (should create HTML_ROW automatically)
     */
    public function testAppendRowString(): void
    {
        $table = new HTML_TABLE();
        
        $table->appendRow('String Content');
        
        $html = $table->getHtml();
        $this->assertStringContainsString('String Content', $html);
    }
    
    /**
     * Test appending multiple rows
     */
    public function testAppendMultipleRows(): void
    {
        $table = new HTML_TABLE();
        
        $table->appendRow('Row 1');
        $table->appendRow(new HTML_ROW('Row 2'));
        $table->appendRow('Row 3');
        
        $html = $table->getHtml();
        $this->assertStringContainsString('Row 1', $html);
        $this->assertStringContainsString('Row 2', $html);
        $this->assertStringContainsString('Row 3', $html);
    }
    
    /**
     * Test that invalid row type throws exception
     */
    public function testAppendRowInvalidTypeThrowsException(): void
    {
        $table = new HTML_TABLE();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Passed in data for a row is neither a class nor a string');
        
        $table->appendRow(123); // Integer should throw exception
    }
    
    /**
     * Test that non-HTML_ROW object throws exception
     */
    public function testAppendRowInvalidObjectThrowsException(): void
    {
        $table = new HTML_TABLE();
        $invalidObject = new \stdClass();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Passed in class is not an HTML_ROW or child type!');
        
        $table->appendRow($invalidObject);
    }
    
    /**
     * Test toHtml outputs directly
     */
    public function testToHtmlOutputsDirectly(): void
    {
        $table = new HTML_TABLE();
        $table->appendRow('Test Output');
        
        ob_start();
        $table->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('Test Output', $output);
        $this->assertStringContainsString('</table>', $output);
    }
}
