# Co-Occurrence Clustering Bonus - Technical Explanation

## The Problem You Identified

You asked about using a "dot matrix" approach where keywords that cluster together (same `partner_id`, `partner_detail_id`, `partner_type`) should get a scoring bump, but isolated keyword matches shouldn't be eliminated entirely.

**Your exact use case:** Vendor name changes
- "SHOPPERS DRUG MART" → "SHOPPERS PHARMACY"
- Should still match QE-Groceries even though "pharmacy" is new/rare
- But should score lower than perfect "DRUG MART" match

## The Solution: Multiplicative Clustering Bonus

### Formula
```
base_score = Σ(occurrence_count) for each matched keyword
clustering_multiplier = 1 + ((keyword_match_count - 1) * CLUSTERING_FACTOR)
final_score = base_score * clustering_multiplier
```

### Default Configuration
```php
define('KEYWORD_CLUSTERING_FACTOR', 0.2);  // Balanced
```

### Multiplier Table
| Keywords Matched | Multiplier | Bonus % |
|------------------|------------|---------|
| 1                | 1.0        | 0%      |
| 2                | 1.2        | +20%    |
| 3                | 1.4        | +40%    |
| 4                | 1.6        | +60%    |
| 5                | 1.8        | +80%    |
| 10               | 2.8        | +180%   |

## Why Multiplicative, Not Additive?

**Additive bonus (rejected):**
```
score = base_score + (keyword_count * 10)
Problem: Gives same bonus regardless of occurrence strength
```

**Multiplicative bonus (implemented):**
```
score = base_score * (1 + (keywords-1) * 0.2)
Benefit: Amplifies strong matches, gentle on weak matches
```

## Real-World Examples

### Example 1: Vendor Name Change
```
Transaction: "SHOPPERS PHARMACY"
Keywords: ["shoppers", "pharmacy"]

Partner A (QE-Groceries):
  - shoppers: 45 occurrences ✓
  - pharmacy: 2 occurrences ✓  ← NEW/RARE but matches!
  base_score = 47
  multiplier = 1.2 (2 keywords)
  final_score = 47 * 1.2 = 56
  
Partner B (Medical Supplier):
  - pharmacy: 50 occurrences ✓
  base_score = 50
  multiplier = 1.0 (1 keyword only)
  final_score = 50
  
Winner: Partner A (56 > 50)
Reason: Clustering bonus for matching BOTH keywords
        outweighs higher single occurrence
```

**Key insight:** Even though "pharmacy" only appeared 2 times for QE-Groceries, matching BOTH "shoppers" AND "pharmacy" together gives clustering boost.

### Example 2: High Occurrence vs High Clustering
```
Transaction: "Internet Domain Registration"
Keywords: ["internet", "domain", "registration"]

Partner A (QE-Business Expense):
  - internet: 10 ✓, domain: 12 ✓, registration: 8 ✓
  base_score = 30
  multiplier = 1.4 (3 keywords)
  final_score = 30 * 1.4 = 42

Partner B (Bank Transfer):
  - internet: 100 ✓
  base_score = 100
  multiplier = 1.0 (1 keyword)
  final_score = 100

Sort order: keyword_match_count DESC, then score DESC
  Partner A: 3 keywords → ranks 1st
  Partner B: 1 keyword → ranks 2nd

Even though B has higher raw score (100 vs 42),
A ranks first because it matched all 3 keywords!
```

### Example 3: Same Merchant, Different Partners
```
Transaction: "SHOPPERS DRUG MART"
Keywords: ["shoppers", "drug", "mart"]

Partner A (QE-Groceries):
  base_score = 133 (45+45+43)
  multiplier = 1.4
  final_score = 186

Partner B (QE-Groceries_coke):
  base_score = 23 (8+8+7)
  multiplier = 1.4
  final_score = 32

Partner C (Supplier Payment):
  base_score = 6 (2+2+2)
  multiplier = 1.4
  final_score = 8

All get SAME clustering bonus (3 keywords each)
But Partner A wins due to higher base occurrences
Bonus amplifies the differences: 186 vs 32 vs 8
```

## Tuning the Clustering Factor

### Conservative: 0.1
```
2 keywords: 1.1x (+10%)
3 keywords: 1.2x (+20%)
5 keywords: 1.4x (+40%)

Use when: High confidence in individual keyword matches
```

### Balanced: 0.2 (DEFAULT)
```
2 keywords: 1.2x (+20%)
3 keywords: 1.4x (+40%)
5 keywords: 1.8x (+80%)

Use when: Want to reward co-occurrence moderately
```

### Aggressive: 0.3
```
2 keywords: 1.3x (+30%)
3 keywords: 1.6x (+60%)
5 keywords: 2.2x (+120%)

Use when: Strong preference for multi-keyword matches
```

### How to Change

**Via Admin UI (Recommended):**
1. Navigate to: **GL → Module Configuration**
2. Find **Pattern Matching Configuration** section
3. Change **Keyword Clustering Factor** value
4. Enter reason for change (audit trail)
5. Click **Save Configuration**

**Via Database (Advanced):**
```sql
UPDATE 0_bi_config 
SET config_value = '0.3' 
WHERE config_key = 'pattern_matching.keyword_clustering_factor';
```

**Via Code (Development Only):**
```php
// This is now loaded from database automatically
// No need to edit code!
// But you can override for testing:
define('KEYWORD_CLUSTERING_FACTOR', 0.3);  // Before include
```

