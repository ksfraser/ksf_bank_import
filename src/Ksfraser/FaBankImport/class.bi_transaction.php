<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

namespace Models;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: class.bi_transaction.php; coverage: docs/TestCases/ (file rollup)
 */
interface ThirdPartyTransactionInterface
{
    /** @BABOK BR-002 / FR-008 / FR-018 — method: getAllTransactions */
    public function getAllTransactions();
    /** @BABOK BR-002 / FR-008 / FR-018 — method: unsetTransaction */
    public function unsetTransaction($transactionId);
    /** @BABOK BR-002 / FR-008 / FR-018 — method: addCustomerFromTransaction */
    public function addCustomerFromTransaction($transactionId);
    /** @BABOK BR-002 / FR-008 / FR-018 — method: addVendorFromTransaction */
    public function addVendorFromTransaction($transactionId);
    /** @BABOK BR-002 / FR-008 / FR-018 — method: processSupplierTransaction */
    public function processSupplierTransaction($transactionId);
    /** @BABOK BR-002 / FR-008 / FR-018 — method: processCustomerTransaction */
    public function processCustomerTransaction($transactionId);
    /** @BABOK BR-002 / FR-008 / FR-018 — method: processBankTransfer */
    public function processBankTransfer($transactionId);
    /** @BABOK BR-002 / FR-008 / FR-018 — method: toggleDebitCredit */
    public function toggleDebitCredit($transactionId);
}

abstract class ThirdPartyTransaction implements ThirdPartyTransactionInterface
{
    protected $tableName;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct($tableName = 'bi_transactions')
    {
        $this->tableName = $tableName;
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getAllTransactions */
    public function getAllTransactions()
    {
        return db_query("SELECT * FROM {$this->tableName}");
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: unsetTransaction */
    public function unsetTransaction($transactionId)
    {
        db_query("UPDATE {$this->tableName} SET status = 'unset' WHERE id = ?", [$transactionId]);
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTransactionById */
    protected function getTransactionById($transactionId)
    {
        $result = db_query("SELECT * FROM {$this->tableName} WHERE id = ?", [$transactionId]);
        return $result ? $result : null;
    }
}

class SquareTransaction extends ThirdPartyTransaction
{
    /** @BABOK BR-002 / FR-008 / FR-018 — method: addCustomerFromTransaction */
    public function addCustomerFromTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            my_add_customer($transaction);
        }
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: addVendorFromTransaction */
    public function addVendorFromTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            add_vendor($transaction);
        }
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: processSupplierTransaction */
    public function processSupplierTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Process supplier transaction logic
        }
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: processCustomerTransaction */
    public function processCustomerTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Process customer transaction logic
        }
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: processBankTransfer */
    public function processBankTransfer($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Process bank transfer logic
        }
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: toggleDebitCredit */
    public function toggleDebitCredit($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Toggle debit/credit logic
        }
    }
}
