<?php

namespace Tests\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * Unit tests for HtmlSubmit class
 * 
 * Tests the submit button HTML generation with proper attributes
 * 
 * @author Kevin Fraser
 * @since 20250119
 */
class HtmlSubmitTest extends TestCase
{
    /**
     * @test
     * Test that HtmlSubmit can be instantiated
     */
    public function it_can_be_instantiated(): void
    {
        $label = new HtmlString('Submit');
        $submit = new HtmlSubmit($label);
        
        $this->assertInstanceOf(HtmlSubmit::class, $submit);
    }
    
    /**
     * @test
     * Test that HtmlSubmit generates correct HTML tag
     */
    public function it_generates_input_submit_tag(): void
    {
        $label = new HtmlString('Submit');
        $submit = new HtmlSubmit($label);
        
        $html = $submit->getHtml();
        
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('value="Submit"', $html);
    }
    
    /**
     * @test
     * Test that HtmlSubmit can set name attribute
     */
    public function it_can_set_name_attribute(): void
    {
        $label = new HtmlString('Save');
        $submit = new HtmlSubmit($label);
        $submit->setName('save_button');
        
        $html = $submit->getHtml();
        
        $this->assertStringContainsString('name="save_button"', $html);
    }
    
    /**
     * @test
     * Test that HtmlSubmit can set id attribute
     */
    public function it_can_set_id_attribute(): void
    {
        $label = new HtmlString('Cancel');
        $submit = new HtmlSubmit($label);
        $submit->setId('cancel-btn');
        
        $html = $submit->getHtml();
        
        $this->assertStringContainsString('id="cancel-btn"', $html);
    }
    
    /**
     * @test
     * Test that HtmlSubmit can set class attribute
     */
    public function it_can_set_class_attribute(): void
    {
        $label = new HtmlString('Delete');
        $submit = new HtmlSubmit($label);
        $submit->setClass('btn btn-danger');
        
        $html = $submit->getHtml();
        
        $this->assertStringContainsString('class="btn btn-danger"', $html);
    }
    
    /**
     * @test
     * Test that HtmlSubmit escapes special characters in label
     */
    public function it_escapes_special_characters_in_label(): void
    {
        $label = new HtmlString('Save & Continue');
        $submit = new HtmlSubmit($label);
        
        $html = $submit->getHtml();
        
        $this->assertStringContainsString('Save &amp; Continue', $html);
    }
    
    /**
     * @test
     * Test that HtmlSubmit is self-closing (empty element)
     */
    public function it_is_self_closing_element(): void
    {
        $label = new HtmlString('Submit');
        $submit = new HtmlSubmit($label);
        
        $html = $submit->getHtml();
        
        // Should end with /> or just > but no </input>
        $this->assertStringNotContainsString('</input>', $html);
    }
}
