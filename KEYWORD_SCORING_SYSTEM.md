# Keyword-Based Pattern Matching System

## Overview

This enhancement to the bank import system implements intelligent keyword-based pattern matching with occurrence scoring to improve transaction partner suggestions.

## Problem Statement

**Before:** Simple string matching couldn't distinguish between similar patterns:
- "Internet Transfer" → Bank Transfer
- "Internet Domain" → QE Business Expense

Both contain "Internet" but represent different transaction types.

**After:** Keyword-based scoring with occurrence counts:
- Breaks text into keywords: ["internet", "transfer"] vs ["internet", "domain"]
- Tracks how often each keyword appears with each partner
- Scores based on keyword matches + occurrence frequency

## Database Schema Changes

### Migration: `sql/add_occurrence_count_to_bi_partners_data.sql`

```sql
ALTER TABLE `0_bi_partners_data` 
    ADD COLUMN `occurrence_count` INTEGER DEFAULT 1;

-- Change unique constraint to allow multiple keywords per partner
ALTER TABLE `0_bi_partners_data` 
    ADD CONSTRAINT `idx_partner_keyword` UNIQUE(
        `partner_id`, 
        `partner_detail_id`, 
        `partner_type`, 
        `data`  -- data is now a single keyword, not full text
    );
```

**Before:**
```
partner_id | partner_detail_id | partner_type | data
10         | 0                 | 1            | "SHOPPERS DRUG MART"
```

**After:**
```
partner_id | partner_detail_id | partner_type | data      | occurrence_count
10         | 0                 | 1            | "shoppers"| 45
10         | 0                 | 1            | "drug"    | 45
10         | 0                 | 1            | "mart"    | 43
```

## Components

### 1. Migration Script
**File:** `sql/add_occurrence_count_to_bi_partners_data.sql`

Run this first to update the database schema.

### 2. Build Script
**File:** `build_partner_keyword_data.php`

Processes all existing settled transactions and builds keyword occurrence data.

**Usage:**
```
1. Navigate to: /modules/ksf_bank_import/build_partner_keyword_data.php
2. Click "Preview (Dry Run)" to see what would be processed
3. Click "Process All Transactions" to build keyword data
```

**What it does:**
- Reads all `bi_transactions` with `fa_trans_no > 0` (settled transactions)
- Extracts keywords from: account, accountName, transactionTitle, memo, merchant, category
- Filters out stopwords and short words (< 3 chars)
- For each keyword, increments occurrence count in `bi_partners_data`

### 3. Search Functions
**File:** `includes/search_partner_keywords.inc`

Functions for searching partners by keywords with scoring.

**Main Functions:**

#### `search_partner_by_keywords($partner_type, $search_text, $limit = 10)`
Returns scored matches for transaction text.

```php
$results = search_partner_by_keywords(PT_SUPPLIER, "Internet Domain Registration", 5);

// Returns:
// [
//     [
//         'partner_id' => 188,
//         'partner_type' => 1,
//         'base_score' => 107,             // Raw sum of occurrence counts
//         'score' => 150,                  // After clustering bonus (107 * 1.4)
//         'clustering_bonus' => 43,        // Bonus for co-occurrence
//         'matched_keywords' => ['internet', 'domain', 'registration'],
//         'total_occurrences' => 107,
//         'keyword_match_count' => 3,
//         'confidence' => 100.0            // Multi-factor percentage
//     ],
//     [
//         'partner_id' => 42,
//         'partner_type' => 1,
//         'base_score' => 30,
//         'score' => 30,                   // No bonus (only 1 keyword)
//         'clustering_bonus' => 0,
//         'matched_keywords' => ['internet'],
//         'total_occurrences' => 30,
//         'keyword_match_count' => 1,
//         'confidence' => 28.0
//     ],
//     ...
// ]
```

