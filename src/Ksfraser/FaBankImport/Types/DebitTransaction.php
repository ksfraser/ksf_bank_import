<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

/**
 * @BABOK CLASS ROLLUP: DebitTransaction — 2 methods; FR-004 (2); DR-001 (2)
 * @BABOK RTM: FR-004 / DR-001; file: Types/DebitTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
class DebitTransaction extends AbstractTransaction
{
    /** @BABOK FR-004 / DR-001 — method: getTransactionType */
    public function getTransactionType(): string
    {
        return 'D';
    }

    /** @BABOK FR-004 / DR-001 — method: processTransaction */
    public function processTransaction(): void
    {
        // Specific debit transaction processing logic
    }
}
