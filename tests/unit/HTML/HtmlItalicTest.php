<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlItalic;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlItalicTest extends TestCase
{
    public function testConstructorWrapsDataInItalicTag(): void
    {
        $element = new HtmlItalic(new HtmlString('italics'));

        $this->assertSame('<i>italics</i>', $element->getHtml());
    }
}
