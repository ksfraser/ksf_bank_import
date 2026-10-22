<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure;

use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;
use Ksfraser\FaBankImport\Models\FaMatchingJEsFinder;
use Ksfraser\FaBankImport\Types\BankTransferTransaction;
use Ksfraser\FaBankImport\Types\DebitTransaction;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the small helpers that only carry a little logic.
 */
final class SmallHelpersTest extends TestCase
{
    /**
     * @return void
     */
    public function testABankTransferTransactionIsProcessed(): void
    {
        $transaction = new BankTransferTransaction(['id' => 1, 'amount' => 10.0]);

        $transaction->processTransaction();

        $this->assertSame('B', $transaction->getTransactionType());
    }

    /**
     * @return void
     */
    public function testADebitTransactionIsProcessed(): void
    {
        $transaction = new DebitTransaction(['id' => 1, 'amount' => 10.0]);

        $transaction->processTransaction();

        $this->assertSame('D', $transaction->getTransactionType());
    }

    /**
     * @return void
     */
    public function testACreatedTransactionIsMarkedInTheUpdate(): void
    {
        $builder = new TransactionQueryBuilder('0_bi_transactions', '0_bi_statements');

        $query = $builder->buildUpdateTransactionsQuery([1, 2], 1, 42, 27, false, true);

        $this->assertStringContainsString('created = 1', $query['sql']);
        $this->assertStringNotContainsString('matched = 1', $query['sql']);
    }

    /**
     * @return void
     */
    public function testTheMatchingFinderIsEmptyWithoutTheLegacyTree(): void
    {
        $finder = new FaMatchingJEsFinder();

        $this->assertFalse(FaMatchingJEsFinder::legacyAvailable());
        $this->assertSame([], $finder->findFor((object) []));
    }
}
