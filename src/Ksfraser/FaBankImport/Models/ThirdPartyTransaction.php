<?php

namespace Models;

/**
 * @BABOK CLASS ROLLUP: ThirdPartyTransactionInterface — 12 methods; FR-008 (12); BR-003 (12)
 * @BABOK RTM: FR-008 / BR-003; file: Models/ThirdPartyTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
interface ThirdPartyTransactionInterface
{
    /** @BABOK FR-008 / BR-003 — method: getAllTransactions */
    public function getAllTransactions();
    /** @BABOK FR-008 / BR-003 — method: unsetTransaction */
    public function unsetTransaction($transactionId);
    /** @BABOK FR-008 / BR-003 — method: addCustomerFromTransaction */
    public function addCustomerFromTransaction($transactionId);
    /** @BABOK FR-008 / BR-003 — method: addVendorFromTransaction */
    public function addVendorFromTransaction($transactionId);
    /** @BABOK FR-008 / BR-003 — method: processSupplierTransaction */
    public function processSupplierTransaction($transactionId);
    /** @BABOK FR-008 / BR-003 — method: processCustomerTransaction */
    public function processCustomerTransaction($transactionId);
    /** @BABOK FR-008 / BR-003 — method: processBankTransfer */
    public function processBankTransfer($transactionId);
    /** @BABOK FR-008 / BR-003 — method: toggleDebitCredit */
    public function toggleDebitCredit($transactionId);
}

abstract class AbstractThirdPartyTransaction implements ThirdPartyTransactionInterface
{
   protected $tableName;

    /** @BABOK FR-008 / BR-003 — method: __construct */
    public function __construct($tableName = 'bi_transactions')
    {
        $this->tableName = $tableName;
    }

    /** @BABOK FR-008 / BR-003 — method: getAllTransactions */
    public function getAllTransactions()
    {
        return db_query("SELECT * FROM {$this->tableName}");
    }

    /** @BABOK FR-008 / BR-003 — method: unsetTransaction */
    public function unsetTransaction($transactionId)
    {
        db_query("UPDATE {$this->tableName} SET status = 'unset' WHERE id = ?", [$transactionId]);
    }

    /** @BABOK FR-008 / BR-003 — method: getTransactionById */
    protected function getTransactionById($transactionId)
    {
        $result = db_query("SELECT * FROM {$this->tableName} WHERE id = ?", [$transactionId]);
        return $result ? $result : null;
    }
}
