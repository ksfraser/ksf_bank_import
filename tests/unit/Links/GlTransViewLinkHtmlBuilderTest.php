<?php

declare(strict_types=1);

namespace Tests\Unit\Links;

use Ksfraser\FA\Links\GlTransViewLinkHtmlBuilder;
use PHPUnit\Framework\TestCase;

final class GlTransViewLinkHtmlBuilderTest extends TestCase
{
    public function testBuildRendersAnchorWithCanonicalGlUrl(): void
    {
        $html = GlTransViewLinkHtmlBuilder::build(22, 345, 'Open Entry');

        $this->assertStringContainsString('href=', $html);
        $this->assertStringContainsString('../../gl/view/gl_trans_view.php?type_id=22&trans_no=345', $html);
        $this->assertStringContainsString('Open Entry', $html);
    }

    public function testBuildAcceptsOptionalAttributes(): void
    {
        $html = GlTransViewLinkHtmlBuilder::build(
            12,
            900,
            'Receipt',
            [
                'target' => '_self',
                'class' => 'btn btn-link',
                'rel' => 'noopener',
                'trans_no' => '9999',
                'trans_type' => '77',
                'type_id' => '77',
            ]
        );

        $this->assertStringContainsString('href=', $html);
        $this->assertStringContainsString('type_id=12&trans_no=900', $html);
        $this->assertStringContainsString('target="_self"', $html);
        $this->assertStringContainsString('class="btn btn-link"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
        $this->assertStringNotContainsString('trans_no="', $html);
        $this->assertStringNotContainsString('trans_type="', $html);
        $this->assertStringNotContainsString(' type_id="77"', $html);
    }

    public function testBuildSupportsExtraQueryParams(): void
    {
        $html = GlTransViewLinkHtmlBuilder::build(
            5,
            678,
            'View Transaction',
            ['target' => '_blank'],
            ['filter' => 'recent', 'tab' => 'details']
        );

        $this->assertStringContainsString('href=', $html);
        $this->assertStringContainsString('type_id=5&trans_no=678&filter=recent&tab=details', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('View Transaction', $html);
    }
}


