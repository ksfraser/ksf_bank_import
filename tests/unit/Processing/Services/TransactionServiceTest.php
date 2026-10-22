<?php

declare(strict_types=1);

/**
 * Tests for the transaction service.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Services
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Services;

use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * TransactionService coverage.
 */
final class TransactionServiceTest extends ProcessingTestCase
{
    /**
     * The pending transactions are read from the repository.
     *
     * @return void
     */
    public function test_it_reads_the_pending_transactions_from_the_repository(): void
    {
        $repository = $this->createMock(TransactionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByFilters')
            ->with(['status' => 'pending'])
            ->willReturn([['id' => 1]]);

        $service = new TransactionService($repository, new TransactionTypeFactory());

        $this->assertSame([['id' => 1]], $service->getPendingTransactions());
    }

    /**
     * A transaction is processed and marked as processed.
     *
     * @return void
     */
    public function test_it_processes_a_transaction_and_marks_it_as_processed(): void
    {
        $repository = $this->createMock(TransactionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(7)
            ->willReturn(['id' => 7, 'transactionDC' => 'C']);
        $repository->expects($this->once())
            ->method('update')
            ->with([7], 1, 0, 0)
            ->willReturn(1);

        $service = new TransactionService($repository, new TransactionTypeFactory());

        $this->assertTrue($service->processTransaction(7, 'C'));
    }

    /**
     * An unknown transaction is refused.
     *
     * @return void
     */
    public function test_it_refuses_an_unknown_transaction(): void
    {
        $repository = $this->createMock(TransactionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(404)
            ->willReturn(null);

        $service = new TransactionService($repository, new TransactionTypeFactory());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found: 404');

        $service->processTransaction(404, 'C');
    }

    /**
     * The direction of a transaction cannot be flipped by the service.
     *
     * @return void
     */
    public function test_it_cannot_flip_the_direction_of_a_transaction(): void
    {
        $repository = $this->createMock(TransactionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(7)
            ->willReturn(['id' => 7]);

        $service = new TransactionService($repository, new TransactionTypeFactory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('toggleTransactionType must be performed via ToggleDebitCreditCommand.');

        $service->toggleTransactionType(7);
    }

    /**
     * The direction of an unknown transaction is refused as well.
     *
     * @return void
     */
    public function test_it_refuses_to_flip_the_direction_of_an_unknown_transaction(): void
    {
        $repository = $this->createMock(TransactionRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(404)
            ->willReturn(null);

        $service = new TransactionService($repository, new TransactionTypeFactory());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found: 404');

        $service->toggleTransactionType(404);
    }
}
