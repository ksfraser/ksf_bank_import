<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Keyword pattern database for partner matching
 * Used by duplicate detection and matching algorithms with occurrence tracking
 * 
 * This is a consolidation of the legacy bi_partners_data class.
 */
class PartnerKeyword
{
    public $id;
    public $partner_id; // int, customer/supplier ID
    public $partner_detail_id; // int, branch/detail ID
    public $partner_type; // int, customer|supplier classification
    public $data; // string, keyword/pattern for matching
    public $updated_ts; // timestamp
    public $occurrence_count; // int, match frequency tracking

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Increment the occurrence count
     */
    public function incrementOccurrence(): void
    {
        $this->occurrence_count = ($this->occurrence_count ?? 0) + 1;
    }

    /**
     * Get the match score based on occurrence count
     */
    public function getMatchScore(): float
    {
        return min(1.0, ($this->occurrence_count ?? 1) / 10);
    }
}
