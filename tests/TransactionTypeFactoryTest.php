<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
use Ksfraser\FaBankImport\Types\CreditTransaction;
use Ksfraser\FaBankImport\Types\DebitTransaction;
use Ksfraser\FaBankImport\Types\BankTransferTransaction;

class TransactionTypeFactoryTest extends TestCase
{
    private $factory;
    private $sampleData;

    protected function setUp(): void
    {
        $this->factory = new TransactionTypeFactory();
        $this->sampleData = [
            'id' => 1,
            'amount' => 100.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test transaction'
        ];
    }

    public function testCreatesCreditTransaction()
    {
        $transaction = $this->factory->createTransactionType('C', $this->sampleData);
        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals('C', $transaction->getTransactionType());
    }

    public function testCreatesDebitTransaction()
    {
        $transaction = $this->factory->createTransactionType('D', $this->sampleData);
        $this->assertInstanceOf(DebitTransaction::class, $transaction);
        $this->assertEquals('D', $transaction->getTransactionType());
    }

    public function testCreatesBankTransferTransaction()
    {
        $transaction = $this->factory->createTransactionType('B', $this->sampleData);
        $this->assertInstanceOf(BankTransferTransaction::class, $transaction);
        $this->assertEquals('B', $transaction->getTransactionType());
    }

    public function testThrowsExceptionForInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createTransactionType('X', $this->sampleData);
    }
}
