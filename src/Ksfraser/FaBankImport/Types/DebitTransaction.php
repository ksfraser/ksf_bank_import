<?php

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

/**
 * @BABOK FR-PROC-004, FR-PROC-006, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
 */
class DebitTransaction extends AbstractTransaction
{
    /**
 * @BABOK FR-PROC-004, FR-PROC-006, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionType(): string
    {
        return 'D';
    }

    /**
 * @BABOK FR-PROC-004, FR-PROC-006, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function processTransaction(): void
    {
        // Specific debit transaction processing logic
    }
}
