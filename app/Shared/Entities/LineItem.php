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
    public $otherBankAccount; // counterparty account number
    public $otherBankAccountName; // counterparty account name
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
     * Get the counterparty account number
     */
    public function getCounterpartyAccount(): ?string
    {
        return $this->otherBankAccount;
    }

    /**
     * Get the counterparty account name
     */
    public function getCounterpartyAccountName(): ?string
    {
        return $this->otherBankAccountName;
    }
}
