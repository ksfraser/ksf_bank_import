<?php

namespace Ksfraser\FaBankImport\Interfaces;

interface TransactionRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findAll(): array;
    public function findByStatus(string $status): array;
    public function save(array $transaction): bool;
    public function update(int $id, array $data): bool;
}