**Scoring Algorithm:**
```
base_score = Σ(occurrence_count) for each matched keyword
clustering_multiplier = 1 + ((keyword_match_count - 1) * 0.2)
final_score = base_score * clustering_multiplier

Sort by: 1) keyword_match_count DESC, 2) final_score DESC
Confidence = (keyword_coverage * 0.6) + (score_strength * 0.4)

Clustering Bonus Logic:
  1 keyword matched:  multiplier = 1.0 (no bonus)
  2 keywords matched: multiplier = 1.2 (+20% boost)
  3 keywords matched: multiplier = 1.4 (+40% boost)
  4 keywords matched: multiplier = 1.6 (+60% boost)
  5 keywords matched: multiplier = 1.8 (+80% boost)

Rationale: When multiple keywords cluster together for the same partner,
this is stronger evidence than individual keyword matches. Protects against
vendor name changes while rewarding consistent co-occurrence patterns.

Example 1: Perfect match with clustering bonus
  Search: "Internet Domain Registration"
  Keywords: ["internet", "domain", "registration"] = 3 keywords
  
  Partner A (QE-Business):
    - internet: 50 ✓, domain: 45 ✓, registration: 12 ✓
    base_score = 50+45+12 = 107
    keyword_match_count = 3
    clustering_multiplier = 1 + ((3-1) * 0.2) = 1.4
    final_score = 107 * 1.4 = 150
    keyword_coverage = 3/3 = 100%
    score_strength = 100% (top score)
    confidence = 100%
  
  Partner B (Bank Transfer):
    - internet: 30 ✓
    base_score = 30
    keyword_match_count = 1
    clustering_multiplier = 1.0 (no bonus)
    final_score = 30 * 1.0 = 30
    keyword_coverage = 1/3 = 33.3%
    score_strength = (30/150) * 100 = 20%
    confidence = (33.3 * 0.6) + (20 * 0.4) = 28%
  
  Result: Partner A (150 pts, 100%) >> Partner B (30 pts, 28%)
  Clustering bonus: Partner A gets +43 pts for having all 3 keywords together!

Example 2: Ambiguous case with clustering
  Search: "SHOPPERS DRUG MART"
  Keywords: ["shoppers", "drug", "mart"] = 3 keywords
  
  Partner A (QE-Groceries):
    - shoppers: 45, drug: 45, mart: 43 ✓✓✓
    base_score = 133
    clustering_multiplier = 1.4
    final_score = 133 * 1.4 = 186
    confidence = 100%
  
  Partner B (QE-Groceries_coke):
    - shoppers: 8, drug: 8, mart: 7 ✓✓✓
    base_score = 23
    clustering_multiplier = 1.4
    final_score = 23 * 1.4 = 32
    score_strength = (32/186) * 100 = 17.2%
    confidence = (100 * 0.6) + (17.2 * 0.4) = 66.9%
  
  Partner C (Supplier Payment):
    - shoppers: 2, drug: 2, mart: 2 ✓✓✓
    base_score = 6
    clustering_multiplier = 1.4
    final_score = 6 * 1.4 = 8
    score_strength = (8/186) * 100 = 4.3%
    confidence = (100 * 0.6) + (4.3 * 0.4) = 61.7%
  
  Result: All get same clustering bonus (3 keywords each)
  Partner A still wins due to higher occurrence counts
  But bonus amplifies the difference (186 vs 32 vs 8)

Example 3: Vendor name change protection
  Search: "SHOPPERS PHARMACY"  ← Changed from "DRUG MART"
  Keywords: ["shoppers", "pharmacy"] = 2 keywords
  
  Partner A (QE-Groceries):
    - shoppers: 45 ✓
    - pharmacy: 2 ✓  ← Rare, but matches!
    base_score = 47
    clustering_multiplier = 1.2
    final_score = 47 * 1.2 = 56
    keyword_coverage = 2/2 = 100%
    confidence = 100%
  
  Partner B (Medical Supplier):
    - pharmacy: 50 ✓
    base_score = 50
    clustering_multiplier = 1.0 (only 1 keyword)
    final_score = 50
    keyword_coverage = 1/2 = 50%
    score_strength = (50/56) * 100 = 89%
    confidence = (50 * 0.6) + (89 * 0.4) = 65.6%
  
  Result: Partner A (56 pts, 100%) > Partner B (50 pts, 66%)
  Even though "pharmacy" is rare for QE-Groceries, matching BOTH
  keywords gives it the clustering bonus, pushing it ahead!
  
  This is exactly what you wanted: Don't eliminate matches entirely,
  but reward co-occurrence clustering.

Example 4: High occurrence single match vs lower co-occurrence
  Search: "Internet Transfer TD Bank"
  Keywords: ["internet", "transfer", "bank"] = 3 keywords
  
  Partner A (Bank Transfer):
    - internet: 100 ✓
    base_score = 100
    clustering_multiplier = 1.0 (only 1 keyword)
    final_score = 100
    keyword_coverage = 1/3 = 33.3%
    confidence = (33.3 * 0.6) + (100 * 0.4) = 60%
  
  Partner B (QE-Bank Fees):
    - internet: 10 ✓, transfer: 8 ✓, bank: 12 ✓
    base_score = 30
    clustering_multiplier = 1.4
    final_score = 30 * 1.4 = 42
    keyword_coverage = 3/3 = 100%
    score_strength = (42/100) * 100 = 42%
    confidence = (100 * 0.6) + (42 * 0.4) = 76.8%
  
  Result: Partner A (100 pts, 60%) vs Partner B (42 pts, 77%)
  Partner A has higher raw score, but Partner B has higher confidence!
  Sort order: keyword_match_count first, so Partner B wins (3 > 1)
  This is correct - matching ALL keywords is stronger signal.
```

