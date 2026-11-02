<?php

namespace Ksfraser\FaBankImport\Factories;

use Ksfraser\FaBankImport\Types\CreditTransaction;
use Ksfraser\FaBankImport\Types\DebitTransaction;
use Ksfraser\FaBankImport\Types\BankTransferTransaction;

/**
 * @BABOK CLASS ROLLUP: TransactionTypeFactory — 1 methods; FR-015 (1)
 * @BABOK RTM: FR-015 / ARCH-001; file: Factories/TransactionTypeFactory.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionTypeFactory
{
    /** @BABOK FR-015 / ARCH-001 — method: createTransactionType */
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
