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

### B. Multi-Factor Scoring (No Special Cases - Scoring Handles All)

Instead of special case logic, use sophisticated multi-factor scoring:

```php
/**
 * Calculate match score using multiple factors
 * 
 * Factors:
 * 1. Exact substring matches (strongest signal)
 * 2. Individual keyword matches (base signal) 
 * 3. Occurrence frequency (learned pattern strength)
 * 4. Recency weighting (recent matches are more relevant)
 * 5. Account number matching (if applicable)
 * 6. Co-occurrence clustering bonus
 */
function calculateMatchScore($transaction, $candidate) {
    $score = 0;
    $weights = [
        'substring' => 100,      // Exact substring very strong
        'keyword' => 10,         // Base keyword match
        'occurrence' => 0.5,     // Frequency multiplier
        'recency' => 0.1,        // Recent boost
        'account' => 80,         // Account match very strong
        'clustering' => 0.2      // Per additional keyword
    ];
    
    // Factor 1: Exact substring matches (e.g., "Credit Card" or "CIBC")
    $substrings = extract_substrings($transaction);
    foreach ($substrings as $substr) {
        if ($candidate['data'] === $substr) {
            $score += $weights['substring'];
        }
    }
    
    // Factor 2: Individual keywords
    $keywords = extract_keywords($transaction);
    $matched_keywords = 0;
    foreach ($keywords as $kw) {
        if ($candidate['data'] === $kw) {
            $score += $weights['keyword'];
            $matched_keywords++;
        }
    }
    
    // Factor 3: Occurrence frequency
    $score += $candidate['occurrence_count'] * $weights['occurrence'];
    
    // Factor 4: Recency - recent matches weighted higher
    $days_ago = (time() - strtotime($candidate['updated_ts'])) / 86400;
    $recency_factor = max(0.5, 1 - ($days_ago / 365));  // Decay over 1 year
    $score *= $recency_factor;
    
    // Factor 5: Account number match (exact match with other_bank_account)
    if ($candidate['account_number'] === $transaction['otherBankAccount']) {
        $score += $weights['account'];
    }
    
    // Factor 6: Clustering bonus (multiple keywords = stronger signal)
    if ($matched_keywords > 1) {
        $clustering_bonus = $matched_keywords - 1;
        $score += $score * $clustering_bonus * $weights['clustering'];
    }
    
    return round($score, 2);
}
```

**This approach:**
- ✓ "Interest" learns to match QE_INTEREST (not hard-coded)
- ✓ "Credit Card" + account number → learns CC pattern
- ✓ "Square Up" + recency + account → quickly learns that CIBC = yours, Manulife = Marcia's
- ✓ Manual CC payments: 2 source accounts → learns both, recency prioritizes most recent
- ✓ Ad-hoc payments: occasional 3rd account → scores lower until used frequently
- ✓ No hard-coded logic = infinitely extensible

### C. GL Score Integration

```php
// GL Score is another factor in multi-factor scoring
// Not special-case handling, just another signal

function integrateGLScore($transaction, $pattern_score, $matching_trans) {
    if (empty($matching_trans)) {
        return $pattern_score;
    }
    
    $top_gl = $matching_trans[0];  // Already sorted by score
    $gl_score = $top_gl['score'] ?? 0;  // 0-100 scale
    
    // GL score is normalized and weighted like other factors
    $weights = [
        'pattern' => 0.6,   // Pattern matching is primary (60%)
        'gl' => 0.4         // GL matching is secondary (40%)
    ];
    
    // Combine scores
    $combined = ($pattern_score * $weights['pattern']) + 
                ($gl_score * $weights['gl']);
    
    return round($combined, 2);
}
```

**Advantage**: When GL score is high, it naturally boosts the final match score.
When GL score is low, pattern-based scoring dominates. No special cases needed.

---

## Implementation Phases

### Phase 1: Fix Data Format & Schema (Migrate to Keywords + Substrings + Recency)
- [ ] Audit current bi_partners_data (which rows have full values vs keywords?)
- [ ] Add columns to bi_partners_data:
  - `data_type` ENUM('substring', 'keyword', 'account')
  - `last_matched_ts` TIMESTAMP (for recency weighting)
- [ ] Run `build_partner_keyword_data.php` to backfill keywords from settled transactions
- [ ] Extract substrings from transaction titles and store with high occurrence counts
- [ ] Set initial `last_matched_ts` to transaction date
- [ ] Deprecate System A: `search_partner_by_bank_account()`
- [ ] Clean data: migrate old full-text values to keyword entries

### Phase 2: Implement Multi-Factor Scoring Engine (No Special Cases)
- [ ] Create `ScoringEngine.php`:
  - `->extractKeywords($text): array`
  - `->extractSubstrings($text): array` (e.g., "Credit Card", "Pre-Auth", "Square Up")
  - `->calculateScore($transaction, $candidate): float`
    - Factor 1: Exact substring matches (+100)
    - Factor 2: Individual keyword matches (+10 each)
    - Factor 3: Occurrence frequency multiplier (×0.5)
    - Factor 4: Recency weighting (1.0 to 0.5 over 1 year)
    - Factor 5: Account number match (+80)
    - Factor 6: Clustering bonus (×0.2 per additional keyword)
  
