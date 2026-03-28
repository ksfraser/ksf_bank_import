# Robust Duplicate Detection Architecture

**Date:** 2025-01-16  
**Status:** Design Phase  
**Impact:** Prevents false positives (Shoppers), handles bank-specific quirks (RBC), maintains data integrity

---

## Problem Statement

Current duplicate detection is **too simplistic** and **too rigid**:

```php
// Current: Simple 1-factor check
function trans_exists()
{
    SELECT * FROM bi_transactions 
    WHERE transactionCode = ? AND acctid = ?
}
```

### Known Failure Scenarios

| Scenario | Issue | Current Impact |
|----------|-------|-----------------|
| **RBC statement re-download** | RBC generates new `transactionCode` per download | ❌ Same transaction imported twice |
| **Shoppers multiple purchases** | Same amount, date, merchant (legitimate) | ⚠️ Flagged as duplicate when it's not |
| **Manulife cross-account codes** | Same code used in different accounts | ✅ Handled correctly (acctid part of key) |
| **Unusual duplicates** | Same amount/date/merchant but different reference | ❓ May or may not be dupe |

---

## Solution: Multi-Level Matching Strategy

### Level 1: Direct Match (Fast Path) ⚡

**Priority:** Highest  
**When:** Transaction exists with exact code + account match

```sql
SELECT * FROM bi_transactions 
WHERE transactionCode = ? AND acctid = ?
LIMIT 1;
```

**Decision:** ✅ **IS DUPLICATE** → Stop, return existing transaction  
**Assumption:** Banks guarantee unique codes per account (most cases)  
**Exception:** RBC has proven this false, so Level 1 can fail

---

### Level 2: Fuzzy Matching (Fallback) 🔍

**Priority:** Medium  
**When:** Level 1 returns nothing, but transaction might still be old

Match on: **valueTimestamp + transactionAmount + merchant/accountName + memo**

```sql
SELECT * FROM bi_transactions 
WHERE acctid = :acctid
  AND valueTimestamp = :date
  AND transactionAmount = :amount
  AND (
      merchant = :merchant 
      OR accountName = :accountName
      OR memo LIKE CONCAT(:memo, '%')
  )
  -- AND NOT already_processed  -- Exclude processed txns
LIMIT 1;
```

**Return scenario:**
- **0 matches** → Not a duplicate, safe to import ✅
- **1 match** → Likely duplicate, but needs review 🤔
- **2+ matches** → Ambiguous! Could be legit repeats (Shoppers) or data corruption 🔴

---

### Level 3: Configuration-Based Whitelisting 📋

**Priority:** Varies (by rule)  
**When:** Fuzzy match returns 2+ candidates

Merchants/categories that commonly have repeat transactions:

```php
// bi_duplicate_rules table
[
    [
        'rule_name' => 'SHOPPERS_REPEAT_ALLOWED',
        'merchant_like' => 'SHOPPERS%',
        'category' => 'RETAIL',
        'action' => 'ALLOW_DUPLICATES',  // Allow multiple same-day purchases
        'notes' => 'Natural retail behavior'
    ],
    [
        'rule_name' => 'ATM_WITHDRAWALS',
        'merchant_like' => 'ATM%',
        'action' => 'ALLOW_DUPLICATES',
        'notes' => 'Can have multiple ATM transactions same day'
    ],
    [
        'rule_name' => 'PAYROLL_RECURRING',
        'merchant_like' => 'PAYROLL%',
        'action' => 'EXACT_DUPLICATE_ONLY',  // Only allow if date+amount+memo match exactly
        'notes' => 'Salaries happen once per period'
    ]
]
```

---

## Implementation Architecture

### 1. New `DuplicateDetectionService` Class

