# Partner Matching Architecture - CORRECTED

## The Real Problem

There are **TWO parallel systems** for storing partner data:

### System A (Current, Used): Full-Text Storage
```php
// In pdata.inc::set_partner_data()
// Stores complete value in data column
INSERT INTO bi_partners_data 
  (partner_id, partner_type, partner_detail_id, data)
VALUES ($id, $type, $detail, "12345-ACME-CORP");  // Full value

// Search uses LIKE
WHERE data LIKE '%12345%'  // Searches for substring
```

**Used by**: When processing transactions manually or during import
**Result**: Simple substring matching, no scoring, inconsistent results

### System B (Better, Not Fully Used): Keyword-Based Learning
```php
// In build_partner_keyword_data.php
// Extracts keywords from settled transactions and stores individually
INSERT INTO bi_partners_data 
  (partner_id, partner_type, partner_detail_id, data, occurrence_count)
VALUES ($id, $type, $detail, "acme", 1);  // Single keyword
ON DUPLICATE KEY UPDATE occurrence_count = occurrence_count + 1;

// Search scores by keyword clustering
$score = $base_occurrence × (1 + (keyword_match_count - 1) × CLUSTERING_FACTOR)
```

**Used by**: Manual script to build data from settled transactions
**Result**: Pattern learning through occurrence_count, multi-keyword scoring, high accuracy

---

## What User Is Saying

**Stop mixing both systems!** Use ONLY System B (keywords with learning):

1. **Pattern-based matching** - not type-based
   - "Pre-Auth Debit;Group benefit" = pattern → matches QE #2
   - "Interest" = pattern → matches QE Interest
   - "Internet Transfer" = pattern → matches Bank Transfer + customer context

2. **Single search across ALL partner types**
   - Find all keywords in bi_partners_data
   - Score and rank by occurrence_count
   - Return highest match (regardless of type)
   - Let the data tell us the type!

3. **Learning from successful matches**
   - User selects "Internet Transfer" = Customer XYZ
   - Save → increment occurrence_count for keywords [internet, transfer] for Customer XYZ
   - Next time more likely to match

4. **Context-aware special cases**
   - E-Transfer receiving: likely has customer name
   - Interest/Interest Paid: fixed QE mappings
   - GL score ≥80: suggest "Partner Match" type

---

## Current Data State in `bi_partners_data`

### Observed Columns:
```sql
CREATE TABLE bi_partners_data (
    partner_id INT(11),           -- Supplier ID, Customer ID, QE code, Bank Account ID
    partner_detail_id INT(11),    -- -1 for supplier, branch_code for customer, 0 for QE/BT
    partner_type INT(11),         -- PT_SUPPLIER(1), PT_CUSTOMER(2), ST_BANKTRANSFER(4), etc
    data VARCHAR(256),            -- MIXED FORMAT (problem!)
    occurrence_count INTEGER,     -- Only populated by build_partner_keyword_data.php
    updated_ts TIMESTAMP
);
```

### Data Format Issues:
```
System A (LIKE search) - Full values, no occurrence_count
partner_id=1, data="ACME-CORP INVOICE", occurrence_count=NULL

System B (Keyword search) - Individual keywords, with occurrence_count  
partner_id=1, data="acme", occurrence_count=5
partner_id=1, data="corp", occurrence_count=3
partner_id=1, data="invoice", occurrence_count=2

Mixed = CHAOS (searches fail, scoring impossible)
```

---

## Proposed Solution Flow

