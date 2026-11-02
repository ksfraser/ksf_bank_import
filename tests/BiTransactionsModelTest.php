<?php

use PHPUnit\Framework\TestCase;

class BiTransactionsModelTest extends TestCase
{
    protected $biTransactionsModel;

    protected function setUp(): void
    {
        $this->biTransactionsModel = new bi_transactions_model();
    }

    public function testInsertTransaction()
    {
        // Set up the necessary properties
        $this->biTransactionsModel->smt_id = 1;
        $this->biTransactionsModel->valueTimestamp = '2025-04-02';
        $this->biTransactionsModel->entryTimestamp = '2025-04-02';
        $this->biTransactionsModel->account = '123456';
        $this->biTransactionsModel->accountName = 'Test Account';
        $this->biTransactionsModel->transactionType = 'DEP';
        $this->biTransactionsModel->transactionCode = 'TX123';
        $this->biTransactionsModel->transactionCodeDesc = 'Transaction Description';
        $this->biTransactionsModel->transactionDC = 'D';
        $this->biTransactionsModel->transactionAmount = 100.00;
        $this->biTransactionsModel->transactionTitle = 'Test Transaction';
        $this->biTransactionsModel->status = 0;
        $this->biTransactionsModel->merchant = 'Test Merchant';
        $this->biTransactionsModel->category = 'Test Category';
        $this->biTransactionsModel->sic = '1234';
        $this->biTransactionsModel->memo = 'Test Memo';
        $this->biTransactionsModel->checknumber = 12345;

        // Call the insert_transaction method
        $this->biTransactionsModel->insert_transaction();

        // Verify that the transaction was inserted correctly
        // This is an example assertion, adjust it according to your actual database interaction
        $this->assertTrue($this->biTransactionsModel->trans_exists());
    }
}

?>