```php
namespace Ksfraser\FaBankImport\Import\Services;

class DuplicateDetectionService
{
    private $level1;  // DirectCodeMatcher
    private $level2;  // FuzzyMatcher
    private $rules;   // DuplicateRulesProvider
    
    /**
     * Detect if transaction is duplicate, with multi-level strategy.
     * 
     * @param array $transaction
     * @return DuplicateCheckResult with: 
     *   - isDuplicate: bool
     *   - level: 'EXACT'|'FUZZY'|'RULE'|'NONE'
     *   - exactMatch: ?array (if found)
     *   - fuzzyMatches: array (if 2+ found)
     *   - recommendedAction: 'IMPORT'|'REVIEW'|'BLOCK'|'ALLOW_REPEAT'
     */
    public function detect(array $transaction): DuplicateCheckResult
    {
        // LEVEL 1: Try direct match (fast)
        $exact = $this->level1->find($transaction);
        if ($exact) {
            return DuplicateCheckResult::exactMatch($exact);
        }
        
        // LEVEL 2: Try fuzzy match (medium)
        $fuzzy = $this->level2->find($transaction);
        
        if (count($fuzzy) === 0) {
            // No duplicate detected
            return DuplicateCheckResult::notDuplicate();
        }
        
        if (count($fuzzy) === 1) {
            // Likely duplicate (unclear if exact or not)
            return DuplicateCheckResult::fuzzyMatch($fuzzy[0], 'REVIEW');
        }
        
        // LEVEL 3: Multiple candidates - check whitelist rules
        $rule = $this->rules->getMatchingRule($transaction);
        
        if ($rule && $rule->allowsDuplicates()) {
            return DuplicateCheckResult::fuzzyMatch(
                $fuzzy[0],
                'ALLOW_REPEAT',
                $rule
            );
        }
        
        // No rule allows this - ambiguous
        return DuplicateCheckResult::ambiguous(
            $fuzzy,
            'REQUEST_REVIEW'  // Show user UI to choose
        );
    }
}
```

### 2. Direct Code Matcher (Level 1)

```php
class DirectCodeMatcher
{
    public function find(array $transaction): ?array
    {
        // Fast lookup: transactionCode + acctid
        $query = sprintf(
            "SELECT * FROM %s bi_transactions 
             WHERE transactionCode = %s 
             AND acctid = %s
             LIMIT 1",
            TB_PREF,
            db_escape($transaction['transactionCode'] ?? ''),
            db_escape($transaction['acctid'] ?? '')
        );
        
        $result = db_query($query);
        return db_fetch_assoc($result);
    }
}
```

### 3. Fuzzy Matcher (Level 2)

```php
class FuzzyMatcher
{
    /**
     * Find transactions matching: date + amount + merchant/memo
     * 
     * Window: Within 3 days (accounts for statement lags)
     */
    public function find(array $transaction): array
    {
        $date = $transaction['valueTimestamp'] ?? null;
        $amount = $transaction['transactionAmount'] ?? 0;
        $merchant = $transaction['merchant'] ?? null;
        $acctid = $transaction['acctid'] ?? null;
        $memo = $transaction['memo'] ?? null;
        
        $dateStart = date('Y-m-d', strtotime($date) - 86400 * 3);  // 3 days before
        $dateEnd = date('Y-m-d', strtotime($date) + 86400 * 3);    // 3 days after
        
        $query = sprintf(
            "SELECT * FROM %s bi_transactions
             WHERE acctid = %s
             AND valueTimestamp BETWEEN %s AND %s
             AND ABS(transactionAmount - %f) < 0.01
             AND (
                 merchant = %s 
                 OR accountName = %s
                 OR memo LIKE %s
             )
             ORDER BY valueTimestamp, transactionAmount",
            TB_PREF,
            db_escape($acctid),
            db_escape($dateStart),
            db_escape($dateEnd),
            (float)$amount,
            db_escape($merchant),
            db_escape($merchant),
            db_escape($memo ? $memo . '%' : '%')
        );
        
        $results = [];
        $res = db_query($query);
        while ($row = db_fetch_assoc($res)) {
            $results[] = $row;
        }
        return $results;
    }
}
```

