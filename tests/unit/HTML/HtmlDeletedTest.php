<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlDeleted;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlDeletedTest extends TestCase
{
    public function testConstructorWrapsDataInDeletedTag(): void
    {
        $element = new HtmlDeleted(new HtmlString('removed'));

        $this->assertSame('<del>removed</del>', $element->getHtml());
    }
}
