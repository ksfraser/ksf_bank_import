<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlInternalCSSList;
use Ksfraser\HTML\Elements\HtmlStyle;

class HtmlInternalCSSListTest extends TestCase
{
    public function testGetHtmlWithEmptyStyleArrayReturnsEmptyString(): void
    {
        $list = new HtmlInternalCSSList();

        $this->assertSame('', $list->getHtml());
    }

    public function testGetHtmlBuildsStyleBlockFromStyleArray(): void
    {
        $list = new HtmlInternalCSSList();
        $list->addStyle(new HtmlStyle('color', 'red'));

        // The class emits the literal '}<backslash>n<backslash>r' characters.
        $this->assertSame('{color:red;}\n\r', $list->getHtml());
    }

    public function testGetHtmlConcatenatesAllStylesInsideTheBlock(): void
    {
        $list = new HtmlInternalCSSList();
        $list->addStyle(new HtmlStyle('color', 'red'));
        $list->addStyle(new HtmlStyle('font-family', 'Arial'));

        $this->assertSame('{color:red;font-family:Arial;}\n\r', $list->getHtml());
    }
}
