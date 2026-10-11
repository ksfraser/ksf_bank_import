<?php

namespace Tests\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlInputButton;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * Test suite for HtmlInputButton base class
 *
 * Tests the abstract base class for button-type input elements
 * (<input type="submit|reset|button">)
 *
 * @package Tests\HTML
 */
class HtmlInputButtonTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_input_tag_with_type()
    {
        $button = new HtmlInputButton("submit", new HtmlString("Submit"));
        $html = $button->getHtml();
        
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('/>', $html);
    }

    /**
     * @test
     */
    public function it_sets_value_from_label()
    {
        $button = new HtmlInputButton("button", new HtmlString("Click Me"));
        $html = $button->getHtml();
        
        $this->assertStringContainsString('value="Click Me"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_name_attribute()
    {
        $button = new HtmlInputButton("submit", new HtmlString("Submit"));
        $button->setName("submit_button");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('name="submit_button"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_id_attribute()
    {
        $button = new HtmlInputButton("reset", new HtmlString("Reset"));
        $button->setId("reset_btn");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('id="reset_btn"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_class_attribute()
    {
        $button = new HtmlInputButton("button", new HtmlString("Button"));
        $button->setClass("btn btn-primary");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('class="btn btn-primary"', $html);
    }

    /**
     * @test
     */
    public function it_escapes_special_characters_in_label()
    {
        $button = new HtmlInputButton("submit", new HtmlString("<script>alert('xss')</script>"));
        $html = $button->getHtml();
        
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    /**
     * @test
     */
    public function it_is_self_closing_element()
    {
        $button = new HtmlInputButton("button", new HtmlString("Test"));
        $html = $button->getHtml();
        
        $this->assertStringContainsString('/>', $html);
        $this->assertStringNotContainsString('</input>', $html);
    }

    /**
     * @test
     */
    public function it_can_be_disabled()
    {
        $button = new HtmlInputButton("submit", new HtmlString("Submit"));
        $button->setDisabled();
        $html = $button->getHtml();
        
        $this->assertStringContainsString('disabled', $html);
    }
}
