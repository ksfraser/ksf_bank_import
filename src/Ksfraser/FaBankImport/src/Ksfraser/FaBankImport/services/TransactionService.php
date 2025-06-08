<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

class TransactionService
{
    private $repository;
    private $factory;

    public function __construct(
        TransactionRepositoryInterface $repository,
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
