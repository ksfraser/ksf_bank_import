<?php
 * Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

class CreditTransaction extends AbstractTransaction
{
    public function getTransactionType(): string
    {
        return 'C';
    }

    public function processTransaction(): void
    {
        // Specific credit transaction processing logic
    }
}
