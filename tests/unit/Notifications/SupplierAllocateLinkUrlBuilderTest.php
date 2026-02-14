<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use Ksfraser\FA\Notifications\SupplierAllocateLinkUrlBuilder;
use PHPUnit\Framework\TestCase;

final class SupplierAllocateLinkUrlBuilderTest extends TestCase
{
    public function testBuildMessageUsesExpectedFormat(): void
    {
        $message = SupplierAllocateLinkUrlBuilder::buildMessage(ST_SUPPAYMENT, 456, 88);

        $this->assertSame('Allocate Payment ' . ST_SUPPAYMENT . '::456 Supplier:88', $message);
    }

    public function testAppRelativePathBuildsExpectedSupplierAllocateUrl(): void
    {
        $path = SupplierAllocateLinkUrlBuilder::appRelativePath(ST_SUPPAYMENT, 456, 88);

        $this->assertSame(
            'purchasing/allocations/supplier_allocate.php?trans_type=' . ST_SUPPAYMENT . '&trans_no=456&supplier_id=88',
            $path
        );
    }
}
