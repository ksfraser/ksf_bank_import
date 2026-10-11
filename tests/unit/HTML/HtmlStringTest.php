<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlStringTest extends TestCase
{
    private HtmlString $htmlString;

    protected function setUp(): void
    {
        $this->htmlString = new HtmlString('test content');
    }

    public function testGetHtmlReturnsEscapedString(): void
    {
        $htmlString = new HtmlString('<p>test & content</p>');
        $this->assertEquals('&lt;p&gt;test &amp; content&lt;/p&gt;', $htmlString->getHtml());
    }

    public function testToHtmlOutputsEscapedString(): void
    {
        $htmlString = new HtmlString('<p>test & content</p>');
        ob_start();
        $htmlString->toHtml();
        $output = ob_get_clean();
        $this->assertEquals('&lt;p&gt;test &amp; content&lt;/p&gt;', $output);
    }
}