<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use Ksfraser\FA\Notifications\AttachmentLinkUrlBuilder;
use PHPUnit\Framework\TestCase;

final class AttachmentLinkUrlBuilderTest extends TestCase
{
    public function testBuildMessageUsesExpectedFormat(): void
    {
        $message = AttachmentLinkUrlBuilder::buildMessage(ST_BANKPAYMENT, 1234);

        $this->assertSame('Attach Document ' . ST_BANKPAYMENT . '::1234', $message);
    }

    public function testAppRelativePathBuildsExpectedAttachmentUrl(): void
    {
        $path = AttachmentLinkUrlBuilder::appRelativePath(ST_BANKPAYMENT, 1234);

        $this->assertSame('admin/attachments.php?filterType=' . ST_BANKPAYMENT . '&trans_no=1234', $path);
    }
}
