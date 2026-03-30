<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Cross-reference table mapping FA bank accounts to OFX/external account identifiers
 * 
 * This is a consolidation of the legacy bi_bank_accounts_model class.
 */
class BankAccountMapping
{
    public $bank_account_id; // int, FrontAccounting bank account reference
    public $intu_bid; // Intuit OFX BID
    public $bankid; // OFX BANKID
    public $acctid; // OFX ACCTID
    public $accttype; // account type (e.g., CHECKING, SAVINGS)
    public $curdef; // currency code

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the primary external account identifier (prefer bankid/acctid)
     */
    public function getPrimaryExternalId(): ?string
    {
        return $this->bankid && $this->acctid ? "{$this->bankid}:{$this->acctid}" : $this->intu_bid;
    }
}
