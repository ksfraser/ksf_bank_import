<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BiStatement;

/**
 * StatementRepositoryInterface - Abstract interface for statement persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface StatementRepositoryInterface
{
    /**
     * Find statement by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): BiStatement;

    /**
     * Find statement by statement ID (OFX identifier)
     * 
     * @throws EntityNotFoundException
     */
    public function findByStatementId(string $statementId): ?BiStatement;

    /**
     * Find all statements for account
     * 
     * @return BiStatement[]
     */
    public function findByAccount(string $account): array;

    /**
     * Save statement (insert or update)
     */
    public function save(BiStatement $statement): void;

    /**
     * Delete statement (cascades to transactions)
     */
    public function delete(int $id): void;

    /**
     * Get total count of statements
     */
    public function count(): int;
}
