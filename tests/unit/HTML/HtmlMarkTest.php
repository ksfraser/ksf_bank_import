<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlMark;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlMarkTest extends TestCase
{
    public function testConstructorWrapsDataInMarkTag(): void
    {
        $element = new HtmlMark(new HtmlString('highlighted'));

        $this->assertSame('<mark>highlighted</mark>', $element->getHtml());
    }
}
