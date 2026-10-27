<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlInserted;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlInsertedTest extends TestCase
{
    public function testConstructorWrapsDataInInsertedTag(): void
    {
        $element = new HtmlInserted(new HtmlString('added'));

        $this->assertSame('<ins>added</ins>', $element->getHtml());
    }
}
