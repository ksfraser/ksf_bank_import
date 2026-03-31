<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BiLineItem;

/**
 * LineItemRepositoryInterface - Abstract interface for transaction line item persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface LineItemRepositoryInterface
{
    /**
     * Find line item by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): BiLineItem;

    /**
     * Find all line items for a transaction
     * 
     * @return BiLineItem[]
     */
    public function findByTransactionId(int $transactionId): array;

    /**
     * Save line item (insert or update)
     */
    public function save(BiLineItem $lineItem): void;

    /**
     * Delete line item
     */
    public function delete(int $id): void;

    /**
     * Find by GL account
     * 
     * @return BiLineItem[]
     */
    public function findByGLAccount(int $glAccount): array;
}
