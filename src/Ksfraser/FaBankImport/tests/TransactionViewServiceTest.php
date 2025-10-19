<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionViewService;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;

class TransactionViewServiceTest extends TestCase
{
    private $transaction;
    private $view;
    private $service;

    protected function setUp(): void
    {
        $this->transaction = $this->createMock(BankTransactionInterface::class);
        $this->view = $this->createMock(TransactionViewInterface::class);
        $this->service = new TransactionViewService($this->transaction, $this->view);
    }

    public function testDisplayCallsRenderOnView()
    {
        $this->transaction->method('getTransactionType')->willReturn('C');
        $this->transaction->method('getId')->willReturn(1);
        
        $this->view->expects($this->once())
            ->method('render')
            ->willReturn('<div>Transaction</div>');
            
        $this->view->expects($this->exactly(2))
            ->method('addButton');

        $result = $this->service->display();
        $this->assertEquals('<div>Transaction</div>', $result);
    }

    public function testSetupActionButtonsForCreditTransaction()
    {
        $this->transaction->method('getTransactionType')->willReturn('C');
        $this->transaction->method('getId')->willReturn(1);
        
        $this->view->expects($this->exactly(2))
            ->method('addButton')
            ->withConsecutive(
                ['AddCustomer', ['id' => 1]],
                ['ProcessTransaction', ['id' => 1]]
            );

        $this->service->display();
    }

    public function testSetupActionButtonsForDebitTransaction()
    {
        $this->transaction->method('getTransactionType')->willReturn('D');
        $this->transaction->method('getId')->willReturn(1);
        
        $this->view->expects($this->exactly(2))
            ->method('addButton')
            ->withConsecutive(
                ['AddVendor', ['id' => 1]],
                ['ProcessTransaction', ['id' => 1]]
            );

        $this->service->display();
    }
}
