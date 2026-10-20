<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK CLASS ROLLUP: TransactionRepositoryInterface — 5 methods; FR-015 (5)
 * @BABOK RTM: FR-015 / ARCH-001; file: Interfaces/TransactionRepositoryInterface.php; coverage: docs/TestCases/ (file rollup)
 */
interface TransactionRepositoryInterface
{
    /** @BABOK FR-015 / ARCH-001 — method: findById */
    public function findById(int $id): ?array;
    /** @BABOK FR-015 / ARCH-001 — method: findAll */
    public function findAll(): array;
    /** @BABOK FR-015 / ARCH-001 — method: findByStatus */
    public function findByStatus(string $status): array;
    /** @BABOK FR-015 / ARCH-001 — method: save */
    public function save(array $transaction): bool;
    /** @BABOK FR-015 / ARCH-001 — method: update */
    public function update(int $id, array $data): bool;
}
