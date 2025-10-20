<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HTML_TABLE;
use Ksfraser\HTML\HtmlString;

/**
 * Test HTML_TABLE accepts HtmlElementInterface
 * 
 * This tests the enhancement we made to HTML_TABLE to accept
 * objects implementing HtmlElementInterface, not just HTML_ROW.
 * 
 * @package Tests\Unit\Views
 * @since 20251019
 */
class HTML_TABLE_HtmlElementTest extends TestCase
{
    /**
     * Test that HTML_TABLE can accept HtmlElementInterface objects
     */
    public function testAcceptsHtmlElementInterface(): void
    {
        $table = new HTML_TABLE();
        $element = new HtmlString('Test Content');
        
        // Should not throw exception
        $table->appendRow($element);
        
        $html = $table->getHtml();
        $this->assertStringContainsString('Test Content', $html);
    }
    
    /**
     * Test that HTML_TABLE wraps HtmlElementInterface in HTML_ROW
     */
    public function testWrapsHtmlElementInRow(): void
    {
        $table = new HTML_TABLE();
        $element1 = new HtmlString('Row 1');
        $element2 = new HtmlString('Row 2');
        
        $table->appendRow($element1);
        $table->appendRow($element2);
        
        $html = $table->getHtml();
        $this->assertStringContainsString('Row 1', $html);
        $this->assertStringContainsString('Row 2', $html);
        $this->assertMatchesRegularExpression('/<tr\s/', $html); // Allow spaces after tr
    }
    
    /**
     * Test that constructor accepts null style (defaults to 2)
     */
    public function testConstructorAcceptsNull(): void
    {
        $table = new HTML_TABLE(null, 90);
        
        $html = $table->getHtml();
        $this->assertStringContainsString('tablestyle2', $html);
        $this->assertStringContainsString('width=\'90%\'', $html);
    }
}
