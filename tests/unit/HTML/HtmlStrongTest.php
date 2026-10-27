<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlStrong;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlStrongTest extends TestCase
{
    public function testConstructorWrapsDataInStrongTag(): void
    {
        $element = new HtmlStrong(new HtmlString('weight'));

        $this->assertSame('<strong>weight</strong>', $element->getHtml());
    }
}
