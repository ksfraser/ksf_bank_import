<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

/**
 * @BABOK CLASS ROLLUP: TransactionService — 4 methods; BR-001 (2); FR-001 (2); BR-003 (1)
 * Transaction Service — core processing
 * @BABOK BR-001 (Auto Pair Detection) / BR-003 (Bank Transfer Creation) / ARCH-001 (Service Design) / FR-001 (Pair Detection) / FR-015 (DI Injection)
 * Consolidated from: getPendingTransactions (BR-001/FR-001), processTransaction (BR-001/FR-001/BR-003), __construct (ARCH-001/FR-015), toggleTransactionType (BR-001/FR-001)
 * @UML docs/ARCHITECTURE.md / docs/UAT_PLAN.md
 */
class TransactionService
{
    private $repository;
    private $factory;

            /** @BABOK ARCH-001 / FR-015 (DI injection) — method: __construct */
    public function __construct(
        $repository,
        TransactionTypeFactory $factory
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
    }

            /** @BABOK BR-001 / FR-001 — method: getPendingTransactions */
    public function getPendingTransactions(): array
    {
        return $this->repository->findByStatus('pending');
    }

            /** @BABOK BR-001 / FR-001 / BR-003 — method: processTransaction */
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

            /** @BABOK RTM: BR-001 / ARCH-001 / FR-001 / FR-015 / TC-011-A / TC-011-B / TC-011-C*/
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
