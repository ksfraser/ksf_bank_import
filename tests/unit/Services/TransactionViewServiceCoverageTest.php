<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionViewService;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;

/**
 * Behavioural tests for the transaction view service.
 */
final class TransactionViewServiceCoverageTest extends TestCase
{
    /** @var BankTransactionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transaction;

    /** @var TransactionViewInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $view;

    /** @var TransactionViewService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transaction = $this->createMock(BankTransactionInterface::class);
        $this->view = $this->createMock(TransactionViewInterface::class);
        $this->service = new TransactionViewService($this->transaction, $this->view);
    }

    public function testDisplayReturnsTheRenderedView(): void
    {
        $this->transaction->method('getTransactionType')->willReturn('C');
        $this->transaction->method('getId')->willReturn(11);

        $this->view->method('render')->willReturn('<div>transaction</div>');

        $this->assertSame('<div>transaction</div>', $this->service->display());
    }

    public function testDisplayAddsTheCustomerButtonForACreditTransaction(): void
    {
        $this->transaction->method('getTransactionType')->willReturn('C');
        $this->transaction->method('getId')->willReturn(11);

        $buttons = [];
        $this->view->method('addButton')->willReturnCallback(
            function ($type, $params) use (&$buttons): void {
                $buttons[] = [$type, $params];
            }
        );

        $this->service->display();

        $this->assertSame(
            [
                ['AddCustomer', ['id' => 11]],
                ['ProcessTransaction', ['id' => 11]],
            ],
            $buttons
        );
    }

    public function testDisplayAddsTheVendorButtonForADebitTransaction(): void
    {
        $this->transaction->method('getTransactionType')->willReturn('D');
        $this->transaction->method('getId')->willReturn(12);

        $buttons = [];
        $this->view->method('addButton')->willReturnCallback(
            function ($type, $params) use (&$buttons): void {
                $buttons[] = [$type, $params];
            }
        );

        $this->service->display();

        $this->assertSame(
            [
                ['AddVendor', ['id' => 12]],
                ['ProcessTransaction', ['id' => 12]],
            ],
            $buttons
        );
    }

    public function testDisplayOnlyAddsTheProcessButtonForOtherTransactionTypes(): void
    {
        $this->transaction->method('getTransactionType')->willReturn('B');
        $this->transaction->method('getId')->willReturn(13);

        $buttons = [];
        $this->view->method('addButton')->willReturnCallback(
            function ($type, $params) use (&$buttons): void {
                $buttons[] = [$type, $params];
            }
        );

        $this->service->display();

        $this->assertSame([['ProcessTransaction', ['id' => 13]]], $buttons);
    }
}
