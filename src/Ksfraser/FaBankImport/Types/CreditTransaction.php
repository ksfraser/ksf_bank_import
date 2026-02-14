<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :CreditTransaction [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for CreditTransaction.
 */
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
