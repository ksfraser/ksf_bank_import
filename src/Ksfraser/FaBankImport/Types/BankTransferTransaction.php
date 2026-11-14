<?php
// @BABOK RTM: FR-004 / DR-001 (domain layer)
/** @BABOK RTM: BR-003 / ARCH-003 / FR-006*/

namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

/**
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001

 */
class BankTransferTransaction extends AbstractTransaction
{
    /** * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
*/
    public function getTransactionType(): string
    {
        return 'B';
    }

    /** * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
*/
    public function processTransaction(): void
    {
        // Specific bank transfer processing logic
    }
}
