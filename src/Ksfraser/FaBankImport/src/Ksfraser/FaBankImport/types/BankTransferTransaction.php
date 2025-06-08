<?php

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
