<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

namespace Models;

interface ThirdPartyTransactionInterface
{
    public function getAllTransactions();
    public function unsetTransaction($transactionId);
    public function addCustomerFromTransaction($transactionId);
    public function addVendorFromTransaction($transactionId);
    public function processSupplierTransaction($transactionId);
    public function processCustomerTransaction($transactionId);
    public function processBankTransfer($transactionId);
    public function toggleDebitCredit($transactionId);
}

abstract class ThirdPartyTransaction implements ThirdPartyTransactionInterface
{
    protected $tableName;

    public function __construct($tableName = 'bi_transactions')
    {
        $this->tableName = $tableName;
    }

    public function getAllTransactions()
    {
        return db_query("SELECT * FROM {$this->tableName}");
    }

    public function unsetTransaction($transactionId)
    {
        db_query("UPDATE {$this->tableName} SET status = 'unset' WHERE id = ?", [$transactionId]);
    }

    protected function getTransactionById($transactionId)
    {
        $result = db_query("SELECT * FROM {$this->tableName} WHERE id = ?", [$transactionId]);
        return $result ? $result : null;
    }
}

class SquareTransaction extends ThirdPartyTransaction
{
    public function addCustomerFromTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            my_add_customer($transaction);
        }
    }

    public function addVendorFromTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            add_vendor($transaction);
        }
    }

    public function processSupplierTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Process supplier transaction logic
        }
    }

    public function processCustomerTransaction($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Process customer transaction logic
        }
    }

    public function processBankTransfer($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Process bank transfer logic
        }
    }

    public function toggleDebitCredit($transactionId)
    {
        $transaction = $this->getTransactionById($transactionId);
        if ($transaction) {
            // Toggle debit/credit logic
        }
    }
}
