<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

/**
 * Result of a duplicate detection check.
 * 
 * Represents the outcome of multi-level duplicate detection:
 * Level 1: Direct code match (transactionCode + acctid)
 * Level 2: Fuzzy match (date + amount + merchant + memo)
 * Level 3: Whitelist rules application
 * 
 * Responsibility: Encapsulate decision logic and recommended action
 */
class DuplicateCheckResult
{
    private $isDuplicate = false;
    private $level = 'NONE';                // EXACT|FUZZY|NONE
    private $exactMatch = null;             // If Level 1 found
    private $fuzzyMatches = [];             // If Level 2 found
    private $recommendedAction = 'IMPORT';  // IMPORT|SKIP|REVIEW|ALLOWED_REPEAT
    private $whitelistRule = null;          // Applied whitelist rule
    private $message = '';                  // Human-readable message
    private $fieldsThatDiffer = '';         // PHASE 2: CSV string of mismatched fields (e.g., "memo,amount")
    private $mustReviewBeforeMerge = false; // PHASE 2: Force review when fields differ
    
    private function __construct() {}
    
    /**
     * Exact match found - definitive duplicate (Level 1).
     *
     * PHASE 2 NOTE: If fields_that_differ is provided and non-empty, indicates that
     * code+acctid match but data differs. Recommendation changes to REVIEW instead of SKIP.
     *
     * @param array $existingTransaction
     * @param string $fieldsThatDiffer CSV string of fields that differ (e.g., "memo,amount")
     *                                  Empty string means all fields match (true duplicate)
     * @return self
     */
    public static function exactMatch(array $existingTransaction, string $fieldsThatDiffer = ''): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'EXACT';
        $r->exactMatch = $existingTransaction;
        $r->fieldsThatDiffer = $fieldsThatDiffer;
        
        // PHASE 2 LOGIC: If fields differ, this is a data mismatch requiring review
        // Otherwise, it's a true duplicate that should be skipped
        if (!empty($fieldsThatDiffer)) {
            $r->recommendedAction = 'REVIEW';
            $r->mustReviewBeforeMerge = true;
            $r->message = sprintf(
                'Code match with field differences: transactionCode=%s, Fields differ: %s',
                $existingTransaction['transactionCode'] ?? '',
                $fieldsThatDiffer
            );
        } else {
            $r->recommendedAction = 'SKIP';
            $r->message = sprintf(
                'Exact duplicate: transactionCode=%s, acctid=%s',
                $existingTransaction['transactionCode'] ?? '',
                $existingTransaction['acctid'] ?? ''
            );
        }
        return $r;
    }
    
    /**
     * Fuzzy match found and whitelisted - allowed repeat (Level 2 + 3).
     *
     * @param array $matchedTransaction
     * @param array $rule Whitelist rule
     * @return self
     */
    public static function fuzzyMatchAllowed(array $matchedTransaction, array $rule): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'FUZZY';
        $r->fuzzyMatches = [$matchedTransaction];
        $r->recommendedAction = 'ALLOWED_REPEAT';
        $r->whitelistRule = $rule;
        $r->message = sprintf(
            'Allowed repeat transaction (rule: %s)',
            $rule['rule_name'] ?? 'UNKNOWN'
        );
        return $r;
    }
    
    /**
     * Fuzzy match found but not whitelisted - needs review (Level 2).
     *
     * @param array $matchedTransactions
     * @return self
     */
    public static function fuzzyMatchNeedsReview(array $matchedTransactions): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'FUZZY';
        $r->fuzzyMatches = $matchedTransactions;
        $r->recommendedAction = 'REVIEW';
        $r->message = sprintf(
            'Possible duplicate - %d transaction(s) match date/amount/merchant',
            count($matchedTransactions)
        );
        return $r;
    }
    
    /**
     * No duplicate found - safe to import.
     *
     * @return self
     */
    public static function notDuplicate(): self
    {
        $r = new self();
        $r->isDuplicate = false;
        $r->level = 'NONE';
        $r->recommendedAction = 'IMPORT';
        $r->message = 'No duplicate detected';
        return $r;
    }
    
    // Getters
    
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }
    
    public function getLevel(): string
    {
        return $this->level;
    }
    
    public function getExactMatch(): ?array
    {
        return $this->exactMatch;
    }
    
    public function getFuzzyMatches(): array
    {
        return $this->fuzzyMatches;
    }
    
    public function getRecommendedAction(): string
    {
        return $this->recommendedAction;
    }
    
    public function getWhitelistRule(): ?array
    {
        return $this->whitelistRule;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Get fields that differ (empty string if all match).
     * 
     * PHASE 2: Returns CSV string like "memo,amount" or "" if all fields identical.
     * Only populated for EXACT matches with code+acctid hit.
     */
    public function getFieldsThatDiffer(): string
    {
        return $this->fieldsThatDiffer;
    }
    
    /**
     * Whether review is mandatory before merging/skipping.
     * 
     * PHASE 2: True when fields differ on code match (potential data corruption).
     */
    public function mustReviewBeforeMerge(): bool
    {
        return $this->mustReviewBeforeMerge;
    }
    
    /**
     * Should this transaction be skipped?
     */
    public function shouldSkip(): bool
    {
        return $this->recommendedAction === 'SKIP';
    }
    
    /**
     * Should this transaction be imported?
     */
    public function shouldImport(): bool
    {
        return in_array($this->recommendedAction, ['IMPORT', 'ALLOWED_REPEAT']);
    }
    
    /**
     * Should this transaction go to user review?
     */
    public function needsReview(): bool
    {
        return $this->recommendedAction === 'REVIEW';
    }
}