#### `get_suggested_partner($partner_type, $search_text)`
Convenience wrapper returning top match only.

```php
$suggestion = get_suggested_partner(PT_SUPPLIER, "SHOPPERS DRUG MART");
// Returns top-scoring partner or null
```

#### `extract_keywords_for_search($text)`
Tokenizes text into searchable keywords.

```php
$keywords = extract_keywords_for_search("Internet Domain Registration");
// Returns: ['internet', 'domain', 'registration']
```

## Keyword Extraction Logic

### Co-Occurrence Clustering Bonus

**Problem:** A partner with 1 keyword at high occurrence could beat a partner with 3 keywords at lower occurrence.

**Solution:** Apply multiplicative bonus when multiple keywords cluster together for the same partner.

**Visual Example:**
```
Search: "Internet Domain Registration"

Database state:
┌──────────────┬─────────────┬──────────────┬────────┬─────────────────┐
│ partner_id   │ partner_type│ data         │ occ    │ Interpretation  │
├──────────────┼─────────────┼──────────────┼────────┼─────────────────┤
│ 188          │ 1 (BP)      │ internet     │ 50     │ ◄─┐             │
│ 188          │ 1 (BP)      │ domain       │ 45     │ ◄─┤ CLUSTERED!  │
│ 188          │ 1 (BP)      │ registration │ 12     │ ◄─┘ 3 keywords  │
│ 42           │ 4 (BT)      │ internet     │ 100    │ ◄── ISOLATED    │
└──────────────┴─────────────┴──────────────┴────────┴─────────────────┘

Without clustering bonus:
  Partner 188: score = 50+45+12 = 107
  Partner 42:  score = 100
  Winner: Partner 42 (wrong! it only matched 1/3 keywords)

With clustering bonus (multiplier = 1 + (keywords-1)*0.2):
  Partner 188: base=107, mult=1.4, score = 150 ✓
  Partner 42:  base=100, mult=1.0, score = 100
  Winner: Partner 188 (correct! matched all keywords)
```

### Tuning the Bonus:**
```
Current formula: multiplier = 1 + ((keyword_count - 1) * CLUSTERING_FACTOR)

Conservative (0.1):  2kw=1.1, 3kw=1.2, 5kw=1.4  ← Subtle boost
Default (0.2):       2kw=1.2, 3kw=1.4, 5kw=1.8  ← Balanced
Aggressive (0.3):    2kw=1.3, 3kw=1.6, 5kw=2.2  ← Strong boost

Configuration:
  1. Navigate to: GL → Module Configuration (Bank Import)
  2. Find "Pattern Matching" section
  3. Adjust "Keyword Clustering Factor" (default: 0.2)
  4. Save with reason for audit trail

The setting is stored in database (bi_config table) and can be modified
in production without code changes.
```

### Tokenization
1. Convert to lowercase
2. Remove special characters (keep alphanumeric + spaces)
3. Split on whitespace

### Filtering
**Remove:**
- Words < 3 characters
- Common stopwords: the, and, or, for, to, from, in, on, at, by, with, of, as, is, was, be, are, were, been, has, have, had, do, does, did, will, would, could, this, that, these, those, it, its, an, a
- Generic banking terms: payment, transaction, transfer, deposit, withdrawal

