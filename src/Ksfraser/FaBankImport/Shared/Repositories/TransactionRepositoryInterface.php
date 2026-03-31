<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * TransactionRepositoryInterface - Abstract interface for transaction persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface TransactionRepositoryInterface
{
    /**
     * Find transaction by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): BiTransaction;

    /**
     * Find transaction by OFX identifier
     * 
     * @throws EntityNotFoundException
     */
    public function findByFitId(string $fitId): ?BiTransaction;

    /**
     * Find all transactions for a statement
     * 
     * @return BiTransaction[]
     */
    public function findByStatementId(int $statementId): array;

    /**
     * Save transaction (insert or update)
     */
    public function save(BiTransaction $transaction): void;

    /**
     * Delete transaction
     */
    public function delete(int $id): void;

    /**
     * Get total count of transactions
     */
    public function count(): int;
}
