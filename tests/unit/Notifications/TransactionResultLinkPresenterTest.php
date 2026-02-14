<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Notifications\TransactionResultLinkPresenter;

final class TransactionResultLinkPresenterTest extends TestCase
{
    public function testDisplayFromResult_ReturnsEmptyWhenNoLinkData(): void
    {
        $presenter = new TransactionResultLinkPresenter();

        $html = $presenter->displayFromResult(['foo' => 'bar'], [], 'SP');

        $this->assertSame('', $html);
    }

    public function testDisplayFromResult_RendersHtmlModeForArrayResult(): void
    {
        $presenter = new TransactionResultLinkPresenter();

        $html = $presenter->displayFromResult(
            [
                'trans_type' => ST_CUSTPAYMENT,
                'trans_no' => 91,
                'view_receipt_link' => '../../sales/view/view_receipt.php?type_id=12&trans_no=91',
            ],
            [
                'transaction_link_output_mode' => 'html',
            ],
            'CU'
        );

        $this->assertStringContainsString('view_receipt.php', $html);
        $this->assertStringContainsString('View Receipt', $html);
    }

    public function testDisplayFromResult_RendersHtmlModeForObjectResult(): void
    {
        $result = new class {
            public function getData(): array
            {
                return [
                    'view_gl_link' => '../../gl/view/gl_trans_view.php?type_id=1&trans_no=77',
                ];
            }

            public function getTransType(): int
            {
                return ST_BANKPAYMENT;
            }
        };

        $presenter = new TransactionResultLinkPresenter();
        $html = $presenter->displayFromResult($result, ['transaction_link_output_mode' => 'html'], 'QE');

        $this->assertStringContainsString('gl_trans_view.php', $html);
    }
}
