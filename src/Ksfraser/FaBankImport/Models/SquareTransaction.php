<?php

namespace Ksfraser\FaBankImport\Models;

/**
 * @BABOK CLASS ROLLUP: SquareTransaction — 4 methods; FR-008 (4); BR-003 (4)
 * @BABOK RTM: FR-008 / BR-003; file: Models/SquareTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
class SquareTransaction extends BankTransaction
{
    /** @BABOK FR-008 / BR-003 — method: addCustomerFromTransaction */
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

    /** @BABOK FR-008 / BR-003 — method: addVendorFromTransaction */
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

    /** @BABOK FR-008 / BR-003 — method: unsetTransaction */
    public function unsetTransaction(): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'unset' WHERE id = ?"
        );
        return $stmt->execute([$this->getId()]);
    }

    /** @BABOK FR-008 / BR-003 — method: toggleDebitCredit */
    public function toggleDebitCredit(): bool
    {
        $newType = $this->getTransactionType() === 'D' ? 'C' : 'D';
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET transactionDC = ? WHERE id = ?"
        );
        return $stmt->execute([$newType, $this->getId()]);
    }
}