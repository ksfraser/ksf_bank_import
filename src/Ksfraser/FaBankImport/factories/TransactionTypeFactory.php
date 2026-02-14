<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionTypeFactory [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionTypeFactory.
 */
namespace Ksfraser\FaBankImport\Factories;

use Ksfraser\FaBankImport\Types\CreditTransaction;
use Ksfraser\FaBankImport\Types\DebitTransaction;
use Ksfraser\FaBankImport\Types\BankTransferTransaction;

class TransactionTypeFactory
{
    public function createTransactionType(string $type, array $data)
    {
        switch ($type) {
            case 'C':
                return new CreditTransaction($data);
            case 'D':
                return new DebitTransaction($data);
            case 'B':
                return new BankTransferTransaction($data);
            default:
                throw new \InvalidArgumentException("Unknown transaction type: $type");
        }
    }
}
