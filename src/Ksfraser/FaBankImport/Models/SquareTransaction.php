<?php

namespace Ksfraser\FaBankImport\Models;

/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010

 *
 * The optional $transactionId parameter lets the controller layer address a
 * transaction by id (getTransactionById-style API) while the model-own
 * callers operate on the row the instance was built from.
 */
class SquareTransaction extends BankTransaction
{
    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function getAllTransactions(): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM bi_transactions ORDER BY id"
        );
        $stmt->execute([]);
        return $stmt->fetch() ?: [];
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function addCustomerFromTransaction($transactionId = null): bool
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO customers (name, email, created_from_transaction)
             VALUES (?, ?, ?)"
        );
        return $stmt->execute([
            $this->data['merchant'] ?? '',
            $this->data['email'] ?? '',
            $this->resolveTransactionId($transactionId)
        ]);
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function addVendorFromTransaction($transactionId = null): bool
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO suppliers (name, created_from_transaction)
             VALUES (?, ?)"
        );
        return $stmt->execute([
            $this->data['merchant'] ?? '',
            $this->resolveTransactionId($transactionId)
        ]);
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function unsetTransaction($transactionId = null): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET status = 'unset' WHERE id = ?"
        );
        return $stmt->execute([$this->resolveTransactionId($transactionId)]);
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function toggleDebitCredit($transactionId = null): bool
    {
        $newType = $this->getTransactionType() === 'D' ? 'C' : 'D';
        $stmt = $this->connection->prepare(
            "UPDATE bi_transactions SET transactionDC = ? WHERE id = ?"
        );
        return $stmt->execute([$newType, $this->resolveTransactionId($transactionId)]);
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function processSupplierTransaction($transactionId = null): bool
    {
        return $this->processTransaction($transactionId);
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function processCustomerTransaction($transactionId = null): bool
    {
        return $this->processTransaction($transactionId);
    }

    /** * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
*/
    public function processBankTransfer($transactionId = null): bool
    {
        return $this->processTransaction($transactionId);
    }

    /**
     * @param int|null $transactionId Optional id from the controller layer.
     * @return int The explicit id, or the id of the stored row.
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    private function resolveTransactionId($transactionId): int
    {
        return $transactionId !== null ? (int)$transactionId : $this->getId();
    }
}
