<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlSuperscript;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlSuperscriptTest extends TestCase
{
    public function testConstructorWrapsDataInSuperscriptTag(): void
    {
        $element = new HtmlSuperscript(new HtmlString('x2'));

        $this->assertSame('<sup>x2</sup>', $element->getHtml());
    }
}
