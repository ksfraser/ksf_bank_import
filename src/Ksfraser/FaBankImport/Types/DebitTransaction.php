<?php
 * Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

class DebitTransaction extends AbstractTransaction
{
    public function getTransactionType(): string
    {
        return 'D';
    }

    public function processTransaction(): void
    {
        // Specific debit transaction processing logic
    }
}
