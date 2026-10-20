<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK CLASS ROLLUP: TransactionViewInterface — 3 methods; FR-015 (3)
 * @BABOK RTM: FR-015 / ARCH-001; file: Interfaces/TransactionViewInterface.php; coverage: docs/TestCases/ (file rollup)
 */
interface TransactionViewInterface
{
    /** @BABOK FR-015 / ARCH-001 — method: render */
    public function render(): string;
    /** @BABOK FR-015 / ARCH-001 — method: renderActions */
    public function renderActions(): string;
    /** @BABOK FR-015 / ARCH-001 — method: addButton */
    public function addButton(string $type, array $params): void;
}
