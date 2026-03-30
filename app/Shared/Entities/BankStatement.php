<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Represents a bank statement header record with account and external system mapping
 * 
 * This is a consolidation of the legacy bi_statements_model class.
 */
class BankStatement
{
    public $id;
    public $bank; // bank institution identifier
    public $account; // our bank account number
    public $currency; // currency code
    public $startBalance;
    public $endBalance;
    public $smtDate; // statement date
    public $number; // statement number
    public $seq;
    public $statementId; // external statement identifier
    public $acctid; // OFX account ID
    public $fitid; // OFX financial institution transaction ID
    public $bankid; // OFX BANKID
    public $intu_bid; // Intuit OFX BID
    public $updated_ts;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the balance reconciliation difference
     */
    public function getBalanceDifference(): float
    {
        return abs($this->endBalance - $this->startBalance);
    }
}
