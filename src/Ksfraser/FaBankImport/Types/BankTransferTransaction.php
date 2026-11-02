<?php
// @BABOK RTM: FR-004 / DR-001 (domain layer)
/** @BABOK RTM: BR-003 / ARCH-003 / FR-006*/

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

/**
 * @BABOK CLASS ROLLUP: BankTransferTransaction — 2 methods; FR-004 (2); DR-001 (2)
 * @BABOK RTM: FR-004 / DR-001; file: Types/BankTransferTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
class BankTransferTransaction extends AbstractTransaction
{
    /** @BABOK FR-004 / DR-001 — method: getTransactionType */
    public function getTransactionType(): string
    {
        return 'B';
    }

    /** @BABOK FR-004 / DR-001 — method: processTransaction */
    public function processTransaction(): void
    {
        // Specific bank transfer processing logic
    }
}
