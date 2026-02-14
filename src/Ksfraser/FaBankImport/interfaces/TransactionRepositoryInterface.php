<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionRepositoryInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionRepositoryInterface.
 */
namespace Ksfraser\FaBankImport\Interfaces;

interface TransactionRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findAll(): array;
    public function findByStatus(string $status): array;
    public function save(array $transaction): bool;
    public function update(int $id, array $data): bool;
}
