<?php

namespace Ksfraser\FaBankImport;

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