### 4. Duplicate Rules Provider

```php
class DuplicateRulesProvider
{
    private $rulesCache = [];
    
    /**
     * Get rules that apply to this transaction
     */
    public function getMatchingRule(array $transaction): ?DuplicateRule
    {
        $merchant = $transaction['merchant'] ?? '';
        $category = $transaction['category'] ?? '';
        
        $rules = $this->loadRules();  // FROM bi_duplicate_rules table
        
        foreach ($rules as $rule) {
            if ($rule->matches($merchant, $category, $transaction)) {
                return $rule;
            }
        }
        
        return null;
    }
    
    private function loadRules(): array
    {
        // SELECT * FROM bi_duplicate_rules WHERE active = 1
        // WITH IN-MEMORY CACHING
    }
}
```

### 5. Result Object

```php
class DuplicateCheckResult
{
    private $isDuplicate = false;
    private $level = 'NONE';                    // EXACT|FUZZY|RULE|NONE
    private $exactMatch = null;
    private $fuzzyMatches = [];
    private $recommendedAction = 'IMPORT';      // IMPORT|REVIEW|BLOCK|ALLOW_REPEAT
    private $rule = null;
    
    public static function exactMatch($existing): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'EXACT';
        $r->exactMatch = $existing;
        $r->recommendedAction = 'SKIP';  // Already exists
        return $r;
    }
    
    public static function fuzzyMatch($candidate, $action, ?DuplicateRule $rule = null): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'FUZZY';
        $r->fuzzyMatches = [$candidate];
        $r->recommendedAction = $action;  // REVIEW or ALLOW_REPEAT
        $r->rule = $rule;
        return $r;
    }
    
    public static function ambiguous(array $candidates, $action): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'AMBIGUOUS';
        $r->fuzzyMatches = $candidates;
        $r->recommendedAction = $action;  // REQUEST_REVIEW
        return $r;
    }
    
    public static function notDuplicate(): self
    {
        $r = new self();
        $r->isDuplicate = false;
        $r->level = 'NONE';
        $r->recommendedAction = 'IMPORT';
        return $r;
    }
}
```

---

## Database Schema

### New `bi_duplicate_rules` Table

```sql
CREATE TABLE `0_bi_duplicate_rules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `rule_name` VARCHAR(64) UNIQUE NOT NULL,
    `merchant_pattern` VARCHAR(255),          -- LIKE pattern: 'SHOPPERS%'
    `category` VARCHAR(64),
    `action` ENUM(
        'ALLOW_DUPLICATES',                   -- Allow same-day repeats
        'EXACT_DUPLICATE_ONLY',               -- Block unless exact match
        'REQUIRE_REVIEW'                      -- Always show UI
    ) DEFAULT 'REQUIRE_REVIEW',
    `notes` TEXT,
    `created_by` INT,
    `created_ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `active` TINYINT DEFAULT 1,
    
    INDEX `idx_merchant` (`merchant_pattern`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed data
INSERT INTO `0_bi_duplicate_rules` VALUES
(NULL, 'SHOPPERS_RETAIL', 'SHOPPERS%', 'RETAIL', 'ALLOW_DUPLICATES', 
 'Multiple purchases in single day are normal', 1, NOW(), 1),

(NULL, 'ATM_WITHDRAWALS', 'ATM%', NULL, 'ALLOW_DUPLICATES',
 'Multiple ATM withdrawals same day are normal', 1, NOW(), 1),

(NULL, 'GROCERY_REPEATS', '%LOBLAWS%', 'GROCERY', 'ALLOW_DUPLICATES',
 'Multiple grocery store visits same day are normal', 1, NOW(), 1);
```

---

## Integration Points

### 1. In TransactionValidator

