<?php
/**
 * Unit tests for TransactionService (refactor-psr contract).
 *
 * Aligned with TransactionRepositoryInterface: findByFilters + typed
 * update(array $ids, int $status, ...).
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

class TransactionServiceTest extends TestCase
{
    private $service;
    private $repository;
    private $factory;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TransactionRepositoryInterface::class);
        $this->factory = $this->createMock(TransactionTypeFactory::class);
        $this->service = new TransactionService($this->repository, $this->factory);
    }

    public function testGetPendingTransactionsCallsRepository()
    {
        $this->repository->expects($this->once())
            ->method('findByFilters')
            ->with(['status' => 'pending'])
            ->willReturn([['id' => 1]]);

        $result = $this->service->getPendingTransactions();
        $this->assertCount(1, $result);
    }

    public function testProcessTransactionThrowsWhenNotFound()
    {
        $this->repository->method('findById')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->processTransaction(999, 'SP');
    }

    public function testToggleTransactionTypeIsCommandOwned()
    {
        // Direction flips belong to ToggleDebitCreditCommand; the service
        // must not perform partial repository updates for it.
        $this->expectException(\LogicException::class);
        $this->service->toggleTransactionType(1);
    }
}
