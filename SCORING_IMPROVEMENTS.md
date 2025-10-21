# Scoring Algorithm Improvements

## Changes Made (2025-10-20)

### 1. Removed Arbitrary +10 Bonus

**Before:**
```php
$score += $occurrence_count + 10;  // Why +10? Arbitrary!
```

**After:**
```php
$score += $occurrence_count;  // Pure occurrence-based scoring
```

**Rationale:** 
- The +10 bonus was arbitrary and could skew results
- Pure occurrence counts reflect actual historical patterns
- If a keyword appears 50 times with Partner A vs 5 times with Partner B, Partner A should score 50 vs 5, not 60 vs 15

### 2. Fixed False Positive Problem

**Problem:** Search "Internet Search" could score Partner A for "internet" keyword even though Partner A never had "search" keyword.

**Before:**
```
Search: "Internet Search"
Keywords: ["internet", "search"]

Partner A: has keyword "internet" (50)
Partner B: has keyword "search" (20)

Both partners scored, but neither has BOTH keywords!
```

**After:**
```php
// Each partner only scored for ITS OWN keywords
// Aggregation happens per-partner, not globally
```

**Result:** Each partner is only scored for keywords it actually has, preventing cross-contamination.

### 3. Added Keyword Match Count Priority

**Before:** Sorted by score only

**After:** 
```php
usort($partner_scores, function($a, $b) {
    // First: More keywords matched = better
    if ($a['keyword_match_count'] != $b['keyword_match_count']) {
        return $b['keyword_match_count'] - $a['keyword_match_count'];
    }
    // Then: Higher score = better
    return $b['score'] - $a['score'];
});
```

**Rationale:**
- Partner matching 3 keywords (even with low counts) is better than partner matching 1 keyword (even with high count)
- "SHOPPERS DRUG MART" matching all 3 words is more confident than just matching "SHOPPERS"

### 4. Added Confidence Score

**New field:** `confidence` (percentage of top score)

```php
$partner['confidence'] = round(($partner['score'] / $top_score) * 100, 1);
```

**Usage:**
```
Partner A: score=100, confidence=100%  (top match)
Partner B: score=50,  confidence=50%   (half as likely)
Partner C: score=10,  confidence=10%   (unlikely but possible)
```

**UI Benefit:** User can see relative likelihood at a glance.

## Problem: Shoppers Example

### Scenario
Same merchant can map to different partners:
- **SHOPPERS DRUG MART** → QE-Groceries (most common)
- **SHOPPERS DRUG MART** → QE-Groceries_coke (specific tracking)
- **SHOPPERS DRUG MART** → Supplier Payment (split payment invoices)

### Before (Old Simple Matching)
```php
search_partner_data_by_needle("SHOPPERS");
// Returns: First match found (unpredictable)
// User: Frustrated when wrong partner suggested
```

### After (Keyword Scoring)
```php
search_partner_by_keywords(0, "SHOPPERS DRUG MART", 10);

// Returns ALL matches, sorted by likelihood:
// [
//     [partner_id=188, score=133, confidence=100%, keywords=3],  // QE-Groceries
//     [partner_id=189, score=23,  confidence=17%,  keywords=3],  // QE-Groceries_coke
//     [partner_id=52,  score=6,   confidence=5%,   keywords=3]   // Supplier Payment
// ]
```

**User Experience:**
1. System suggests **QE-Groceries** (100% confidence) - auto-selected
2. User sees alternatives are available
3. If this is a special case (split payment), user can override
4. Next time user selects Supplier Payment, that occurrence count increases

## Problem: "Internet Search" vs "Internet Domain"

### The Challenge
Both contain "internet" but represent different transaction types.

### Test Case 1: "Internet Search"
```
Keywords: ["internet", "search"]

Partner A (??-???):
  - internet: 10 occurrences ✓
  - search: 8 occurrences ✓
  keyword_match_count = 2, score = 18, confidence = 100%

Partner B (BT-Transfer):
  - internet: 50 occurrences ✓
  keyword_match_count = 1, score = 50, confidence = (50/18)*100 = ???
```

