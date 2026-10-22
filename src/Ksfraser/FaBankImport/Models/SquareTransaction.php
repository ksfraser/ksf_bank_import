<?php

namespace Ksfraser\FaBankImport\Models;

/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 */
class SquareTransaction extends BankTransaction
{
    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
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

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
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

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function unsetTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'unset' WHERE id = ?"
        );
        return $stmt->execute([$this->getId()]);
    }

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function toggleDebitCredit(): bool
    {
        $newType = $this->getTransactionType() === 'D' ? 'C' : 'D';
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET transactionDC = ? WHERE id = ?"
        );
        return $stmt->execute([$newType, $this->getId()]);
    }
}