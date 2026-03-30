<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Represents a single line item in a bank statement for display and matching
 * 
 * This is a consolidation of the legacy bi_lineitem class.
 */
class LineItem
{
    public $transactionDC; // Debit/Credit indicator
    public $our_account; // our bank account
    public $valueTimestamp;
    public $entryTimestamp;
    public $otherBankaccount; // alternative spelling variant
    public $otherBankaccountName;
    public $otherBankAccount; // preferred spelling
    public $otherBankAccountName;
    public $transactionTitle;
    public $status;
    public $currency;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the counterparty account (resolves both spelling variants)
     */
    public function getCounterpartyAccount(): ?string
    {
        return $this->otherBankAccount ?? $this->otherBankaccount;
    }

    /**
     * Get the counterparty account name (resolves both spelling variants)
     */
    public function getCounterpartyAccountName(): ?string
    {
        return $this->otherBankAccountName ?? $this->otherBankaccountName;
    }
}
