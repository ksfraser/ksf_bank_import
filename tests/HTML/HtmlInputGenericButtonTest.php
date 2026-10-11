<?php

namespace Tests\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlInputGenericButton;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * Test suite for HtmlInputGenericButton class
 *
 * Tests the generic button input element (<input type="button">)
 * Used for client-side JavaScript interactions without form submission
 *
 * @package Tests\HTML
 */
class HtmlInputGenericButtonTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $label = new HtmlString("Click Me");
        $button = new HtmlInputGenericButton($label);
        
        $this->assertInstanceOf(HtmlInputGenericButton::class, $button);
    }

    /**
     * @test
     */
    public function it_generates_input_button_tag()
    {
        $label = new HtmlString("Click Here");
        $button = new HtmlInputGenericButton($label);
        $html = $button->getHtml();
        
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('value="Click Here"', $html);
        $this->assertStringContainsString('/>', $html);
    }

    /**
     * @test
     */
    public function it_can_set_name_attribute()
    {
        $label = new HtmlString("Action");
        $button = new HtmlInputGenericButton($label);
        $button->setName("action_button");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('name="action_button"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_id_attribute()
    {
        $label = new HtmlString("Button");
        $button = new HtmlInputGenericButton($label);
        $button->setId("my_btn");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('id="my_btn"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_class_attribute()
    {
        $label = new HtmlString("Button");
        $button = new HtmlInputGenericButton($label);
        $button->setClass("btn btn-info");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('class="btn btn-info"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_onclick_attribute()
    {
        $label = new HtmlString("Alert");
        $button = new HtmlInputGenericButton($label);
        $button->setOnclick("alert('Hello World!')");
        $html = $button->getHtml();
        
        $this->assertStringContainsString('onclick="alert(\'Hello World!\')"', $html);
    }

    /**
     * @test
     */
    public function it_escapes_special_characters_in_label()
    {
        $label = new HtmlString("<script>alert('xss')</script>");
        $button = new HtmlInputGenericButton($label);
        $html = $button->getHtml();
        
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    /**
     * @test
     */
    public function it_is_self_closing_element()
    {
        $label = new HtmlString("Button");
        $button = new HtmlInputGenericButton($label);
        $html = $button->getHtml();
        
        $this->assertStringContainsString('/>', $html);
        $this->assertStringNotContainsString('</input>', $html);
    }

    /**
     * @test
     */
    public function it_can_be_disabled()
    {
        $label = new HtmlString("Button");
        $button = new HtmlInputGenericButton($label);
        $button->setDisabled();
        $html = $button->getHtml();
        
        $this->assertStringContainsString('disabled', $html);
    }

    /**
     * @test
     */
    public function it_supports_method_chaining()
    {
        $label = new HtmlString("Do Something");
        $button = new HtmlInputGenericButton($label);
        
        $result = $button->setName("action")
                         ->setId("action_btn")
                         ->setClass("btn btn-primary")
                         ->setOnclick("doSomething()");
        
        $this->assertSame($button, $result);
        
        $html = $button->getHtml();
        $this->assertStringContainsString('name="action"', $html);
        $this->assertStringContainsString('id="action_btn"', $html);
        $this->assertStringContainsString('class="btn btn-primary"', $html);
        $this->assertStringContainsString('onclick="doSomething()"', $html);
    }
}
