<?php

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