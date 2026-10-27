<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlStyle;

class HtmlStyleTest extends TestCase
{
    public function testGetHtml(): void
    {
        $style = new HtmlStyle('color', 'red');

        $this->assertSame('color:red;', $style->getHtml());
    }

    public function testGetHtmlWithBorderRadius(): void
    {
        $style = new HtmlStyle('border-radius', '4px');

        $this->assertSame('border-radius:4px;', $style->getHtml());
    }
}
