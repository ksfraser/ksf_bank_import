<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HtmlOption;
use Ksfraser\HTML\HtmlString;

/**
 * HtmlOptionTest
 *
 * Tests for HtmlOption class.
 *
 * @package    Ksfraser\Tests\Unit\HTML
 * @author     Claude AI Assistant
 * @since      20251020
 */
class HtmlOptionTest extends TestCase
{
    public function testConstruction(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $this->assertInstanceOf(HtmlOption::class, $option);
    }

    public function testGetHtmlBasic(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $html = $option->getHtml();

        $this->assertStringContainsString('<option', $html);
        $this->assertStringContainsString('value="value1"', $html);
        $this->assertStringContainsString('Label 1', $html);
        $this->assertStringContainsString('</option>', $html);
    }

    public function testGetHtmlWithSelected(): void
    {
        $option = new HtmlOption('value1', 'Label 1', true);
        $html = $option->getHtml();

        $this->assertStringContainsString('selected', $html);
    }

    public function testGetHtmlWithoutSelected(): void
    {
        $option = new HtmlOption('value1', 'Label 1', false);
        $html = $option->getHtml();

        $this->assertStringNotContainsString('selected', $html);
    }

    public function testSetSelected(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $option->setSelected(true);
        $html = $option->getHtml();

        $this->assertStringContainsString('selected', $html);
    }

    public function testSetSelectedFluentInterface(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $result = $option->setSelected(true);

        $this->assertSame($option, $result);
    }

    public function testGetValue(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $this->assertEquals('value1', $option->getValue());
    }

    public function testGetLabel(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $this->assertEquals('Label 1', $option->getLabel());
    }

    public function testIsSelected(): void
    {
        $option = new HtmlOption('value1', 'Label 1', true);
        $this->assertTrue($option->isSelected());

        $option->setSelected(false);
        $this->assertFalse($option->isSelected());
    }

    public function testLabelIsHtmlEscaped(): void
    {
        $option = new HtmlOption('value1', '<script>alert("XSS")</script>');
        $html = $option->getHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testValueIsHtmlEscaped(): void
    {
        $option = new HtmlOption('"><script>alert("XSS")</script>', 'Label');
        $html = $option->getHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    public function testWithHtmlStringLabel(): void
    {
        $label = new HtmlString('Label with <b>formatting</b>');
        $option = new HtmlOption('value1', $label->getHtml());
        $html = $option->getHtml();

        // HtmlString escapes HTML, then HtmlOption escapes again (double escaping)
        // This is expected behavior - don't pass already-escaped content
        $this->assertStringContainsString('&amp;lt;b&amp;gt;', $html);
    }

    public function testToHtmlOutputsOption(): void
    {
        $option = new HtmlOption('value1', 'Label 1');

        ob_start();
        $option->toHtml();
        $output = ob_get_clean();

        $this->assertStringContainsString('<option', $output);
        $this->assertStringContainsString('value="value1"', $output);
    }

    public function testNumericValue(): void
    {
        $option = new HtmlOption('123', 'Numeric Value');
        $html = $option->getHtml();

        $this->assertStringContainsString('value="123"', $html);
    }

    public function testEmptyValue(): void
    {
        $option = new HtmlOption('', 'Select One...');
        $html = $option->getHtml();

        $this->assertStringContainsString('value=""', $html);
        $this->assertStringContainsString('Select One...', $html);
    }

    public function testZeroValue(): void
    {
        $option = new HtmlOption('0', 'Zero');
        $html = $option->getHtml();

        $this->assertStringContainsString('value="0"', $html);
    }

    public function testWithDisabledAttribute(): void
    {
        $option = new HtmlOption('value1', 'Label 1');
        $option->setAttribute('disabled', 'disabled');
        $html = $option->getHtml();

        $this->assertStringContainsString('disabled', $html);
    }

    public function testCanBeReused(): void
    {
        $option = new HtmlOption('value1', 'Label 1');

        $html1 = $option->getHtml();
        $html2 = $option->getHtml();

        $this->assertEquals($html1, $html2);
    }

    public function testCanToggleSelected(): void
    {
        $option = new HtmlOption('value1', 'Label 1');

        $option->setSelected(true);
        $html1 = $option->getHtml();
        $this->assertStringContainsString('selected', $html1);

        $option->setSelected(false);
        $html2 = $option->getHtml();
        $this->assertStringNotContainsString('selected', $html2);
    }
}
