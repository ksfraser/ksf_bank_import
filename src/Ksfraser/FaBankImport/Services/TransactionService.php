<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

/**
 * Transaction Service — core processing
 * @BABOK BR-001 (Automatic Transaction Matching) — FR-001, FR-002, FR-003
 * @BABOK ARCH-001 (Service-Oriented Design for BR-001) — FR-014, FR-015
 * @UML docs/ARCHITECTURE.md / docs/UAT_PLAN.md
 */
class TransactionService
{
    private $repository;
    private $factory;

    public function __construct(
        $repository,
        TransactionTypeFactory $factory
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
    }

    public function getPendingTransactions(): array
    {
        return $this->repository->findByStatus('pending');
    }

    public function processTransaction(int $id, string $type): bool
    {
        $transaction = $this->repository->findById($id);
        
        if (!$transaction) {
            throw new \InvalidArgumentException("Transaction not found: $id");
        }

        $transactionObj = $this->factory->createTransactionType($type, $transaction);
        $transactionObj->processTransaction();

        return $this->repository->update($id, ['status' => 'processed']);
    }

    public function toggleTransactionType(int $id): bool
    {
        $transaction = $this->repository->findById($id);
        
        if (!$transaction) {
            throw new \InvalidArgumentException("Transaction not found: $id");
        }

        $newType = $transaction['transactionDC'] === 'C' ? 'D' : 'C';
        
        return $this->repository->update($id, ['transactionDC' => $newType]);
    }
}
