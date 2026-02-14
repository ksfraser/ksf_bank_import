<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionServiceTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionServiceTest.
 */
namespace Tests\Unit;

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
            ->method('findByStatus')
            ->with('pending')
            ->willReturn([]);

        $result = $this->service->getPendingTransactions();
        $this->assertIsArray($result);
    }

    public function testProcessTransactionThrowsExceptionWhenNotFound()
    {
        $this->repository->method('findById')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->processTransaction(1, 'C');
    }

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
