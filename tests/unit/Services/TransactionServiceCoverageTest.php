<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

/**
 * Behavioural tests for the transaction application service.
 */
final class TransactionServiceCoverageTest extends TestCase
{
    /** @var TransactionRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var TransactionTypeFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var TransactionService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TransactionRepositoryInterface::class);
        $this->factory = $this->createMock(TransactionTypeFactory::class);
        $this->service = new TransactionService($this->repository, $this->factory);
    }

    public function testGetPendingTransactionsDelegatesToTheRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('findByFilters')
            ->with(['status' => 'pending'])
            ->willReturn([['id' => 7]]);

        $this->assertSame([['id' => 7]], $this->service->getPendingTransactions());
    }

    public function testProcessTransactionThrowsWhenTheTransactionIsUnknown(): void
    {
        $this->repository->method('findById')->with(99)->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found: 99');

        $this->service->processTransaction(99, 'SP');
    }

    public function testProcessTransactionRunsTheTypeHandlerAndMarksTheRowAsProcessed(): void
    {
        $this->repository->method('findById')->with(5)->willReturn(['id' => 5, 'amount' => 10]);

        $processed = new class {
            /** @var bool */
            public $called = false;

            public function processTransaction(): void
            {
                $this->called = true;
            }
        };

        $this->factory->expects($this->once())
            ->method('createTransactionType')
            ->with('SP', ['id' => 5, 'amount' => 10])
            ->willReturn($processed);

        $this->repository->expects($this->once())
            ->method('update')
            ->with([5], 1, 0, 0);

        $this->assertTrue($this->service->processTransaction(5, 'SP'));
        $this->assertTrue($processed->called);
    }

    public function testToggleTransactionTypeThrowsWhenTheTransactionIsUnknown(): void
    {
        $this->repository->method('findById')->with(3)->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found: 3');

        $this->service->toggleTransactionType(3);
    }

    public function testToggleTransactionTypeIsOwnedByTheToggleDebitCreditCommand(): void
    {
        $this->repository->method('findById')->with(3)->willReturn(['id' => 3]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('toggleTransactionType must be performed via ToggleDebitCreditCommand.');

        $this->service->toggleTransactionType(3);
    }
}
