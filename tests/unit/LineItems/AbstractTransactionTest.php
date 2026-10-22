<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\FaBankImport\Models\AbstractTransaction;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\ConcreteTransaction;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ConcreteTransaction.php';

/**
 * Coverage for the abstract transaction base class.
 */
final class AbstractTransactionTest extends TestCase
{
    /**
     * @return array A complete transaction row.
     */
    private function transactionData(array $overrides = []): array
    {
        return $overrides + [
            'id' => 3,
            'amount' => 12.5,
            'valueTimestamp' => '2026-01-31',
            'memo' => 'A memo',
            'status' => 1,
            'our_account' => '000123',
            'ourBankAccountName' => 'Chequing',
            'ourBankAccountCode' => '1061',
            'otherBankAccount' => '000999',
            'otherBankAccountName' => 'Counterparty',
        ];
    }

    public function testTheBaseClassImplementsTheTransactionContract(): void
    {
        $transaction = new ConcreteTransaction($this->transactionData());

        $this->assertInstanceOf(BankTransactionInterface::class, $transaction);
    }

    public function testTheStoredValuesAreExposed(): void
    {
        $transaction = new ConcreteTransaction($this->transactionData());

        $this->assertSame(3, $transaction->getId());
        $this->assertSame(12.5, $transaction->getAmount());
        $this->assertSame('2026-01-31', $transaction->getDate());
        $this->assertSame('A memo', $transaction->getMemo());
        $this->assertSame(1, $transaction->getStatus());
        $this->assertSame('CONCRETE', $transaction->getTransactionType());
    }

    public function testTheAccountDetailsAreBuiltFromTheRow(): void
    {
        $transaction = new ConcreteTransaction($this->transactionData());

        $this->assertSame(
            [
                'account' => '000123',
                'name' => 'Chequing',
                'code' => '1061',
            ],
            $transaction->getAccountDetails()
        );
    }

    public function testTheOtherPartyDetailsAreBuiltFromTheRow(): void
    {
        $transaction = new ConcreteTransaction($this->transactionData());

        $this->assertSame(
            [
                'account' => '000999',
                'name' => 'Counterparty',
            ],
            $transaction->getOtherPartyDetails()
        );
    }

    public function testMissingValuesFallBackToEmptyDefaults(): void
    {
        $transaction = new ConcreteTransaction([]);

        $this->assertSame(0.0, $transaction->getAmount());
        $this->assertSame('', $transaction->getDate());
        $this->assertSame('', $transaction->getMemo());
        $this->assertSame(0, $transaction->getStatus());
        $this->assertSame(
            ['account' => '', 'name' => '', 'code' => ''],
            $transaction->getAccountDetails()
        );
        $this->assertSame(
            ['account' => '', 'name' => ''],
            $transaction->getOtherPartyDetails()
        );
    }

    public function testAMissingIdBreaksTheTypedAccessor(): void
    {
        $transaction = new ConcreteTransaction([]);

        $this->expectException(\TypeError::class);

        $transaction->getId();
    }
}
