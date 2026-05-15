<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * @deprecated Target class is no longer module-owned under src/Ksfraser/HTML
 *             with the tested namespace. Kept only as historical package coverage
 *             outside the active suite.
 */
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