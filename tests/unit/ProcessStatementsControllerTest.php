<?php

use PHPUnit\Framework\TestCase;
use Controllers\ProcessStatementsController;
use Ksfraser\FaBankImport\Service\ThirdPartyTransactionActionsInterface;
use Views\TransactionView;

class ProcessStatementsControllerTest extends TestCase
{
    private $controller;
    private $transactionModelMock;
    private $viewMock;

    protected function setUp(): void
    {
        $this->transactionModelMock = $this->createMock(ThirdPartyTransactionActionsInterface::class);
        $this->viewMock = $this->createMock(TransactionView::class);

        $this->controller = new ProcessStatementsController();
        $this->controller->transactionModel = $this->transactionModelMock;
        $this->controller->view = $this->viewMock;
    }

    public function testIndexRendersTransactionList()
    {
        $transactions = [
            ['id' => 1, 'title' => 'Transaction 1', 'amount' => 100],
            ['id' => 2, 'title' => 'Transaction 2', 'amount' => 200],
        ];

        $this->transactionModelMock->method('getAllTransactions')->willReturn($transactions);
        $this->viewMock->expects($this->once())
            ->method('renderTransactionList')
            ->with($transactions);

        $this->controller->index();
    }

    public function testUnsetTransactionCallsModelMethod()
    {
        $_POST['UnsetTrans'] = [1, 2];

        $called = [];
        $this->transactionModelMock->expects($this->exactly(2))
            ->method('unsetTransaction')
            ->willReturnCallback(function($id) use (&$called) {
                $called[] = $id;
            });

        $this->controller->unsetTransaction();

        $this->assertEquals([1, 2], $called);
    }

    public function testAddCustomerCallsModelMethod()
    {
        $_POST['AddCustomer'] = [1, 2];

        $called = [];
        $this->transactionModelMock->expects($this->exactly(2))
            ->method('addCustomerFromTransaction')
            ->willReturnCallback(function($id) use (&$called) {
                $called[] = $id;
            });

        $this->controller->addCustomer();

        $this->assertEquals([1, 2], $called);
    }

    public function testAddVendorCallsModelMethod()
    {
        $_POST['AddVendor'] = [1, 2];

        $called = [];
        $this->transactionModelMock->expects($this->exactly(2))
            ->method('addVendorFromTransaction')
            ->willReturnCallback(function($id) use (&$called) {
                $called[] = $id;
            });

        $this->controller->addVendor();

        $this->assertEquals([1, 2], $called);
    }

    public function testToggleTransactionCallsModelMethod()
    {
        $_POST['ToggleTransaction'] = [1, 2];

        $called = [];
        $this->transactionModelMock->expects($this->exactly(2))
            ->method('toggleDebitCredit')
            ->willReturnCallback(function($id) use (&$called) {
                $called[] = $id;
            });

        $this->controller->toggleTransaction();

        $this->assertEquals([1, 2], $called);
    }

    public function testProcessTransactionWithValidPartnerType()
    {
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'SP'];

        $this->transactionModelMock->expects($this->once())
            ->method('processSupplierTransaction')
            ->with(1);

        $this->controller->processTransaction();
    }

    public function testProcessTransactionWithInvalidPartnerType()
    {
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'INVALID'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid partner type: INVALID');

        $this->controller->processTransaction();
    }
}
