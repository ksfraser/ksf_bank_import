<?php

namespace Ksfraser\FaBankImport;

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