### Transaction Arrives
```
Bank data:
  - otherBankAccount: "12345-67-890"
  - otherBankAccountName: "ACME Corporation"
  - transactionTitle: "Pre-Auth Debit;Group benefit 2025"
  - transactionDC: "D" (Debit)
  - amount: 1500.00
  - matching_trans[]: [{ score: 85, type: 1, no: 5001 }, ...]
  
Step 1: Extract Search Pattern
  ├─ Keyword extraction: "Pre-Auth Debit", "Group", "benefit", "2025"
  │   MINUS stopwords: ["pre-auth", "group", "benefit", "2025"] = 4 keywords
  ├─ Bank account: "12345"
  └─ Bank account name keywords: ["acme", "corporation"]
  
  Combined search text = "pre-auth group benefit 2025 acme corporation"

Step 2: Score Pattern Against bi_partners_data
  SELECT partner_id, partner_type, partner_detail_id, data, occurrence_count
  FROM bi_partners_data
  WHERE data IN ('pre-auth', 'group', 'benefit', '2025', 'acme', 'corporation')
  
  Results (before clustering):
  ├─ QE#12 (Interest Paid): [benefit(0)] = 0 points
  ├─ QE#2 (Group Benefit): [group(8), benefit(12), 2025(3)] = 23 points
  ├─ Customer ACME: [acme(45), corporation(20)] = 65 points
  ├─ ST_BANKTRANSFER to [acme-bank]: [acme(8), transfer(0)] = 8 points
  └─ QE#5 (Quick Entry): [pre-auth(4)] = 4 points
  
Step 3: Apply Clustering Bonus
  QE#2 matches 3 keywords:
    score = 23 × (1 + (3-1) × 0.2) = 23 × 1.4 = 32.2 points
    
  Customer ACME matches 2 keywords:
    score = 65 × (1 + (2-1) × 0.2) = 65 × 1.2 = 78 points
    
  After clustering: Customer ACME still leads (78 > 32)
  
Step 4: Integrate GL Score?
  matching_trans score = 85 (high!)
  GL Type = ST_JOURNAL or ST_CUSTPAYMENT?
  Maybe consider this as "Partner Match" suggestion?
  
Step 5: Confidence Calculation
  Customer ACME:
    - confidence = 78 / 85 (normalized) = ~92%
    - EXCEEDS auto_threshold (75%) → AUTO-SELECT
    
Step 6: Display & Learn
  ├─ UI shows: "Partner: Customer → ACME Corporation (92% confidence)"
  ├─ User confirms (or corrects)
  ├─ On save: 
  │   UPDATE bi_partners_data SET occurrence_count = occurrence_count + 1
  │   WHERE partner_id = ACME AND (data='acme' OR data='corporation')
  └─ Next debit from ACME will score even higher!
```

---

## Architecture Events & Flow

### A. Transaction Processing Flow (Current vs Proposed)

**Current (Wrong - Type-First)**
```
1. displayPartnerType() checks: Is it Debit or Credit?
2. If Debit → displaySupplierPartnerType()
   └─ search_partner_by_bank_account(PT_SUPPLIER, account)
     └─ First supplier with account match returned (no scoring)
3. If not found → User selects manually
4. If Credit → displayCustomerPartnerType()
   └─ search_partner_by_bank_account(PT_CUSTOMER, account)
     └─ First customer with account match returned (no scoring)
5. If neither works → displayQuickEntryPartnerType()
   └─ Manual selection
```

**Proposed (Right - Pattern-First)**
```
1. Extract pattern from transaction
   └─ build_search_text(otherBankAccountName, transactionTitle, memo)
   
2. Search bi_partners_data ACROSS ALL TYPES
   └─ search_partner_by_keywords(NULL, search_text, 10)
      (NULL = no type filter!)
   
3. Score results with clustering bonus
   └─ Candidates ranked by confidence
   
4. Auto-select if confidence ≥ 75%
   └─ partnerType determined by result.partner_type
   └─ partnerId set from result.partner_id
   
5. Display suggestions if confidence < 75%
   └─ Top 5 candidates shown
   └─ User can select
   
6. On save: Learn
   └─ increment_keyword_occurrence() for selected partner
   └─ bi_partners_data.occurrence_count += 1
```

### B. Special Cases

```php
// Early check before pattern matching
function checkSpecialCases($transactionTitle, $transactionDC, $memo) {
    // Interest
    if (preg_match('/interest/i', $transactionTitle . $memo)) {
        $qe = ($transactionDC == 'C') ? QE_INTEREST_EARNED : QE_INTEREST_PAID;
        return match_quick_entry($qe);  // Auto-match
    }
    
    // E-Transfer (context aware)
    if (preg_match('/e-transfer|etransfer|interac/i', $transactionTitle)) {
        if ($transactionDC == 'C') {
            // Receiving: likely has customer name, try Customer first
            return search_by_pattern($text_without_etransfer, [PT_CUSTOMER, ST_BANKTRANSFER]);
        } else {
            // Sending: try Bank Transfer or Quick Entry
            return search_by_pattern($text_without_etransfer, [ST_BANKTRANSFER, PT_QE]);
        }
    }
    
    // Bank charges/fees
    if (preg_match('/bank\s+charge|monthly\s+fee|service\s+charge/i', $transactionTitle)) {
        return match_quick_entry(QE_BANK_CHARGES);
    }
    
    return null;  // Fall through to pattern matching
}
```

### C. GL Score Integration

```php
// When matching_trans score is high enough
function considerGLMatchSuggestion($matching_trans, $pattern_match) {
    if (empty($matching_trans)) return false;
    
    $top_gl = $matching_trans[0];  // Already sorted by score
    
    if ($top_gl['score'] >= 80) {
        // Strong GL match exists
        // Option 1: Auto-select if no pattern match
        if (empty($pattern_match)) {
            return suggest_partner_match_type($matching_trans);
        }
        
        // Option 2: Boost confidence if pattern match exists
        if ($pattern_match['confidence'] < 80) {
            $pattern_match['confidence'] = min(80, $pattern_match['confidence'] + 20);
            $pattern_match['note'] = "Confidence boosted by GL match";
        }
    }
    
    return false;
}
```

