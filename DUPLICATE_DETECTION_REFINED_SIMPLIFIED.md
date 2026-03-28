# Refined Duplicate Detection - Simplified Strategy

**Reference:** [ROBUST_DUPLICATE_DETECTION_DESIGN.md](ROBUST_DUPLICATE_DETECTION_DESIGN.md)  
**Date:** 2025-01-16  
**Status:** Refined Design

---

## Key Corrections

### 1. **transactionCode is Authoritative** ✅

```php
// LEVEL 1: Definitive check
if (transactionCode matches AND acctid matches) {
    → DUPLICATE (100% certain)
    → No whitelist can override this
}
```

**Why:** Bank codes are unique per account. If they match, same transaction. Period.

---

### 2. **Whitelisting Only for Date+Amount+Merchant+Memo** ✅

```php
// LEVEL 2: Fuzzy detection (where whitelisting applies)
if (transactionCode does NOT match) {
    Check: date + amount + merchant + memo
    
    If found AND whitelisted:
        → Allow duplicate (e.g., SHOPPERS rule)
    If found AND NOT whitelisted:
        → Show user for review
    If NOT found:
        → Import as new
}
```

**Why:** Only use fuzzy matching where ambiguity exists. Whitelist rules only apply when code-based detection fails.

---

### 3. **Remove ±3 Day Window** ✅

```php
// BEFORE (WRONG)
WHERE valueTimestamp BETWEEN date-3 AND date+3

// AFTER (CORRECT)
WHERE valueTimestamp = exact_date
```

**Why:** You're right - if same statement re-downloads, **date is identical**. No window needed. This simplifies everything.

**Only case for window:** Inter-statement reconciliation (different statement files). That's a different flow.

---

## Corrected Two-Level Architecture

### Level 1: Direct Code Match (Fast Path)