**Keep:**
- Meaningful 3+ character words
- Business names, locations, categories

### Examples

| Input Text | Keywords Extracted |
|------------|-------------------|
| "SHOPPERS DRUG MART" | shoppers, drug, mart |
| "Internet Transfer - TD" | internet |
| "Internet Domain GoDaddy" | internet, domain, godaddy |
| "EPCOR UTILITIES INC" | epcor, utilities, inc |
| "Payment to Bell" | bell |

## Integration with Transaction Handlers

All 6 transaction handlers already call partner data update functions:

### Supplier & Customer
```php
// SupplierTransactionHandler.php
update_partner_data($partnerId, PT_SUPPLIER, null, $transaction['account']);

// CustomerTransactionHandler.php  
update_partner_data($partnerId, PT_CUSTOMER, $branchId, $transaction['memo']);
```

### Quick Entry & Bank Transfer
```php
// QuickEntryTransactionHandler.php
set_bank_partner_data($ourAccount['id'], $transType, $partnerId, $transactionTitle);

// BankTransferTransactionHandler.php
set_bank_partner_data($fromAccount, $transType, $toAccount, $memo);
```

### Manual & Matched
```php
// ManualSettlementHandler.php
set_partner_data($personType, $existingType, $personTypeId, $memo);

// MatchedTransactionHandler.php
set_partner_data($personType, $transType, $personTypeId, $memo);
```

## Usage Workflow

### 1. Initial Setup
```bash
# 1. Run migration
mysql database_name < sql/add_occurrence_count_to_bi_partners_data.sql

# 2. Build keyword data from existing transactions
# Navigate to: /modules/ksf_bank_import/build_partner_keyword_data.php
# Click "Process All Transactions"
```

### 2. During Transaction Import
As transactions are processed:
1. Handler calls `update_partner_data()` or `set_bank_partner_data()`
2. Keywords extracted from transaction text
3. Each keyword's occurrence count incremented in `bi_partners_data`

### 3. Suggesting Partners
When presenting new transaction for manual matching:
```php
// Get transaction text
$search_text = $transaction['account'] . ' ' . 
               $transaction['transactionTitle'] . ' ' . 
               $transaction['memo'];

// Get suggestions
$suggestions = search_partner_by_keywords(PT_SUPPLIER, $search_text, 5);

// Present to user as dropdown or recommendations
foreach ($suggestions as $suggestion) {
    echo "Partner {$suggestion['partner_id']} - Score: {$suggestion['score']}<br>";
    echo "Matched keywords: " . implode(', ', $suggestion['matched_keywords']) . "<br>";
}
```

## Testing

### Test the Search Function
Navigate to: `/modules/ksf_bank_import/includes/search_partner_keywords.inc`

This will run test searches and display results with scores.

### Test Cases

1. **Exact Match**
   - Search: "SHOPPERS DRUG MART"
   - Expected: High score for QE-Groceries partner

2. **Partial Match**
   - Search: "SHOPPERS"
   - Expected: Still matches QE-Groceries (lower score)

3. **Ambiguous Keywords**
   - Search: "Internet Transfer"
   - Expected: Bank Transfer partner scores highest
   
   - Search: "Internet Domain"
   - Expected: QE-Business Expense partner scores highest

4. **No Match**
   - Search: "BRAND NEW MERCHANT"
   - Expected: Empty results (no keyword matches)

## Benefits

1. **Disambiguation**: Distinguishes between similar patterns based on context
2. **Learning**: System gets smarter over time as occurrence counts increase
3. **Partial Matching**: Works even with incomplete or misspelled text
4. **Performance**: Indexed lookups on keywords (fast)
5. **Transparency**: Score shows confidence level to users
6. **Ambiguity Handling**: Returns multiple suggestions for same merchant/different partners
7. **No False Positives**: Only scores partners for their own keywords

## Handling Ambiguous Cases

### Problem: Same Merchant → Multiple Partners

Real-world example: **SHOPPERS DRUG MART**
- Most purchases → `QE-Groceries` (everyday items)
- Some purchases → `QE-Groceries_coke` (specific product tracking)
- Rare cases → `Supplier Payment` (split payments requiring invoice)

### Solution: Return All Suggestions with Confidence

