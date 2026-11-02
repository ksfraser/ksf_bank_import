<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

/** @BABOK CLASS ROLLUP: TransactionServiceTest — 4 methods; NFR-016 (4); FR-018 (4) */
class TransactionServiceTest extends TestCase
{
    private $service;
    private $repository;
    private $factory;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(TransactionRepositoryInterface::class);
        $this->factory = $this->createMock(TransactionTypeFactory::class);
        $this->service = new TransactionService($this->repository, $this->factory);
    }

    /** @BABOK NFR-016 / FR-018 — method: testGetPendingTransactionsCallsRepository */
    public function testGetPendingTransactionsCallsRepository()
    {
        $this->repository->expects($this->once())
            ->method('findByStatus')
            ->with('pending')
            ->willReturn([]);

        $result = $this->service->getPendingTransactions();
        $this->assertIsArray($result);
    }

    /** @BABOK NFR-016 / FR-018 — method: testProcessTransactionThrowsExceptionWhenNotFound */
    public function testProcessTransactionThrowsExceptionWhenNotFound()
    {
        $this->repository->method('findById')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->processTransaction(1, 'C');
    }

    /** @BABOK NFR-016 / FR-018 — method: testToggleTransactionTypeSuccessfully */
    public function testToggleTransactionTypeSuccessfully()
    {
        $transaction = [
            'id' => 1,
            'transactionDC' => 'C'
        ];

        $this->repository->method('findById')
            ->willReturn($transaction);

        $this->repository->expects($this->once())
            ->method('update')
            ->with(1, ['transactionDC' => 'D'])
            ->willReturn(true);

        $result = $this->service->toggleTransactionType(1);
        $this->assertTrue($result);
    }
}
