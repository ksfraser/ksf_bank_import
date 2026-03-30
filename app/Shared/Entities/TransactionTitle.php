<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * MT940 multi-line title parsing storage
 * Splits 10 title fields to extract transaction details from structured bank narrative
 * 
 * This is a consolidation of the legacy bi_transactionTitle_model class.
 */
class TransactionTitle
{
    public $id_bi_transactionTitle_model;
    public $transaction_id;
    public $bi_transactionTitle; // concatenated title from all sources
    public $staging_id; // master record reference
    
    // Individual title fields from different formats/fields
    public $bi_transactionTitle1;
    public $bi_transactionTitle2;
    public $bi_transactionTitle3;
    public $bi_transactionTitle4;
    public $bi_transactionTitle5;
    public $bi_transactionTitle6;
    public $bi_transactionTitle7;
    public $bi_transactionTitle8;
    public $bi_transactionTitle9;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get all title parts as an array
     */
    public function getTitleParts(): array
    {
        $parts = [];
        for ($i = 1; $i <= 9; $i++) {
            $prop = "bi_transactionTitle{$i}";
            if (!empty($this->$prop)) {
                $parts[] = $this->$prop;
            }
        }
        return $parts;
    }

    /**
     * Rebuild the concatenated title from individual parts
     */
    public function rebuildConcatenatedTitle(): string
    {
        return implode(' | ', $this->getTitleParts());
    }
}
