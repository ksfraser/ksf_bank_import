<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlSmall;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlSmallTest extends TestCase
{
    public function testConstructorWrapsDataInSmallTag(): void
    {
        $element = new HtmlSmall(new HtmlString('fine print'));

        $this->assertSame('<small>fine print</small>', $element->getHtml());
    }
}
