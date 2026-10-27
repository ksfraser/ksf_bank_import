<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlStyleList;
use Ksfraser\HTML\Elements\HtmlStyle;

class HtmlStyleListTest extends TestCase
{
    public function testConstructorRegistersStyle(): void
    {
        $list = new HtmlStyleList(new HtmlStyle('width', '100px'));

        $this->assertSame('style="width:100px;"', $list->getHtml());
    }

    public function testAddStyleAppendsToTheList(): void
    {
        $list = new HtmlStyleList(new HtmlStyle('width', '100px'));
        $list->addStyle(new HtmlStyle('height', '200px'));

        $this->assertSame('style="width:100px;height:200px;"', $list->getHtml());
    }

    public function testGetHtmlWithEmptyListReturnsEmptyString(): void
    {
        $list = new HtmlStyleList();

        $this->assertSame('', $list->getHtml());
    }

    public function testGetStylesStringReturnsInnerDeclarations(): void
    {
        $list = new HtmlStyleList(new HtmlStyle('width', '100px'));
        $list->addStyle(new HtmlStyle('height', '200px'));

        $this->assertSame('width:100px;height:200px;', $list->getStylesString());
    }

    public function testGetStylesStringWithEmptyListReturnsEmptyString(): void
    {
        $list = new HtmlStyleList();

        $this->assertSame('', $list->getStylesString());
    }

    public function testToHtmlEchoesTheStyledAttribute(): void
    {
        $list = new HtmlStyleList(new HtmlStyle('color', 'red'));

        $this->expectOutputString('style="color:red;"');
        $list->toHtml();
    }
}
