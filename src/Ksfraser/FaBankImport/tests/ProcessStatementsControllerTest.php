<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ProcessStatementsControllerTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ProcessStatementsControllerTest.
 */
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

    /**
     * Test that the index method renders the transaction list.
     */
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

    /**
     * Test that unsetTransaction calls the model method.
     */
    public function testUnsetTransactionCallsModelMethod()
    {
        $_POST['UnsetTrans'] = [1, 2];

        $this->transactionModelMock->expects($this->exactly(2))
            ->method('unsetTransaction')
            ->withConsecutive([1], [2]);

        $this->controller->unsetTransaction();
    }

    /**
     * Test that addCustomer calls the model method.
     */
    public function testAddCustomerCallsModelMethod()
    {
        $_POST['AddCustomer'] = [1, 2];

        $this->transactionModelMock->expects($this->exactly(2))
            ->method('addCustomerFromTransaction')
            ->withConsecutive([1], [2]);

        $this->controller->addCustomer();
    }

    /**
     * Test that addVendor calls the model method.
     */
    public function testAddVendorCallsModelMethod()
    {
        $_POST['AddVendor'] = [1, 2];

        $this->transactionModelMock->expects($this->exactly(2))
            ->method('addVendorFromTransaction')
            ->withConsecutive([1], [2]);

        $this->controller->addVendor();
    }

    /**
     * Test that toggleTransaction calls the model method.
     */
    public function testToggleTransactionCallsModelMethod()
    {
        $_POST['ToggleTransaction'] = [1, 2];

        $this->transactionModelMock->expects($this->exactly(2))
            ->method('toggleDebitCredit')
            ->withConsecutive([1], [2]);

        $this->controller->toggleTransaction();
    }

    /**
     * Test processing a transaction with a valid partner type.
     */
    public function testProcessTransactionWithValidPartnerType()
    {
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'SP'];

        $this->transactionModelMock->expects($this->once())
            ->method('processSupplierTransaction')
            ->with(1);

        $this->controller->processTransaction();
    }

    /**
     * Test processing a transaction with an invalid partner type.
     */
    public function testProcessTransactionWithInvalidPartnerType()
    {
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'INVALID'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid partner type: INVALID');

        $this->controller->processTransaction();
    }
}
