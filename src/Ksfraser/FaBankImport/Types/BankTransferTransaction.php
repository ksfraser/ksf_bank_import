<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BankTransferTransaction [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BankTransferTransaction.
 */
namespace Ksfraser\FaBankImport\Types;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

class BankTransferTransaction extends AbstractTransaction
{
    public function getTransactionType(): string
    {
        return 'B';
    }

    public function processTransaction(): void
    {
        // Specific bank transfer processing logic
    }
}
