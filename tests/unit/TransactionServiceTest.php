<?php
/** @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
            ->method('findByFilters')
            ->with(['status' => 'pending'])
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

    public function testToggleTransactionTypeIsOwnedByTheToggleDebitCreditCommand()
    {
        $transaction = [
            'id' => 1,
            'transactionDC' => 'C'
        ];

        $this->repository->method('findById')
            ->willReturn($transaction);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('toggleTransactionType must be performed via ToggleDebitCreditCommand.');

        $this->service->toggleTransactionType(1);
    }
}