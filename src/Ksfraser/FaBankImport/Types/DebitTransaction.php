<?php

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