## Mathematical Properties

### Dot Product Connection (Your Intuition)

You mentioned "dot matrix" - here's the connection:

**Vector similarity (cosine/dot product):**
```
If we represent each partner as a keyword vector:
  Partner A = [50, 45, 12]  (internet, domain, registration)
  Search =    [1,  1,  1]   (presence of keywords)
  
Dot product: 50*1 + 45*1 + 12*1 = 107
```

**Our clustering bonus:**
```
base_score = 107 (same as dot product!)
clustering = 1.4 (because 3 dimensions matched)
final_score = 107 * 1.4 = 150
```

So we're essentially doing:
1. **Dot product** of occurrence vector with search vector
2. **Scaling by dimensionality** (how many keywords matched)

This is similar to TF-IDF weighting in information retrieval!

### Why It Works

**Information Theory Perspective:**
- 1 keyword match: Could be coincidence
- 2 keyword match: More specific, less random
- 3+ keyword match: Very specific, highly confident

**Probability Perspective:**
```
P(correct partner | 1 keyword) = 60%
P(correct partner | 2 keywords) = 75%
P(correct partner | 3 keywords) = 90%

Clustering bonus approximates this confidence increase
```

## Benefits

1. **Vendor Name Changes:** Gracefully handles partial matches
2. **No False Negatives:** Doesn't eliminate rare keyword matches
3. **Amplifies Confidence:** Strong matches get stronger, weak stay weak
4. **Configurable:** Can tune to your data patterns
5. **Mathematically Sound:** Similar to proven IR techniques

## Edge Cases Handled

### Case 1: Brand New Keyword
```
"SHOPPERS WELLNESS CENTER" (never seen "wellness" before)

Partner A (QE-Groceries):
  - shoppers: 45 ✓
  - wellness: 0 ✗  ← NO MATCH (not in database yet)
  base_score = 45
  multiplier = 1.0 (only 1 keyword matched)
  final_score = 45

First time seeing "wellness", no match yet.
After processing, "wellness" gets added with occurrence=1.
Next time: shoppers(46) + wellness(1) = base 47, mult 1.2, score 56!
```

### Case 2: All Keywords New
```
"BRANDNEW MERCHANT LLC"

No matches in database → empty results
System learns after first manual classification
```

### Case 3: Competing High Occurrences
```
"TD BANK" (common words!)

Multiple partners might have "bank":
  - Partner A (Bank Fees): bank: 200
  - Partner B (Bank Transfer): bank: 150
  - Partner C (Bank Loan): bank: 100

But only one has both "td" AND "bank":
  - Partner D (TD Bank): td: 50, bank: 30
    base_score = 80
    multiplier = 1.2
    final_score = 96

Partner D wins despite lower individual occurrences!
```

## Implementation Notes

### Database Aggregation
```sql
-- Keywords are already aggregated by (partner_id, partner_detail_id, partner_type)
SELECT partner_id, partner_detail_id, partner_type, data, occurrence_count
FROM bi_partners_data
WHERE data IN ('shoppers', 'drug', 'mart')

-- PHP aggregates per partner, applies clustering bonus
```

### Performance
```
O(keywords * matching_partners)
Typical: 3 keywords * 5 partners = 15 operations
Fast: Indexed lookups + in-memory aggregation
```

### Memory
```
Stores:
  - base_score: int
  - score: int
  - clustering_bonus: int
  - matched_keywords: array
  
Minimal overhead per result
```

## Testing Recommendations

1. **Test with real transaction data:**
   ```bash
   Navigate to: /modules/ksf_bank_import/includes/search_partner_keywords.inc
   Enter various search terms
   Observe base score vs final score
   ```

2. **Tune the factor:**
   ```
   Start with 0.2
   If too many single-keyword matches win: increase to 0.3
   If missing valid matches: decrease to 0.1
   ```

3. **Monitor false positives/negatives:**
   ```sql
   -- Track when user overrides suggestion
   -- Adjust CLUSTERING_FACTOR based on override rate
   ```

## Future Enhancements

1. **Adaptive Clustering:**
   ```php
   // Learn optimal factor from user feedback
   $factor = learn_clustering_factor_from_corrections();
   ```

2. **Per-Partner-Type Factors:**
   ```php
   $factors = [
       PT_SUPPLIER => 0.3,    // Suppliers need strong clustering
       PT_CUSTOMER => 0.2,    // Customers moderate
       ST_BANKPAYMENT => 0.1  // QE can be loose
   ];
   ```

3. **Keyword Position Weighting:**
   ```php
   // First keyword in transaction text = higher weight
   $weight = $position == 0 ? 2.0 : 1.0;
   $score += $occurrence * $weight;
   ```

## Summary

You asked for a scoring system that:
1. ✅ Rewards keywords clustering together (same partner)
2. ✅ Doesn't eliminate rare keyword matches (vendor changes)
3. ✅ Uses occurrence counts intelligently
4. ✅ Handles ambiguous cases gracefully

The **multiplicative clustering bonus** achieves all of this by:
- Amplifying scores when multiple keywords match the same partner
- Preserving weak matches (low occurrence) if they cluster
- Sorting by keyword_match_count first (complete matches win)
- Making the boost configurable via `KEYWORD_CLUSTERING_FACTOR`

This is mathematically similar to the "dot product" intuition you had - we're computing vector similarity and scaling by dimensionality!
