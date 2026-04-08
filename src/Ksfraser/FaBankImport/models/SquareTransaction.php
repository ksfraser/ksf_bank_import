<?php

namespace Ksfraser\FaBankImport\Models;

class SquareTransaction extends BankTransaction
{
    public function addCustomerFromTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO customers (name, email, created_from_transaction) 
             VALUES (?, ?, ?)"
        );
        return $stmt->execute([
            $this->data['merchant'] ?? '',
            $this->data['email'] ?? '',
            $this->getId()
        ]);
    }

    public function addVendorFromTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO suppliers (name, created_from_transaction) 
             VALUES (?, ?)"
        );
        return $stmt->execute([
            $this->data['merchant'] ?? '',
            $this->getId()
        ]);
    }

    public function unsetTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'unset' WHERE id = ?"
        );
        return $stmt->execute([$this->getId()]);
    }

    public function toggleDebitCredit(): bool
    {
        $newType = $this->getTransactionType() === 'D' ? 'C' : 'D';
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET transactionDC = ? WHERE id = ?"
        );
        return $stmt->execute([$newType, $this->getId()]);
    }
}