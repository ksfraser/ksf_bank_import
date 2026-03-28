<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\BankingTransaction;

class BankingTransactionTest extends TestCase
{
    public function test_getTransactionTitle_and_accountName()
    {
        $t = new BankingTransaction();
        $t->transactionTitle1 = 'One';
        $t->transactionTitle2 = 'Two';
        $this->assertStringContainsString('One', $t->getTransactionTitle());

        $t->accountName1 = 'A';
        $t->accountName2 = 'B';
        $this->assertSame('AB', $t->getAccountName());
    }

    public function test_validate()
    {
        $t = new BankingTransaction();
        $this->assertFalse($t->validate());
        $t->transactionAmount = 100;
        $t->transactionType = 'T';
        $t->transactionCode = 'C';
        $t->transactionDC = 'D';
        $this->assertTrue($t->validate());
    }
}
