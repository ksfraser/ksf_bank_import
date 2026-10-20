<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Controllers\BankImportController;
use Ksfraser\FaBankImport\Models\SquareTransaction;

/** @BABOK CLASS ROLLUP: BankImportControllerTest — 4 methods; NFR-016 (4); FR-018 (4) */
class BankImportControllerTest extends TestCase
{
    private $controller;
    private $transactionModelMock;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        $this->transactionModelMock = $this->createMock(SquareTransaction::class);
        $this->controller = new BankImportController();
        $this->controller->transactionModel = $this->transactionModelMock;
    }

    /** @BABOK NFR-016 / FR-018 — method: testIndexLoadsTransactions */
    public function testIndexLoadsTransactions()
    {
        $this->transactionModelMock->method('getAllTransactions')->willReturn([
            ['id' => 1, 'title' => 'Transaction 1', 'amount' => 100],
            ['id' => 2, 'title' => 'Transaction 2', 'amount' => 200],
        ]);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('<h1>Transactions</h1>', $output);
        $this->assertStringContainsString('Transaction 1', $output);
        $this->assertStringContainsString('Transaction 2', $output);
    }

    /** @BABOK NFR-016 / FR-018 — method: testProcessTransactionWithValidPartnerType */
    public function testProcessTransactionWithValidPartnerType()
    {
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'SP'];

        $this->transactionModelMock->expects($this->once())
            ->method('processSupplierTransaction')
            ->with(1);

        $this->controller->processTransaction();
    }

    /** @BABOK NFR-016 / FR-018 — method: testProcessTransactionWithInvalidPartnerType */
    public function testProcessTransactionWithInvalidPartnerType()
    {
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'INVALID'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid partner type: INVALID');

        $this->controller->processTransaction();
    }
}
