<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlHidden;

/**
 * Test HtmlHidden Class
 * 
 * @coversDefaultClass \Ksfraser\HTML\HtmlHidden
 */
class HtmlHiddenTest extends TestCase
{
    /**
     * Test basic hidden field with name and value
     * 
     * @covers ::__construct
     * @covers ::getHtml
     */
    public function testBasicHiddenField(): void
    {
        $hidden = new HtmlHidden("user_id", "12345");
        $html = $hidden->getHtml();
        
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="user_id"', $html);
        $this->assertStringContainsString('value="12345"', $html);
    }
    
    /**
     * Test hidden field with only name (no value)
     * 
     * @covers ::__construct
     * @covers ::getHtml
     */
    public function testHiddenFieldWithNameOnly(): void
    {
        $hidden = new HtmlHidden("field_name");
        $html = $hidden->getHtml();
        
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="field_name"', $html);
    }
    
    /**
     * Test hidden field with empty constructor (using fluent interface)
     * 
     * @covers ::__construct
     */
    public function testHiddenFieldWithFluentInterface(): void
    {
        $hidden = (new HtmlHidden())
            ->setName("customer_id")
            ->setValue("42");
        
        $html = $hidden->getHtml();
        
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="customer_id"', $html);
        $this->assertStringContainsString('value="42"', $html);
    }
    
    /**
     * Test hidden field with special characters (should be escaped)
     * 
     * @covers ::getHtml
     */
    public function testHiddenFieldEscapesSpecialCharacters(): void
    {
        $hidden = new HtmlHidden("field", "<script>alert('xss')</script>");
        $html = $hidden->getHtml();
        
        // Should escape the value
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
    
    /**
     * Test hidden field with empty value
     * 
     * @covers ::__construct
     * @covers ::getHtml
     */
    public function testHiddenFieldWithEmptyValue(): void
    {
        $hidden = new HtmlHidden("empty_field", "");
        $html = $hidden->getHtml();
        
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="empty_field"', $html);
        $this->assertStringContainsString('value=""', $html);
    }
    
    /**
     * Test toHtml outputs correctly
     * 
     * @covers ::toHtml
     */
    public function testToHtmlOutputsCorrectly(): void
    {
        $hidden = new HtmlHidden("test", "value");
        
        ob_start();
        $hidden->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('name="test"', $output);
        $this->assertStringContainsString('value="value"', $output);
    }
    
    /**
     * Test hidden field is self-closing (empty element)
     * 
     * @covers ::getHtml
     */
    public function testHiddenFieldIsSelfClosing(): void
    {
        $hidden = new HtmlHidden("test", "value");
        $html = $hidden->getHtml();
        
        // Should be self-closing or have no closing tag
        $this->assertStringNotContainsString('</input>', $html);
        $this->assertStringContainsString('<input', $html);
    }
    
    /**
     * Test constructor with null values
     * 
     * @covers ::__construct
     */
    public function testConstructorWithNullValues(): void
    {
        $hidden = new HtmlHidden(null, null);
        $html = $hidden->getHtml();
        
        $this->assertStringContainsString('type="hidden"', $html);
        // Should not crash
        $this->assertIsString($html);
    }
}
