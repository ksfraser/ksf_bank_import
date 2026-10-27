<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlEmphasize;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlEmphasizeTest extends TestCase
{
    public function testConstructorWrapsDataInEmphasizedTag(): void
    {
        $element = new HtmlEmphasize(new HtmlString('stress'));

        $this->assertSame('<em>stress</em>', $element->getHtml());
    }
}
