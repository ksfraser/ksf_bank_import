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

    /**
     * Get a display-friendly name for this mapping
     */
    public function getDisplayName(): string
    {
        $parts = [];

        if ($this->bankid && $this->acctid) {
            $parts[] = "{$this->bankid}:{$this->acctid}";
        }
        if ($this->intu_bid) {
            $parts[] = "(intu: {$this->intu_bid})";
        }
        if ($this->accttype) {
            $parts[] = "[{$this->accttype}]";
        }

        if (empty($parts)) {
            return "Bank Mapping #{$this->bank_account_id}";
        }

        return implode(' ', $parts);
    }

    /**
     * Convert mapping to array (for JSON serialization, etc)
     */
    public function toArray(): array
    {
        return [
            'bank_account_id' => $this->bank_account_id,
            'intu_bid' => $this->intu_bid,
            'bankid' => $this->bankid,
            'acctid' => $this->acctid,
            'accttype' => $this->accttype,
            'curdef' => $this->curdef,
        ];
    }

    /**
     * Check if this mapping has valid OFX identifiers
     */
    public function hasValidIdentifiers(): bool
    {
        return !empty($this->bankid) || !empty($this->acctid) || !empty($this->intu_bid);
    }

    /**
     * Check if this mapping is linked to a FA bank account
     */
    public function isLinkedToFAAccount(): bool
    {
        return !empty($this->bank_account_id) && $this->bank_account_id > 0;
    }

    /**
     * Get a composite key for matching (useful for deduplication)
     */
    public function getCompositeKey(): string
    {
        $key_parts = [
            'acctid' => $this->acctid ?? '',
            'bankid' => $this->bankid ?? '',
            'intu_bid' => $this->intu_bid ?? '',
        ];

        // Create a normalized key that ignores order and empty values
        $normalized = array_filter($key_parts);
        ksort($normalized);

        return json_encode($normalized) ?: '';
    }
}
