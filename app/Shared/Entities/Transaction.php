<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Represents a single imported bank transaction from any source (OFX, CSV, MT940, PayPal, etc.)
 * 
 * This is a consolidation of the legacy bi_transaction class.
 * It serves as the canonical entity for bank transactions within the Shared kernel.
 */
class Transaction
{
    public $id;
    public $smt_id; // statement reference
    public $valueTimestamp;
    public $entryTimestamp;
    public $account; // our bank account
    public $accountName;
    public $transactionType;
    public $transactionCode;
    public $transactionCodeDesc;
    public $transactionDC; // Debit/Credit indicator
    public $transactionAmount;
    public $transactionTitle; // description string
    public $status;
    public $matchinfo;
    public $fa_trans_type;
    public $fa_trans_no; // FrontAccounting transaction reference
    public $fitid; // OFX identifier
    public $acctid; // OFX account ID
    public $merchant;
    public $category;
    public $sic;
    public $memo;
    public $checknumber;
    public $matched;
    public $created;
    public $g_partner; // user selection for quick entry
    public $g_option;
    public $partnerId; // matched FA details
    public $custBranch;
    public $invoiceNo;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Check if transaction has been matched to a FA record
     */
    public function isMatched(): bool
    {
        return !empty($this->fa_trans_type) && !empty($this->fa_trans_no);
    }

    /**
     * Check if transaction is a debit
     */
    public function isDebit(): bool
    {
        return $this->transactionDC === 'D' || $this->transactionDC === 'DEBIT';
    }

    /**
     * Check if transaction is a credit
     */
    public function isCredit(): bool
    {
        return $this->transactionDC === 'C' || $this->transactionDC === 'CREDIT';
    }
}
