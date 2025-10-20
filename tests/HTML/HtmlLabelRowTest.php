<?php

namespace Tests\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HtmlLabelRow;
use Ksfraser\HTML\HtmlString;

/**
 * Test suite for HtmlLabelRow class
 *
 * Tests a table row with a label cell and a content cell
 * Commonly used in forms: <tr><td class="label">Name:</td><td>John Doe</td></tr>
 *
 * @package Tests\HTML
 */
class HtmlLabelRowTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $label = new HtmlString("Name:");
        $content = new HtmlString("John Doe");
        $row = new HtmlLabelRow($label, $content);
        
        $this->assertInstanceOf(HtmlLabelRow::class, $row);
    }

    /**
     * @test
     */
    public function it_generates_label_row_with_two_cells()
    {
        $label = new HtmlString("Username:");
        $content = new HtmlString("jdoe");
        $row = new HtmlLabelRow($label, $content);
        $html = $row->getHtml();
        
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('</tr>', $html);
        $this->assertStringContainsString('<td', $html);
        $this->assertStringContainsString('</td>', $html);
        $this->assertStringContainsString('Username:', $html);
        $this->assertStringContainsString('jdoe', $html);
    }

    /**
     * @test
     */
    public function it_applies_label_class_to_first_cell()
    {
        $label = new HtmlString("Email:");
        $content = new HtmlString("john@example.com");
        $row = new HtmlLabelRow($label, $content);
        $html = $row->getHtml();
        
        $this->assertStringContainsString('class="label"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_custom_label_width()
    {
        $label = new HtmlString("Description:");
        $content = new HtmlString("Some text");
        $row = new HtmlLabelRow($label, $content);
        $row->setLabelWidth(30);
        $html = $row->getHtml();
        
        $this->assertStringContainsString('width="30%"', $html);
    }

    /**
     * @test
     */
    public function it_uses_default_label_width_of_25()
    {
        $label = new HtmlString("Field:");
        $content = new HtmlString("Value");
        $row = new HtmlLabelRow($label, $content);
        $html = $row->getHtml();
        
        $this->assertStringContainsString('width="25%"', $html);
    }

    /**
     * @test
     */
    public function it_can_set_custom_label_class()
    {
        $label = new HtmlString("Status:");
        $content = new HtmlString("Active");
        $row = new HtmlLabelRow($label, $content);
        $row->setLabelClass("custom-label");
        $html = $row->getHtml();
        
        $this->assertStringContainsString('class="custom-label"', $html);
    }

    /**
     * @test
     */
    public function it_can_add_attributes_to_content_cell()
    {
        $label = new HtmlString("Amount:");
        $content = new HtmlString("$100.00");
        $row = new HtmlLabelRow($label, $content);
        $row->setContentCellAttributes('colspan="2" class="currency"');
        $html = $row->getHtml();
        
        $this->assertStringContainsString('colspan="2"', $html);
        $this->assertStringContainsString('class="currency"', $html);
    }

    /**
     * @test
     */
    public function it_escapes_special_characters_in_label_and_content()
    {
        $label = new HtmlString("<script>alert('xss')</script>");
        $content = new HtmlString("<b>Bold</b>");
        $row = new HtmlLabelRow($label, $content);
        $html = $row->getHtml();
        
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Bold</b>', $html);
    }

    /**
     * @test
     */
    public function it_supports_method_chaining()
    {
        $label = new HtmlString("Name:");
        $content = new HtmlString("Test");
        $row = new HtmlLabelRow($label, $content);
        
        $result = $row->setLabelWidth(20)
                      ->setLabelClass("form-label")
                      ->setContentCellAttributes('class="form-value"');
        
        $this->assertSame($row, $result);
        
        $html = $row->getHtml();
        $this->assertStringContainsString('width="20%"', $html);
        $this->assertStringContainsString('class="form-label"', $html);
        $this->assertStringContainsString('class="form-value"', $html);
    }
}
