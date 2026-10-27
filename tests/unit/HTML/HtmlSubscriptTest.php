<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlSubscript;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlSubscriptTest extends TestCase
{
    public function testConstructorWrapsDataInSubscriptTag(): void
    {
        $element = new HtmlSubscript(new HtmlString('H2O'));

        $this->assertSame('<sub>H2O</sub>', $element->getHtml());
    }
}
