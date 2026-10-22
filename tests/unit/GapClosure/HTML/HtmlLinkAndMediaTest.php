<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML;

use Ksfraser\HTML\Elements\HtmlA;
use Ksfraser\HTML\Elements\HtmlImage;
use Ksfraser\HTML\Elements\HtmlLink;
use Ksfraser\HTML\Elements\HtmlString;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the link and image elements.
 *
 * @see \Ksfraser\HTML\Elements\HtmlA
 * @see \Ksfraser\HTML\Elements\HtmlLink
 * @see \Ksfraser\HTML\Elements\HtmlImage
 */
final class HtmlLinkAndMediaTest extends TestCase
{
    /**
     * @return void
     */
    public function testLinkAcceptsLinkText(): void
    {
        $link = new HtmlLink(new HtmlString('original'));
        $link->addHref('/target', 'replacement');

        $this->assertStringContainsString('href="/target"', $link->getHtml());
    }

    /**
     * @return void
     */
    public function testLinkAcceptsTheParentTarget(): void
    {
        $link = new HtmlLink(new HtmlString('text'));
        $link->setTarget('_parent');

        $this->assertStringContainsString('target="_parent"', $link->getHtml());
    }

    /**
     * @return void
     */
    public function testLinkAcceptsTheTopTarget(): void
    {
        $link = new HtmlLink(new HtmlString('text'));
        $link->setTarget('_top');

        $this->assertStringContainsString('target="_top"', $link->getHtml());
    }

    /**
     * @return void
     */
    public function testLinkRejectsAnUnknownTarget(): void
    {
        $link = new HtmlLink(new HtmlString('text'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Target type not recognized: sideways');

        $link->setTarget('sideways');
    }

    /**
     * @return void
     */
    public function testAnchorRefusesANestedLink(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot nest links inside links');

        new HtmlA('/target', new HtmlLink(new HtmlString('inner')));
    }

    /**
     * @return void
     */
    public function testAnchorRefusesANestedAnchor(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot nest links inside links');

        new HtmlA('/target', new HtmlA('/inner', 'inner'));
    }

    /**
     * @return void
     */
    public function testImageRendersItsAttributes(): void
    {
        $image = new HtmlImage(new HtmlString(''));
        $image->setSrc('/logo.png');
        $image->setAlt('The logo');
        $image->setHeight('20');
        $image->setWidth('30');

        $html = $image->getHtml();

        $this->assertStringContainsString('src="/logo.png"', $html);
        $this->assertStringContainsString('alt="The logo"', $html);
        $this->assertStringContainsString('height="20"', $html);
        $this->assertStringContainsString('width="30"', $html);
    }
}
