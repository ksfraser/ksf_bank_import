<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\TransferMatch;

/**
 * TransferMatchRepositoryInterface - Abstract interface for transfer match persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface TransferMatchRepositoryInterface
{
    /**
     * Find match by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): TransferMatch;

    /**
     * Find matches for transaction (from either side)
     * 
     * @return TransferMatch[]
     */
    public function findByTransactionId(int $transactionId): array;

    /**
     * Find match between two specific transactions
     */
    public function findByTransactionPair(int $sourceId, int $targetId): ?TransferMatch;

    /**
     * Find all unconfirmed matches
     * 
     * @return TransferMatch[]
     */
    public function findUnconfirmed(): array;

    /**
     * Save match (insert or update)
     */
    public function save(TransferMatch $match): void;

    /**
     * Delete match
     */
    public function delete(int $id): void;

    /**
     * Count total matches
     */
    public function count(): int;
}