---

## Implementation Phases

### Phase 1: Fix Data Format (Migrate to Keywords Only)
- [ ] Audit current bi_partners_data (which rows have full values vs keywords?)
- [ ] Run `build_partner_keyword_data.php` to backfill keywords from settled transactions
- [ ] Deprecate System A: `search_partner_by_bank_account()`
- [ ] Consider migration script to convert old full-text values to keywords

### Phase 2: Refactor Matching Logic (Pattern-First)
- [ ] Create `PatternMatcher.php`:
  - `->extractPattern($transaction): string`
  - `->search($pattern, $confidence_threshold): array`
  - `->checkSpecialCases($transaction): ?array`
  - `->integrateGLScore($matching_trans, $candidates): array`

- [ ] Update `PROD/class.ViewBiLineItems.php::display_right()`:
  - Call PatternMatcher EARLY (before displayPartnerType() switch)
  - Store matched suggestion with confidence
  - Pass to display methods

- [ ] Update display methods to use suggestion:
  - `displaySupplierPartnerType()` - ignore if pattern suggests otherwise
  - `displayCustomerPartnerType()` - ignore if pattern suggests otherwise
  - Special-case handling for suggestions

### Phase 3: Add Learning Mechanism
- [ ] Track selected partner on form submission
- [ ] When saving transaction in `process_statements.php`:
  - Extract keywords from final selection
  - Call `increment_keyword_occurrence()` for each keyword
  - `bi_partners_data.occurrence_count` increases
  
- [ ] Optional: Show learning stats to user
  - "This pattern is now learned (confidence will increase next time)"

### Phase 4: Test & Tune
- [ ] Run existing test transactions
- [ ] Measure auto-match rate before/after
- [ ] Adjust CLUSTERING_FACTOR if needed (0.1-0.3 range)
- [ ] Test special cases (Interest, E-Transfer, Bank Charges)
- [ ] Verify GL score integration

---

## Database: What Needs to Change

### Current Chaos:
```sql
-- Full values
INSERT INTO bi_partners_data VALUES (1, -1, 1, "ACME-CORP", NULL);

-- Keywords
INSERT INTO bi_partners_data VALUES (1, -1, 1, "acme", 5);
INSERT INTO bi_partners_data VALUES (1, -1, 1, "corp", 3);

-- Mixed = search breaks!
SELECT * WHERE data LIKE '%acme%'  -- finds keywords only
SELECT * WHERE data = 'acme'       -- finds keywords only
```

### Proposed (Clean):
```sql
-- ONLY keywords with occurrence counts
INSERT INTO bi_partners_data VALUES (1, -1, 1, "acme", 1)
  ON DUPLICATE KEY UPDATE occurrence_count = occurrence_count + 1;

INSERT INTO bi_partners_data VALUES (1, -1, 1, "corp", 1)
  ON DUPLICATE KEY UPDATE occurrence_count = occurrence_count + 1;

-- Search is clean:
SELECT * WHERE data IN ('acme', 'corp')
  ORDER BY occurrence_count DESC;
```

### Migration Required:
```sql
-- Backup old data
CREATE TABLE bi_partners_data_backup AS SELECT * FROM bi_partners_data;

-- Delete old full-value entries
DELETE FROM bi_partners_data WHERE occurrence_count IS NULL;

-- Run build_partner_keyword_data.php
-- Verify new keyword entries exist

-- Confirm data quality
SELECT COUNT(*) as total_entries,
       COUNT(CASE WHEN occurrence_count > 0 THEN 1 END) as keyword_entries,
       COUNT(CASE WHEN occurrence_count IS NULL THEN 1 END) as old_entries
FROM bi_partners_data;
```

---

## Key Changes Summary

| Aspect | Current (Wrong) | Proposed (Right) |
|--------|-----------------|-----------------|
| **Primary Key** | Partner type (assumed) | Transaction pattern (keywords) |
| **Data Storage** | Full values + keywords mixed | Keywords only |
| **Search Scope** | One type at a time | All types simultaneously |
| **Scoring** | No scoring (LIMIT 1) | Full scoring with clustering |
| **Learning** | None | occurrence_count incremented |
| **Special Cases** | None | Interest, E-Transfer, Charges |
| **GL Integration** | Ignored | Used for confidence boost |
| **Auto-Select Threshold** | N/A (no scoring) | 75% confidence |
| **User Experience** | Manual select 80% of time | Auto-select 80% of time |
