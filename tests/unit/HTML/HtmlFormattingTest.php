<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlFormatting;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlAttributeList;

class HtmlFormattingTest extends TestCase
{
    public function testConstructorResetsTag(): void
    {
        $element = new HtmlFormatting(new HtmlString('some data'));

        $this->assertSame('<>some data</>', $element->getHtml());
    }

    public function testAddAttributeThrows(): void
    {
        $element = new HtmlFormatting(new HtmlString('some data'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Does HTML Formatting allow Attributes?');

        $element->addAttribute(new HtmlAttribute('class', 'test-class'));
    }

    public function testSetAttributeListThrows(): void
    {
        $element = new HtmlFormatting(new HtmlString('some data'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Does HTML Formatting allow Attributes?');

        $element->setAttributeList(
            new HtmlAttributeList(new HtmlAttribute('', ''))
        );
    }

    public function testNewAttributeListThrows(): void
    {
        $element = new HtmlFormatting(new HtmlString('some data'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Does HTML Formatting allow Attributes?');

        $element->newAttributeList();
    }
}