```php
$results = search_partner_by_keywords(0, "SHOPPERS DRUG MART", 10);

// Returns ALL matching partners:
// [
//     ['partner_id' => 188, 'score' => 133, 'confidence' => 100.0],  // QE-Groceries
//     ['partner_id' => 189, 'score' => 23, 'confidence' => 17.3],   // QE-Groceries_coke
//     ['partner_id' => 52, 'score' => 6, 'confidence' => 4.5]       // Supplier Payment
// ]
```

### UI Recommendations

**Option 1: Auto-select with override**
```
✓ QE-Groceries (100% confidence) [Change ▼]
  └─ Other options: QE-Groceries_coke (17%), Supplier Payment (5%)
```

**Option 2: Show top 3**
```
Suggestions:
○ QE-Groceries          ████████████ 100%
○ QE-Groceries_coke     ██           17%
○ Supplier Payment      █            5%
```

**Option 3: Threshold-based**
```
if (confidence >= 80%) {
    // Auto-select, allow override
} else if (confidence >= 40%) {
    // Pre-select but highlight as uncertain
} else {
    // Show dropdown, no default
}
```

## Performance Considerations

- **Indexes Added:**
  - `idx_partner_type_data` (partner_type, data)
  - `idx_occurrence_count` (occurrence_count DESC)
  - `idx_partner_keyword` (UNIQUE on partner_id, partner_detail_id, partner_type, data)

- **Query Performance:**
  - Keyword extraction: O(n) where n = text length
  - Database lookup: Indexed scan on keywords (fast)
  - Scoring: In-memory aggregation (fast)

## Future Enhancements

1. **Weighting by Field:**
   - Keywords from `merchant` field: weight × 2.0
   - Keywords from `category` field: weight × 1.5
   - Keywords from `memo` field: weight × 1.0

2. **Phrase Matching:**
   - Track 2-word phrases: "drug mart", "internet transfer"
   - Higher weight for phrase matches

3. **Decay Function:**
   - Recent occurrences weighted higher
   - Add `last_occurrence` timestamp

4. **Machine Learning:**
   - Use occurrence patterns to train classifier
   - Predict partner type from keywords

5. **User Feedback:**
   - Track when user overrides suggestion
   - Adjust weights based on corrections

## Maintenance

### Rebuild Keywords
If pattern matching seems off:
```
1. Truncate bi_partners_data: DELETE FROM 0_bi_partners_data;
2. Re-run: build_partner_keyword_data.php
```

### Check Keyword Stats
```sql
-- Top keywords by occurrence
SELECT data, SUM(occurrence_count) as total
FROM 0_bi_partners_data
GROUP BY data
ORDER BY total DESC
LIMIT 50;

-- Partners with most keywords
SELECT partner_id, partner_type, COUNT(*) as keyword_count
FROM 0_bi_partners_data
GROUP BY partner_id, partner_type
ORDER BY keyword_count DESC;

-- Ambiguous keywords (same keyword → multiple partners)
SELECT data, COUNT(DISTINCT partner_id) as partner_count
FROM 0_bi_partners_data
GROUP BY data
HAVING partner_count > 1
ORDER BY partner_count DESC;
```

## Troubleshooting

**Problem:** Suggestions are not accurate

**Solutions:**
1. Check if migration was run: `SHOW COLUMNS FROM 0_bi_partners_data;`
2. Check if keywords were built: `SELECT COUNT(*) FROM 0_bi_partners_data;`
3. Verify stopwords aren't blocking good keywords
4. Adjust keyword length threshold (currently 3+ chars)

**Problem:** Performance issues

**Solutions:**
1. Verify indexes exist: `SHOW INDEX FROM 0_bi_partners_data;`
2. Limit number of keywords extracted (currently unlimited)
3. Add LIMIT to search queries
4. Consider caching frequent searches

## Related Files

- `sql/update.sql` - Installer schema (canonical)
- `sql/add_occurrence_count_to_bi_partners_data.sql` - Migration
- `build_partner_keyword_data.php` - Build script
- `includes/search_partner_keywords.inc` - Search functions
- `includes/pdata.inc` - Original partner data functions
- `src/Ksfraser/FaBankImport/handlers/*.php` - Transaction handlers

## License

MIT License - Copyright 2025 KSF
