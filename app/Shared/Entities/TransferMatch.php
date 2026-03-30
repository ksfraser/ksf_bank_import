<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Workflow table tracking internal bank transfer matches between debit and credit transactions
 * Separate from transactions to maintain source data integrity
 * 
 * This is a consolidation of the legacy bi_transfer_matches_model class.
 */
class TransferMatch
{
    public $id;
    public $debit_transaction_id;
    public $credit_transaction_id;
    public $from_transaction_id; // directional identifier
    public $to_transaction_id;
    public $match_status; // candidate|confirmed|rejected|expired
    public $match_confidence; // float, 0-100 score
    public $match_group; // string, grouping identifier
    public $requires_review; // bool
    public $source; // auto|manual
    public $suggested_at;
    public $confirmed_at;
    public $confirmed_by;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Check if match has been confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->match_status === 'confirmed';
    }

    /**
     * Check if match is a candidate awaiting review
     */
    public function isCandidate(): bool
    {
        return $this->match_status === 'candidate';
    }

    /**
     * Get the match confidence as a percentage
     */
    public function getConfidencePercentage(): string
    {
        return number_format($this->match_confidence ?? 0, 2) . '%';
    }
}
