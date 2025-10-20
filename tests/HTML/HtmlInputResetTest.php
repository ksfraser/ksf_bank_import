<?php

namespace Tests\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HtmlInputReset;
use Ksfraser\HTML\HtmlString;

/**
 * Test suite for HtmlInputReset class
 *
 * Tests the reset button input element (<input type="reset">)
 *
 * @package Tests\HTML
 */
class HtmlInputResetTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $label = new HtmlString("Reset");
        $reset = new HtmlInputReset($label);
        
        $this->assertInstanceOf(HtmlInputReset::class, $reset);
    }

    /**
     * @test
     */
    public function it_generates_input_reset_tag()
    {
        $label = new HtmlString("Reset Form");
        $reset = new HtmlInputReset($label);
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="reset"', $html);
        $this->assertStringContainsString('value="Reset Form"', $html);
        $this->assertStringContainsString('/>', $html);
    }

    /**
     * @test
     */
    public function it_can_set_name_attribute()
    {
        $label = new HtmlString("Clear");
        $reset = new HtmlInputReset($label);
        $reset->setName("reset_button");
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('name="reset_button"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_id_attribute()
    {
        $label = new HtmlString("Reset");
        $reset = new HtmlInputReset($label);
        $reset->setId("reset_btn");
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('id="reset_btn"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_class_attribute()
    {
        $label = new HtmlString("Reset");
        $reset = new HtmlInputReset($label);
        $reset->setClass("btn btn-secondary");
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('class="btn btn-secondary"', $html);
    }

    /**
     * @test
     */
    public function it_escapes_special_characters_in_label()
    {
        $label = new HtmlString("<script>alert('xss')</script>");
        $reset = new HtmlInputReset($label);
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    /**
     * @test
     */
    public function it_is_self_closing_element()
    {
        $label = new HtmlString("Reset");
        $reset = new HtmlInputReset($label);
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('/>', $html);
        $this->assertStringNotContainsString('</input>', $html);
    }

    /**
     * @test
     */
    public function it_can_be_disabled()
    {
        $label = new HtmlString("Reset");
        $reset = new HtmlInputReset($label);
        $reset->setDisabled();
        $html = $reset->getHtml();
        
        $this->assertStringContainsString('disabled', $html);
    }

    /**
     * @test
     */
    public function it_supports_method_chaining()
    {
        $label = new HtmlString("Clear Form");
        $reset = new HtmlInputReset($label);
        
        $result = $reset->setName("clear")
                       ->setId("clear_btn")
                       ->setClass("btn btn-warning");
        
        $this->assertSame($reset, $result);
        
        $html = $reset->getHtml();
        $this->assertStringContainsString('name="clear"', $html);
        $this->assertStringContainsString('id="clear_btn"', $html);
        $this->assertStringContainsString('class="btn btn-warning"', $html);
    }
}
