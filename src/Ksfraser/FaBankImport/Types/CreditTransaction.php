<?php

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

/**
 * @BABOK FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
 */
class CreditTransaction extends AbstractTransaction
{
    /**
 * @BABOK FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionType(): string
    {
        return 'C';
    }

    /**
 * @BABOK FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function processTransaction(): void
    {
        // Specific credit transaction processing logic
    }
}
