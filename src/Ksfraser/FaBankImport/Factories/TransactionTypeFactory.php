<?php

namespace Ksfraser\FaBankImport\Factories;

use Ksfraser\FaBankImport\Types\CreditTransaction;
use Ksfraser\FaBankImport\Types\DebitTransaction;
use Ksfraser\FaBankImport\Types\BankTransferTransaction;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class TransactionTypeFactory
{
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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
