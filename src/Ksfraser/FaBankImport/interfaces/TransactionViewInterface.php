<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */
interface TransactionViewInterface
{
    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function render(): string;
    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function renderActions(): string;
    /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function addButton(string $type, array $params): void;
}
