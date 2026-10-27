<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlBold;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlBoldTest extends TestCase
{
    public function testConstructorWrapsDataInBoldTag(): void
    {
        $element = new HtmlBold(new HtmlString('important'));

        $this->assertSame('<b>important</b>', $element->getHtml());
    }
}
