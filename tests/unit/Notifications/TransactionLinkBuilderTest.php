<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Notifications\TransactionLinkBuilder;
use Ksfraser\FA\Notifications\TransactionLinkRoutePolicy;

final class TransactionLinkBuilderTest extends TestCase
{
    public function testBuild_UsesExplicitAliasKeys(): void
    {
        $builder = new TransactionLinkBuilder();

        $links = $builder->build([
            'view_entry_link' => '../../gl/view/gl_trans_view.php?type_id=1&trans_no=99',
            'view_payment_link' => '../../gl/view/gl_trans_view.php?type_id=22&trans_no=77',
            'attach_link' => '../../admin/attachments.php?filterType=1&trans_no=99',
            'allocate_link' => '../../sales/allocations/customer_allocate.php?trans_no=77&trans_type=12&debtor_no=10',
        ]);

        $keys = array_column($links, 'key');
        $this->assertContains('view_gl_link', $keys, 'Entry alias should map to entry key family');
        $this->assertContains('view_payment_link', $keys);
        $this->assertContains('attach_document_link', $keys);
        $this->assertContains('allocate_link', $keys);
    }

    public function testBuild_UsesStructuredLinksArray(): void
    {
        $builder = new TransactionLinkBuilder();

        $links = $builder->build([
            'links' => [
                [
                    'key' => 'edit_native',
                    'url' => '../../sales/customer_invoice.php?ModifyInvoice=44',
                    'label' => 'Edit Invoice',
                    'kind' => 'edit',
                ],
                [
                    'url' => '../../sales/view/view_invoice.php?trans_no=44',
                    'label' => 'View Invoice',
                ],
            ],
        ]);

        $this->assertCount(2, $links);
        $this->assertSame('edit_native', $links[0]['key']);
        $this->assertSame('Edit Invoice', $links[0]['label']);
        $this->assertSame('edit', $links[0]['kind']);
    }

    public function testBuild_DoesNotDeriveLinksByDefault(): void
    {
        $builder = new TransactionLinkBuilder();

        $links = $builder->build([
            'trans_no' => 500,
            'trans_type' => 12,
        ]);

        $this->assertSame([], $links, 'Derived links must be opt-in to avoid wrong route assumptions');
    }

    public function testBuild_CanDeriveNativeAndGlLinksWhenEnabled(): void
    {
        $builder = new TransactionLinkBuilder(null, true);

        $links = $builder->build([
            'trans_no' => 500,
            'trans_type' => 12,
        ]);

        $urls = array_column($links, 'url');

        $this->assertContains('../../gl/view/gl_trans_view.php?type_id=12&trans_no=500', $urls);
        $this->assertContains('../../sales/view/view_receipt.php?type_id=12&trans_no=500', $urls);
    }

    public function testBuild_PrioritizesByContextPolicy(): void
    {
        $policy = new TransactionLinkRoutePolicy(
            ['matched_settlement' => ['edit', 'entry', 'receipt']],
            []
        );

        $builder = new TransactionLinkBuilder(null, false, $policy);

        $links = $builder->build([
            'view_gl_link' => '../../gl/view/gl_trans_view.php?type_id=0&trans_no=111',
            'edit_entry_link' => '../../gl/gl_journal.php?ModifyGL=111',
            'view_receipt_link' => '../../sales/view/view_receipt.php?type_id=12&trans_no=111',
        ], null, [
            'context' => 'matched_settlement',
        ]);

        $this->assertNotEmpty($links);
        $this->assertSame('edit', $links[0]['kind']);
        $this->assertSame('entry', $links[1]['kind']);
    }

    public function testBuild_DerivedPrimaryLinkUsesComprehensiveTypeLabels(): void
    {
        $builder = new TransactionLinkBuilder(null, true);

        $cases = [
            ST_JOURNAL => ['View Journal Entry', 'entry'],
            ST_BANKPAYMENT => ['View Bank Payment', 'payment'],
            ST_BANKDEPOSIT => ['View Deposit', 'entry'],
            ST_BANKTRANSFER => ['View Bank Transfer', 'entry'],
            ST_SALESINVOICE => ['View Sales Invoice', 'invoice'],
            ST_CUSTPAYMENT => ['View Customer Payment', 'payment'],
            ST_SUPPAYMENT => ['View Supplier Payment', 'payment'],
            ST_SUPPINVOICE => ['View Supplier Invoice', 'invoice'],
        ];

        foreach ($cases as $transType => $expected) {
            [$expectedLabel, $expectedKind] = $expected;

            $links = $builder->build([
                'trans_no' => 123,
                'trans_type' => $transType,
            ]);

            $primary = null;
            foreach ($links as $link) {
                if (($link['key'] ?? '') === 'derived_view_link') {
                    $primary = $link;
                    break;
                }
            }

            $this->assertNotNull($primary, "Expected derived_view_link for trans type {$transType}");
            $this->assertSame($expectedLabel, $primary['label']);
            $this->assertSame($expectedKind, $primary['kind']);
        }
    }
}
