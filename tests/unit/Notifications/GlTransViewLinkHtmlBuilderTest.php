<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use Ksfraser\FA\Notifications\GlTransViewLinkHtmlBuilder;
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
}
