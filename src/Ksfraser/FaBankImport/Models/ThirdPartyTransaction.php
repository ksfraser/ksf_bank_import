<?php

namespace Models;

/**
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001

 */
interface ThirdPartyTransactionInterface
{
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function getAllTransactions();
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function unsetTransaction($transactionId);
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function addCustomerFromTransaction($transactionId);
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function addVendorFromTransaction($transactionId);
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function processSupplierTransaction($transactionId);
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function processCustomerTransaction($transactionId);
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function processBankTransfer($transactionId);
    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function toggleDebitCredit($transactionId);
}

/**
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 */
abstract class AbstractThirdPartyTransaction implements ThirdPartyTransactionInterface
{
   protected $tableName;

    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function __construct($tableName = 'bi_transactions')
    {
        $this->tableName = $tableName;
    }

    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function getAllTransactions()
    {
        return db_query("SELECT * FROM {$this->tableName}");
    }

    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    public function unsetTransaction($transactionId)
    {
        db_query("UPDATE {$this->tableName} SET status = 'unset' WHERE id = ?", [$transactionId]);
    }

    /** * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
*/
    protected function getTransactionById($transactionId)
    {
        $result = db_query("SELECT * FROM {$this->tableName} WHERE id = ?", [$transactionId]);
        return $result ? $result : null;
    }
}