- [ ] Create `PatternMatcher.php`:
  - `->search($transaction, $threshold): array` (uses ScoringEngine)
  - Returns ranked candidates with scores
  - No type filtering (searches all types)
  - No special-case logic (all patterns learned)

- [ ] Database queries for ScoringEngine:
  ```sql
  -- Get all matches for transaction keywords/substrings
  SELECT * FROM bi_partners_data
  WHERE data IN (keywords/substrings)
  ORDER BY data_type DESC, occurrence_count DESC;
  ```

### Phase 2b: Add Recency Learning
- [ ] Track `last_matched_ts` for each partner
- [ ] On successful match confirmation:
  ```php
  UPDATE bi_partners_data 
  SET last_matched_ts = NOW(),
      occurrence_count = occurrence_count + 1
  WHERE partner_id = X AND partner_type = Y AND data = Z;
  ```
- [ ] This enables:
  - Recent patterns score higher (your Square deposits > Marcia's)
  - Patterns decay slowly if unused (if you stop using specific CC account)
  - Account clustering learns mode shifts (primary 2nd, occasional 3rd)

### Phase 3: Refactor Matching Logic (Pattern-First, No Types Pre-Assumed)
- [ ] Update `PROD/class.ViewBiLineItems.php::display_right()`:
  - Call PatternMatcher EARLY (before any type-specific display)
  - Store best match with confidence score
  - Pass to display methods
  
- [ ] Update display methods:
  - All methods now receive pre-computed match suggestion
  - `displaySupplierPartnerType()` - shows suggestion if type matches, otherwise shows why
  - `displayCustomerPartnerType()` - same
  - `displayBankTransferPartnerType()` - same
  - Only auto-select if confidence >= 75% (no manual override except user choice)

- [ ] If pattern suggests different type than data suggests:
  - Show suggestion prominently
  - User can accept or override
  - Either way, learning is recorded

### Phase 4: Test, Tune & Verify Multi-Factor Scoring
- [ ] Process real transactions with multi-factor scoring enabled
- [ ] Run test suite with known patterns (Interest, CC payments, Square Up, E-Transfer)
- [ ] Measure auto-match rate before/after
- [ ] Tune weights and multipliers:
  - Substring bonus: 100 (adjust if too aggressive)
  - Keyword base: 10 (adjust if too weak)
  - Occurrence multiplier: 0.5 (frequency sensitivity)
  - Recency decay: 365 days (adjust if learning is stale)
  - Account match: 80 (account very strong signal)
  - Clustering bonus: 0.2 per extra keyword
- [ ] Verify recency scoring differentiates:
  - CIBC Square deposits (yours, frequent) vs Manulife (Marcia's, rare)
  - Primary CC account vs occasional ad-hoc account
- [ ] Verify substring + keyword + recency handles:
  - Interest payments (patterns learned automatically)
  - CC payments from specific account (account + pattern combo)
  - Pre-Auth CC (same account, learns quickly)
- [ ] No manual special cases maintained

---

## Data Storage: Substrings + Keywords + Recency

Unlike System B which only stores keywords, enhanced System B stores multiple data types:

```sql
CREATE TABLE bi_partners_data (
    partner_id INT(11),
    partner_detail_id INT(11),
    partner_type INT(11),
    data VARCHAR(256),              -- Can be substring OR keyword
    data_type ENUM('substring','keyword'),  -- NEW: Differentiates storage type
    occurrence_count INTEGER,       -- How many times matched
    last_matched_ts TIMESTAMP,      -- NEW: When last successfully matched (for recency)
    updated_ts TIMESTAMP
);
```

**Examples of stored patterns:**
```
Substring matches (strong signals, occurrence counts naturally high):
partner_id=12 (QE-Interest-Paid), data="interest paid", data_type=substring, occurrence_count=47
partner_id=12 (QE-Interest-Paid), data="interest earned", data_type=substring, occurrence_count=31

partner_id=4 (ST_BANKTRANSFER-to-CIBC), data="Credit Card", data_type=substring, occurrence_count=89
partner_id=4 (ST_BANKTRANSFER-to-CIBC), data="cibc", data_type=keyword, occurrence_count=215

Keyword matches (base signal):
partner_id=4 (ST_BANKTRANSFER-to-CIBC), data="credit", data_type=keyword, occurrence_count=92
partner_id=4 (ST_BANKTRANSFER-to-CIBC), data="card", data_type=keyword, occurrence_count=88

Account-specific learning:
partner_id=4 (ST_BANKTRANSFER-to-CIBC), data="12345678", data_type=account, occurrence_count=193

Recency helps differentiation:
partner_id=1001 (Square-Up-to-FHS), data="square up", data_type=substring, 
  occurrence_count=47, last_matched_ts=2026-04-10 (YOUR recent Square deposits)

partner_id=2045 (Square-Up-to-Marcia), data="square up", data_type=substring,
  occurrence_count=12, last_matched_ts=2026-04-01 (Marcia's occasional deposits)
  
-- When Square Up appears next:
-- Score 1: 47 × (1.0) = 47 (you, recent)
-- Score 2: 12 × (0.8) = 9.6 (Marcia, less recent)
-- YOUR account wins!
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
