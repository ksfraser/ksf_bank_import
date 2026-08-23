<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;

/**
 * Application service orchestrating transaction processing.
 *
 * Aligned (refactor-psr) with the repository's actual contract:
 * filter-based reads and typed update(array $ids, int $status, ...) calls.
 *
 * @since 20260822
 */
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

    /**
     * Fetch transactions still pending processing.
     *
     * @return array
     */
    public function getPendingTransactions(): array
    {
        return $this->repository->findByFilters(['status' => 'pending']);
    }

    /**
     * Process a transaction through its type handler and mark it processed.
     *
     * @param int    $id   Transaction id.
     * @param string $type Transaction type code.
     * @return bool True when the update affected rows.
     * @throws \InvalidArgumentException When transaction not found.
     */
    public function processTransaction(int $id, string $type): bool
    {
        $transaction = $this->repository->findById($id);

        if (!$transaction) {
            throw new \InvalidArgumentException("Transaction not found: $id");
        }

        $transactionObj = $this->factory->createTransactionType($type, $transaction);
        $transactionObj->processTransaction();

        // Status 'processed' is FA status 1 in bi_transactions.
        $this->repository->update([$id], 1, 0, 0);
        return true;
    }

    /**
     * Toggle the debit/credit direction of a transaction.
     *
     * Delegates to the ToggleDebitCredit command path in production; here we
     * perform the direct repository update for the legacy contract.
     *
     * @param int $id Transaction id.
     * @return bool True on success.
     * @throws \InvalidArgumentException When transaction not found.
     */
    public function toggleTransactionType(int $id): bool
    {
        $transaction = $this->repository->findById($id);

        if (!$transaction) {
            throw new \InvalidArgumentException("Transaction not found: $id");
        }

        // Direction flips are handled by the dedicated command/handler which
        // owns the FA-side journal updates; repository alone cannot flip DC.
        throw new \LogicException(
            'toggleTransactionType must be performed via ToggleDebitCreditCommand.'
        );
    }
}