**Wait, this reveals a bug!** If Partner B has higher score but fewer keywords, confidence can be >100%.

Let me fix this:

### Fixed Confidence Calculation

**Problem:** Confidence should reflect "likelihood of being correct", not just "percentage of top score"

**Better approach:**
```php
// Calculate confidence as percentage of total score pool
$total_score = array_sum(array_column($partner_scores, 'score'));
foreach ($partner_scores as &$partner) {
    $partner['confidence'] = $total_score > 0 
        ? round(($partner['score'] / $total_score) * 100, 1)
        : 0;
}
```

**Or:** Keep current (percentage of top) but explain that lower matches can have inflated confidence.

**Or:** Use keyword_match_count as primary signal:
```php
// Confidence based on keyword coverage
$search_keyword_count = count($keywords);
$partner['confidence'] = round(
    ($partner['keyword_match_count'] / $search_keyword_count) * 100, 
    1
);
```

## Recommendation: Multi-Factor Confidence

```php
// Calculate confidence based on:
// 1. Keyword coverage (what % of search keywords matched)
// 2. Score strength (what % of top score)
$search_keyword_count = count($keywords);
$top_score = $partner_scores[0]['score'];

foreach ($partner_scores as &$partner) {
    $keyword_coverage = ($partner['keyword_match_count'] / $search_keyword_count) * 100;
    $score_strength = ($partner['score'] / $top_score) * 100;
    
    // Weighted average: 60% keyword coverage, 40% score strength
    $partner['confidence'] = round(
        ($keyword_coverage * 0.6) + ($score_strength * 0.4),
        1
    );
}
```

### Example with Multi-Factor Confidence

Search: "Internet Domain Registration Shopify"
Keywords: ["internet", "domain", "registration", "shopify"] = 4 keywords

**Partner A (QE-Business Expense):**
- Matched: internet(50), domain(45), registration(12), shopify(8)
- keyword_match_count = 4/4 = 100%
- score = 115
- If top score, score_strength = 100%
- **confidence = (100 * 0.6) + (100 * 0.4) = 100%**

**Partner B (Bank Transfer):**
- Matched: internet(30)
- keyword_match_count = 1/4 = 25%
- score = 30
- score_strength = (30/115) * 100 = 26%
- **confidence = (25 * 0.6) + (26 * 0.4) = 25.4%**

Much better! Partner B's confidence reflects it only matched 1 keyword.

## Updated Return Format

```php
[
    'partner_id' => int,
    'partner_detail_id' => int,
    'partner_type' => int,
    'score' => int,              // Sum of occurrence counts for matched keywords
    'matched_keywords' => array, // Which keywords matched
    'keyword_match_count' => int,// How many keywords matched
    'total_occurrences' => int,  // Sum of occurrences (same as score)
    'confidence' => float        // 0-100, multi-factor confidence score
]
```

## Summary of Issues & Fixes

| Issue | Before | After |
|-------|--------|-------|
| Arbitrary bonus | +10 per keyword | Pure occurrence counts |
| False positives | Partner scored for other partners' keywords | Only scored for own keywords |
| Sort order | Score only | Keyword count, then score |
| Ambiguity | First match wins | All matches returned, ranked |
| Confidence | Not shown | Multi-factor percentage |
| User experience | Guess which partner | See likelihood + alternatives |

## Next Steps

1. ✅ Remove +10 bonus
2. ✅ Add keyword_match_count field
3. ✅ Sort by keyword count first
4. ✅ Add basic confidence score
5. ⏳ Implement multi-factor confidence (optional)
6. ⏳ Add UI threshold logic (auto-select if >80%, etc.)
7. ⏳ Track user overrides to improve scoring