```php
class TransactionValidator
{
    private $duplicateDetection;
    
    public function validate($transaction, int $bankAccountId, array $options = []): ValidationResult
    {
        // ... other checks ...
        
        // New duplicate detection
        if ($options['checkDuplicate'] ?? false) {
            $dupResult = $this->duplicateDetection->detect($transaction);
            
            if ($dupResult->isDuplicate()) {
                if ($dupResult->getRecommendedAction() === 'SKIP') {
                    // Exact duplicate - skip silently
                    $result->recordRuleCheck('not_duplicate', false);
                    throw TransactionValidationException::duplicateTransaction(...);
                }
                
                if ($dupResult->getRecommendedAction() === 'ALLOW_REPEAT') {
                    // Whitelist rule allows it
                    $result->addWarning('Allowed repeat transaction (rule: ' . 
                        $dupResult->getRule()->getName() . ')');
                    $result->recordRuleCheck('not_duplicate', true);
                }
                
                if ($dupResult->getRecommendedAction() === 'REVIEW') {
                    // Show UI for manual decision
                    $result->addWarning('Possible duplicate - review recommended');
                    $result->recordRuleCheck('not_duplicate', 'PENDING_REVIEW');
                }
            } else {
                $result->recordRuleCheck('not_duplicate', true);
            }
        }
    }
}
```

### 2. In ProcessStatements UI

```php
// When UI shows "possible duplicate" matches:
// 1. Display fuzzy matched transaction(s)
// 2. Let user choose: "Skip", "Import anyway", "Mark as duplicate grouping"
// 3. Store user decision in bi_duplicate_rules if creating new pattern
```

---

## Migration Path

### Phase 1: Implement Service (No breaking changes)
1. Create `DuplicateDetectionService`
2. Create `DirectCodeMatcher`, `FuzzyMatcher`, `DuplicateRulesProvider`
3. Add `bi_duplicate_rules` table with seed data
4. Tests: 100% coverage for each component

### Phase 2: Integrate into Validators
1. Inject `DuplicateDetectionService` into `TransactionValidator`
2. Update duplicate check logic to use multi-level approach
3. Tests: Integration tests with various scenarios

### Phase 3: UI Integration
1. Update `process_statements.php` to show fuzzy matches
2. Allow user to whitelist new patterns
3. Log all decisions in audit trail

---

## Testing Strategy

```php
// Unit Tests
test_level1_exact_match_works()
test_level1_returns_null_when_no_match()
test_level2_finds_same_day_transactions()
test_level2_tolerates_3day_window()
test_level2_tolerates_amount_variations()
test_level3_applies_shoppers_rule()
test_level3_applies_atm_rule()
test_ambiguous_result_when_2plus_matches()

// Integration Tests
test_rbc_redownload_scenario()          // New code, same date/amount/merchant
test_shoppers_repeat_scenario()         // Different transactions, same day
test_manulife_crossaccount_scenario()   // Different accounts, same code
```

---

## Benefits

| Aspect | Benefit |
|--------|---------|
| **Accuracy** | Catches duplicates RBC would slip through |
| **False Positives** | Shoppers no longer blocked |
| **User Control** | Can whitelist patterns they know are repeats |
| **Auditability** | Tracks why each detection decision was made |
| **Extensibility** | New rules can be added without code changes |
| **Performance** | Level 1 is O(1) index lookup; only goes to Level 2 if needed |

---

## Related Issues

- 🔴 RBC re-downloads creating duplicate transactions
- 🟡 Legitimate repeat transactions (Shoppers) being flagged
- 🔴 Cross-account duplicate codes (Manulife)
- 🟡 No user-facing duplicate resolution UI

---

## Next Steps

1. **Implement** `DuplicateDetectionService` (start with Level 1 + 2)
2. **Add database** `bi_duplicate_rules` table  
3. **Create unit tests** (focus on RBC + Shoppers scenarios)
4. **Integrate** into `TransactionValidator`
5. **Build UI** for fuzzy match review/whitelisting
