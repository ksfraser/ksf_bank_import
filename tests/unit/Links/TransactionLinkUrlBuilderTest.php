<?php

declare(strict_types=1);

namespace Tests\\Unit\\Links;

use Ksfraser\\FA\\Links\\TransactionLinkUrlBuilder;
use PHPUnit\Framework\TestCase;

final class TransactionLinkUrlBuilderTest extends TestCase
{
    public function testGlTransViewBuildsCanonicalGlViewUrl(): void
    {
        $url = TransactionLinkUrlBuilder::glTransView(ST_BANKTRANSFER, 321);

        $this->assertSame('../../gl/view/gl_trans_view.php?type_id=' . ST_BANKTRANSFER . '&trans_no=321', $url);
    }

    public function testGlTransViewSupportsExtraQueryParams(): void
    {
        $url = TransactionLinkUrlBuilder::glTransView(ST_SUPPAYMENT, 123, ['filter' => 'active', 'page' => 2]);

        $this->assertSame('../../gl/view/gl_trans_view.php?type_id=' . ST_SUPPAYMENT . '&trans_no=123&filter=active&page=2', $url);
    }

    public function testGlTransViewLinkDataWrapsUrlWithExpectedKey(): void
    {
        $linkData = TransactionLinkUrlBuilder::glTransViewLinkData(ST_SUPPAYMENT, 44);

        $this->assertArrayHasKey('view_gl_link', $linkData);
        $this->assertSame('../../gl/view/gl_trans_view.php?type_id=' . ST_SUPPAYMENT . '&trans_no=44', $linkData['view_gl_link']);
    }
}


