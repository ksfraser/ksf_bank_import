<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlImage;
use Ksfraser\HTML\Elements\HtmlString;

class HtmlImageTest extends TestCase
{
    public function testSetSizeComposesWidthAndHeightStyles(): void
    {
        $image = new HtmlImage(new HtmlString('logo'));
        $image->setSrc('logo.png');
        $image->setSize(100, 200);

        $this->assertSame(
            '<img src="logo.png" style="width:100px;height:200px;">logo</img>',
            $image->getHtml()
        );
    }

    public function testSetSizeRendersStyleAttributeWithIntegerPixelValues(): void
    {
        $image = new HtmlImage(new HtmlString(''));
        $image->setSrc('pixel.gif');
        $image->setSize(2, 3);

        $this->assertStringContainsString(
            'style="width:2px;height:3px;"',
            $image->getHtml()
        );
    }
}
