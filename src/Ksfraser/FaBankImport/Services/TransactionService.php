<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

/**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 * Transaction Service — core processing

 * Consolidated from: getPendingTransactions (BR-001/FR-001), processTransaction (BR-001/FR-001/BR-003), __construct (ARCH-001/FR-015), toggleTransactionType (BR-001/FR-001)
 * @UML docs/ARCHITECTURE.md / docs/UAT_PLAN.md
 */
class TransactionService
{
    private $repository;
    private $factory;

            /** * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
*/
    public function __construct(
        $repository,
        TransactionTypeFactory $factory
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
    }

            /** * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
*/
    public function getPendingTransactions(): array
    {
        return $this->repository->findByFilters(['status' => 'pending']);
    }

            /** * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
*/
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
    /**
     * Direction flips are owned by ToggleDebitCreditCommand (FR-PROC-011):
     * the service only resolves the row, the command performs the flip.
     *
     * @param int $id Transaction primary key
     * @return bool never — always throws
     * @throws \InvalidArgumentException when the row is unknown
     * @throws \LogicException always, since the flip is the command's job
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
     */
    public function toggleTransactionType(int $id): bool
    {
        $transaction = $this->repository->findById($id);

        if (!$transaction) {
            throw new \InvalidArgumentException("Transaction not found: $id");
        }

        throw new \LogicException('toggleTransactionType must be performed via ToggleDebitCreditCommand.');
    }
}
