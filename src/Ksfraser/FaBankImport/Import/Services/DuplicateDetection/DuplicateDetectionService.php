<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

/**
 * Main Duplicate Detection Service
 * 
 * Orchestrates three-level duplicate detection:
 * 
 * Level 1: Direct Code Match (authoritative)
 *   - transactionCode + acctid must both match
 *   - No whitelist override possible
 *   - Result: SKIP (duplicate)
 * 
 * Level 2: Fuzzy Match (fallback)
 *   - date + amount ±$0.01 + merchant/memo
 *   - No date window (re-downloads have identical dates)
 *   - Multiple candidates may match
 * 
 * Level 3: Whitelist Rules (user policy)
 *   - SHOPPERS% → allow duplicates (legitimate repeats)
 *   - PAYROLL% → review needed (should be once per period)
 *   - No rule → show user review UI
 * 
 * Responsibility: Coordinate matchers and apply business logic
 */
class DuplicateDetectionService
{
    private $directMatcher;
    private $fuzzyMatcher;
    private $rulesProvider;
    
    public function __construct(
        DirectCodeMatcher $directMatcher = null,
        FuzzyMatcher $fuzzyMatcher = null,
        DuplicateRulesProvider $rulesProvider = null
    ) {
        $this->directMatcher = $directMatcher ?? new DirectCodeMatcher();
        $this->fuzzyMatcher = $fuzzyMatcher ?? new FuzzyMatcher();
        $this->rulesProvider = $rulesProvider ?? new DuplicateRulesProvider();
    }
    
    /**
     * Detect if transaction is a duplicate using multi-level strategy.
     *
     * PHASE 2 UPDATE: Level 1 now validates ALL fields when code+acctid match.
     * If fields differ on code match, flags for review (potential data corruption).
     *
     * @param array $transaction Transaction data
     * @return DuplicateCheckResult Decision with action and matching records
     */
    public function detect(array $transaction): DuplicateCheckResult
    {
        // LEVEL 1: Direct match with field comparison (fast, authoritative)
        // PHASE 2: Now uses findAndCompare to detect field mismatches
        $codeMatchResult = $this->directMatcher->findAndCompare($transaction);
        
        if ($codeMatchResult) {
            // Code+acctid matched
            $existingTransaction = $codeMatchResult['match'];
            $fieldsThatDiffer = $codeMatchResult['fields_that_differ'];  // CSV string or empty
            
            // Pass field differences to result factory
            // If fields differ → recommendedAction='REVIEW'
            // If fields match → recommendedAction='SKIP'
            return DuplicateCheckResult::exactMatch($existingTransaction, $fieldsThatDiffer);
        }
        
        // LEVEL 2: Fuzzy match (slower, needs rule checking)
        $fuzzy = $this->fuzzyMatcher->find($transaction);
        
        if (empty($fuzzy)) {
            // No matches found
            return DuplicateCheckResult::notDuplicate();
        }
        
        // LEVEL 3: Apply whitelist rules
        $rule = $this->rulesProvider->findMatchingRule($transaction);
        
        if ($rule && $this->ruleAllowsDuplicates($rule)) {
            // Whitelisted - allow the import
            return DuplicateCheckResult::fuzzyMatchAllowed($fuzzy[0], $rule);
        }
        
        // Not whitelisted - show user
        return DuplicateCheckResult::fuzzyMatchNeedsReview($fuzzy);
    }
    
    /**
     * Check if rule allows duplicate transactions.
     *
     * @param array $rule
     * @return bool
     */
    private function ruleAllowsDuplicates(array $rule): bool
    {
        // Default: 0 (do not allow, require review)
        $allow = (int)($rule['allow_duplicates'] ?? 0);
        return $allow === 1;
    }
}
