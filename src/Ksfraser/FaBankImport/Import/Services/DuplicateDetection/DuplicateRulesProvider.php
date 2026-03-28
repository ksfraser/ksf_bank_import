<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Level 3: Duplicate Rules Provider
 * 
 * Loads and caches whitelist rules that determine whether fuzzy matches
 * should be allowed (e.g., SHOPPERS can have multiple purchases same day).
 * 
 * Rules are stored in bi_duplicate_rules table with pattern matching.
 * 
 * Responsibility: Manage whitelist rule loading and matching
 */
class DuplicateRulesProvider
{
    private $rulesCache = null;
    
    /**
     * Find a whitelisted rule that applies to this transaction.
     *
     * @param array $transaction Transaction data
     * @return array|null Matching rule if found
     */
    public function findMatchingRule(array $transaction): ?array
    {
        $rules = $this->loadRules();
        
        if (empty($rules)) {
            return null;
        }
        
        $merchant = $transaction['merchant'] ?? '';
        $category = $transaction['category'] ?? '';
        
        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $merchant, $category)) {
                return $rule;
            }
        }
        
        return null;
    }
    
    /**
     * Check if a rule matches the transaction.
     *
     * @param array $rule
     * @param string $merchant
     * @param string $category
     * @return bool
     */
    private function ruleMatches(array $rule, string $merchant, string $category): bool
    {
        // Check merchant pattern
        if (isset($rule['merchant_pattern']) && !empty($rule['merchant_pattern'])) {
            if (!$this->patternMatches($rule['merchant_pattern'], $merchant)) {
                return false;
            }
        }
        
        // Check category (if specified)
        if (isset($rule['category']) && !empty($rule['category'])) {
            if ($category !== $rule['category']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Match a merchant pattern (LIKE format).
     *
     * SHOPPERS% matches: SHOPPERS, SHOPPERS DRUG MART, etc.
     * Multiple patterns: pattern1|pattern2|pattern3
     *
     * @param string $pattern
     * @param string $merchant
     * @return bool
     */
    private function patternMatches(string $pattern, string $merchant): bool
    {
        if (empty($pattern) || empty($merchant)) {
            return false;
        }
        
        // Split by pipe for multiple patterns
        $patterns = array_map('trim', explode('|', $pattern));
        
        foreach ($patterns as $p) {
            if ($this->likeMatch($p, $merchant)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * SQL LIKE pattern matching.
     *
     * @param string $pattern Pattern (e.g., 'SHOPPERS%')
     * @param string $value Value to match
     * @return bool
     */
    private function likeMatch(string $pattern, string $value): bool
    {
        // Convert SQL LIKE to regex
        $pattern = strtoupper($pattern);
        $value = strtoupper($value);
        
        // Escape regex special chars except % and _
        $regex = preg_escape($pattern, '/');
        // Convert % to .* and _ to .
        $regex = str_replace('\\%', '.*', $regex);
        $regex = str_replace('\\_', '.', $regex);
        
        return preg_match('/^' . $regex . '$/', $value) === 1;
    }
    
    /**
     * Load all active whitelist rules from database.
     *
     * @return array Array of rule records
     * @throws TransactionFetchException
     */
    private function loadRules(): array
    {
        // Use cache if already loaded
        if ($this->rulesCache !== null) {
            return $this->rulesCache;
        }
        
        try {
            $query = sprintf(
                "SELECT * FROM %s bi_duplicate_rules 
                 WHERE active = 1
                 ORDER BY rule_name",
                TB_PREF
            );
            
            $rules = [];
            $res = db_query($query, 'Could not load duplicate rules');
            
            while ($row = db_fetch_assoc($res)) {
                $rules[] = $row;
            }
            
            // Cache for this request
            $this->rulesCache = $rules;
            return $rules;
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_duplicate_rules",
                $e->getMessage()
            );
        }
    }
}