```php
class DirectCodeMatcher
{
    public function find(array $transaction): ?array
    {
        // DEFINITIVE: Code + Account must both match
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

**Decision Tree:**
- ✅ Found = **DUPLICATE** (skip)
- ❌ Not Found = Go to Level 2

---

### Level 2: Fuzzy Match on Exact Date + Amount + Merchant + Memo

```php
class FuzzyMatcher
{
    /**
     * Find duplicates on: date + amount + merchant/payee + memo
     * 
     * NO DATE WINDOW - exact date match only
     * (Re-downloads have identical dates; cross-statement matching is different flow)
     */
    public function find(array $transaction): array
    {
        $date = $transaction['valueTimestamp'] ?? null;
        $amount = $transaction['transactionAmount'] ?? 0;
        $merchant = $transaction['merchant'] ?? null;
        $memo = $transaction['memo'] ?? null;
        $acctid = $transaction['acctid'] ?? null;
        
        $query = sprintf(
            "SELECT * FROM %s bi_transactions
             WHERE acctid = %s
             AND valueTimestamp = %s
             AND ABS(transactionAmount - %f) < 0.01
             AND (
                 merchant = %s 
                 OR memo = %s
             )
             ORDER BY id DESC",  // Most recent first
            TB_PREF,
            db_escape($acctid),
            db_escape($date),         // EXACT DATE NO WINDOW
            (float)$amount,
            db_escape($merchant),
            db_escape($memo)
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

**Decision Tree:**
- 0 matches = ✅ Import as new
- 1 match = Check whitelist rules
  - Whitelisted = ✅ Import (allowed repeat)
  - Not whitelisted = ⚠️ Show user review
- 2+ matches = 🔴 Ambiguous
  - Whitelisted = ✅ Import (allowed repeats)
  - Not whitelisted = ⚠️ Show user to pick

---

## Whitelist Rules Table

Now simplifed - only for fuzzy matches:

```sql
CREATE TABLE `0_bi_duplicate_rules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `rule_name` VARCHAR(64) UNIQUE NOT NULL,
    
    -- Match these fields to apply rule
    `merchant_pattern` VARCHAR(255),      -- LIKE pattern
    `category` VARCHAR(64),               -- Transaction category (if available)
    
    `allow_duplicates` TINYINT DEFAULT 0, -- 1 = allow repeats, 0 = require review
    `notes` TEXT,
    `active` TINYINT DEFAULT 1,
    
    INDEX `idx_merchant` (`merchant_pattern`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed data:
INSERT INTO `0_bi_duplicate_rules` VALUES
(NULL, 'SHOPPERS_RETAIL', 'SHOPPERS%', 'RETAIL', 1, 
 'Chain retailer - multiple purchases same day normal', 1),

(NULL, 'ATM_WITHDRAWALS', 'ATM%', NULL, 1,
 'Multiple ATM transactions same day normal', 1),

(NULL, 'LOBLAWS_GROCERY', '%LOBLAWS%|%SUPERSTORE%', 'GROCERY', 1,
 'Grocery chain - multiple visits possible', 1);
```

---

## Data Flow: How Merchant Populates

### Source: Parser Output

Parsers extract from bank CSV:

```php
// Example: CSV fields
$csv_fields = [
    'date' => '2025-01-15',
    'description' => 'SHOPPERS DRUG MART #1234',  // ← Merchant info
    'amount' => '-45.99'
];

// Parser populates transaction object:
$trz->valueTimestamp = '2025-01-15';
$trz->transactionAmount = -45.99;
$trz->merchant = 'SHOPPERS DRUG MART #1234';  // ← From CSV
$trz->memo = 'Pharmacy purchase';              // ← May be from CSV or parsed
```

### Stored in bi_transactions Table

```
id | valueTimestamp | transactionAmount | merchant           | memo          | transactionCode | acctid
1  | 2025-01-15     | 45.99            | SHOPPERS DRUG MART | Pharmacy      | RBC-001         | ACC123
2  | 2025-01-15     | 45.99            | SHOPPERS DRUG MART | Pharmacy      | RBC-002         | ACC123 (re-download)
```

### During Import (Re-download Scenario)

```
New TX arriving:
  valueTimestamp: 2025-01-15
  amount: 45.99
  merchant: "SHOPPERS DRUG MART"  (← FROM PARSER, not concatenated)
  memo: "Pharmacy"
  transactionCode: RBC-002  (← NEW CODE in re-download)
  acctid: ACC123

Level 1: Check transactionCode (RBC-002) + acctid
  Query: WHERE transactionCode = 'RBC-002' AND acctid = 'ACC123'
  Result: NOT FOUND (code changed) → Continue to Level 2

Level 2: Fuzzy match
  Query: WHERE valueTimestamp = '2025-01-15'
         AND amount = 45.99
         AND merchant = 'SHOPPERS DRUG MART'
  Result: Found TX-1 ✓

Level 3: Check whitelist
  Merchant: SHOPPERS% matches rule SHOPPERS_RETAIL
  Rule.allow_duplicates = 1
  Result: ✅ ALLOWED → Import with log: "Allowed repeat (SHOPPERS_RETAIL rule)"
```

---

## Simplified Result Object

```php
class DuplicateCheckResult
{
    private $isDuplicate = false;
    private $level = 'NONE';                    // EXACT|FUZZY|NONE
    private $exactMatch = null;                 // If Level 1 found
    private $fuzzyMatches = [];                 // If Level 2 found (array)
    private $recommendedAction = 'IMPORT';      // IMPORT|SKIP|REVIEW|ALLOWED_REPEAT
    private $rule = null;                       // Matching whitelist rule
    
    public static function exact($existing): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'EXACT';
        $r->exactMatch = $existing;
        $r->recommendedAction = 'SKIP';
        return $r;
    }
    
    public static function fuzzyAllowed($match, $rule): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'FUZZY';
        $r->fuzzyMatches = [$match];
        $r->recommendedAction = 'ALLOWED_REPEAT';
        $r->rule = $rule;
        return $r;
    }
    
    public static function fuzzyReview($matches): self
    {
        $r = new self();
        $r->isDuplicate = true;
        $r->level = 'FUZZY';
        $r->fuzzyMatches = $matches;
        $r->recommendedAction = 'REVIEW';
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

## Corrected Decision Flow

```
Input: New Transaction
├── Level 1: Check transactionCode + acctid
│   ├── Found → EXACT DUPLICATE
│   │   └── Action: SKIP (no whitelist override)
│   │
│   └── Not Found → Level 2
│       └── Check: date + amount + merchant + memo (NO WINDOW)
│           ├── 0 Matches
│           │   └── NOT DUPLICATE → Action: IMPORT
│           │
│           ├── 1+ Matches → Level 3
│           │   └── Check Whitelist Rules
│           │       ├── Found + allow_duplicates=1
│           │       │   └── Action: ALLOWED_REPEAT (import with log)
│           │       │
│           │       └── Found + allow_duplicates=0
│           │           └── Action: REVIEW (show user UI)
│           │
│           └── 2+ Matches (Ambiguous) → Level 3
│               └── Check Whitelist Rules
│                   ├── Found + allow_duplicates=1
│                   │   └── Action: ALLOWED_REPEAT (import all)
│                   │
│                   └── Found + allow_duplicates=0
│                       └── Action: REVIEW (user picks which to keep)
```

---

## Scenario: RBC Re-Download (Revisited)

```
Statement Download 1:
  TX: amount=$500, date=2025-01-15, merchant=PAYROLL, code=RBC-001
  → Imported ✓

Statement Download 2 (re-download, code changed):
  TX: amount=$500, date=2025-01-15, merchant=PAYROLL, code=RBC-002
  
Level 1: transactionCode=RBC-002, acctid=ACC123
  DB: SELECT _ WHERE transactionCode='RBC-002' AND acctid='ACC123'
  Result: NOT FOUND (code is different)
  
Level 2: Fuzzy match
  DB: SELECT _ WHERE valueTimestamp='2025-01-15'
                   AND amount=500
                   AND merchant='PAYROLL'
  Result: FOUND (original TX from Download 1)
  
Level 3: Whitelist
  Merchant=PAYROLL, check rules
  Rule: PAYROLL_RECURRING (allow_duplicates=0)
  Result: ⚠️ Show user review
  
UI: "Duplicate payroll deposit detected - same date, amount, merchant.
     This may be a re-download or the same recurring deposit.
     [Skip] [Import Anyway] [Remember This Pattern]"
     
User: Clicks [Skip]
Result: ✅ Not imported again
```

---

## Performance

| Operation | Time | Notes |
|-----------|------|-------|
| Level 1 (Code + acctid lookup) | ~2ms | Indexed |
| Level 2 (Date + amount + merchant) | ~5-20ms | Range queries |
| Level 3 (Whitelist pattern match) | ~1ms | In-memory |
| **Total per transaction** | ~8-23ms | <50ms typical |
| **1000 txns** | ~8-23s | Acceptable |

---

## Implementation Checklist

- [ ] Remove 3-day window from Level 2 fuzzy matcher
- [ ] Simplify whitelist rules to only apply at Level 2
- [ ] Ensure transactionCode + acctid key is primary dedup gate
- [ ] Confirm merchant field comes from parser (not concatenated)
- [ ] Update unit tests:
  - [ ] Exact code match = always duplicate
  - [ ] Fuzzy match with whitelist = import
  - [ ] Fuzzy match without whitelist = review
  - [ ] No matches = import
- [ ] Update seed data for bi_duplicate_rules table

---

## Next Steps

1. Verify merchant field source in all parsers
2. Implement simplified Level 1 + Level 2 matchers
3. Add simplified whitelist rules table
4. Update TransactionValidator to use new flow
5. Unit tests with RBC re-download scenario
