# Pattern Matching Configuration - Quick Reference

## 🚀 Quick Start

### Install
```bash
mysql your_database < sql/add_pattern_matching_config.sql
```

### Access
**GL → Module Configuration → Pattern Matching Configuration**

### Settings

| Setting | Default | Range | Description |
|---------|---------|-------|-------------|
| **Keyword Clustering Factor** | 0.2 | 0-1 | Bonus per additional keyword |
| **Min Confidence Threshold** | 30% | 0-100% | Filter low-confidence matches |
| **Max Suggestions** | 5 | 1-20 | Number of results to show |
| **Min Keyword Length** | 3 | 2-10 | Minimum chars to index |

---

## 🎯 When to Adjust Clustering Factor

### Current Value: 0.2 (Balanced)

#### ⬆️ Increase to 0.3 (Aggressive)
**When:**
- Single keywords with high occurrence are winning incorrectly
- Need stronger preference for complete keyword matches
- Example: "Internet" (100 occurrences) beating "Internet Domain Registration" (30 each)

**Effect:**
- More weight on matching ALL keywords
- 3 keywords: ×1.6 bonus (was ×1.4)
- 5 keywords: ×2.2 bonus (was ×1.8)

#### ⬇️ Decrease to 0.1 (Conservative)
**When:**
- Missing valid partial matches
- System too strict on keyword count
- Example: "SHOPPERS" alone not matching "SHOPPERS DRUG MART"

**Effect:**
- Less weight on keyword count
- 3 keywords: ×1.2 bonus (was ×1.4)
- 5 keywords: ×1.4 bonus (was ×1.8)

#### ✅ Keep at 0.2 (Recommended)
**When:**
- Suggestions are mostly correct
- Good balance between precision and recall
- Users rarely override suggestions

---

## 📊 Effect on Scoring

### Example: "Internet Domain Registration"

| Factor | 3-keyword Match | 1-keyword Match | Winner |
|--------|-----------------|-----------------|--------|
| **0.1** | base=30 × 1.2 = **36** | base=100 × 1.0 = **100** | ❌ 1-keyword |
| **0.2** | base=30 × 1.4 = **42** | base=100 × 1.0 = **100** | ❌ 1-keyword |
| **0.3** | base=30 × 1.6 = **48** | base=100 × 1.0 = **100** | ❌ 1-keyword |

**But:** Sort order is keyword_match_count first!
- 3-keyword match ranks 1st (even with lower score)
- 1-keyword match ranks 2nd
- Confidence reflects this: 3-kw = 100%, 1-kw = 60%

### Real Impact: Ambiguous Cases

```
"SHOPPERS DRUG MART"

Partner A (QE-Groceries):      base=133 → 0.1: 159, 0.2: 186, 0.3: 213
Partner B (QE-Groceries_coke): base=23  → 0.1: 28,  0.2: 32,  0.3: 37
Partner C (Supplier Payment):  base=6   → 0.1: 7,   0.2: 8,   0.3: 10

Gap at 0.1: 159 vs 28 = 5.7x
Gap at 0.2: 186 vs 32 = 5.8x
Gap at 0.3: 213 vs 37 = 5.8x

Conclusion: Clustering factor affects absolute scores but 
relative ranking stays similar (all have 3 keywords).
```

---

## 🔧 Change Workflow

### Via UI (Recommended)
1. GL → Module Configuration
2. Pattern Matching → Keyword Clustering Factor
3. Change value (e.g., 0.2 → 0.3)
4. Enter reason: "Reducing false positives"
5. Save Configuration
6. ✅ Change logged in audit trail

### Via Database (Emergency)
```sql
UPDATE 0_bi_config 
SET config_value = '0.3' 
WHERE config_key = 'pattern_matching.keyword_clustering_factor';
```

### Verify Change
```sql
SELECT config_key, config_value, updated_at, updated_by 
FROM 0_bi_config 
WHERE config_key = 'pattern_matching.keyword_clustering_factor';
```

---

## 🧪 Testing Quick Check

### Before Change
```
Navigate to: /modules/ksf_bank_import/includes/search_partner_keywords.inc
Search: "Internet Domain Registration"
Note: scores and confidence levels
```

### After Change
```
Refresh same page
Same search: "Internet Domain Registration"
Compare: scores should be higher, confidence may change
```

### Validation
```php
echo "Clustering Factor: " . KEYWORD_CLUSTERING_FACTOR . "<br>";
// Should match your database value
```

---

## ⏪ Quick Rollback

### Via UI
1. Module Configuration
2. Recent Configuration Changes (bottom of page)
3. Note old value
4. Change back to old value
5. Reason: "Rollback - too aggressive"

### Via Database
```sql
-- Get previous value
SELECT old_value FROM 0_bi_config_history 
WHERE config_key = 'pattern_matching.keyword_clustering_factor'
ORDER BY changed_at DESC LIMIT 1;

-- Restore
UPDATE 0_bi_config 
SET config_value = '[old_value]'
WHERE config_key = 'pattern_matching.keyword_clustering_factor';
```

---

## 📈 Monitoring

### Track Accuracy
```sql
-- If you implement suggestion feedback:
SELECT 
    AVG(CASE WHEN suggested = selected THEN 100 ELSE 0 END) as accuracy_pct
FROM bi_suggestion_feedback
WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Target: 70%+ accuracy
-- If < 60%: Adjust clustering factor
```

### Common Patterns

| Accuracy | Action |
|----------|--------|
| < 50% | Review entire system |
| 50-60% | Increase clustering (try 0.3) |
| 60-70% | Keep current setting |
| 70-80% | Optimal (0.2 working well) |
| > 80% | Could try 0.1 for more suggestions |

---

## 💡 Pro Tips

1. **Start Conservative:** Begin with 0.2, observe for 1 week
2. **Small Changes:** Adjust by 0.1 increments only
3. **Document Reasons:** Always explain why you changed
4. **Monitor Impact:** Check accuracy after each change
5. **User Feedback:** Ask users if suggestions improved
6. **Peak Periods:** Don't change during busy periods
7. **Backup First:** Note old value before changing
8. **Test Staging:** Test in dev environment first

---

## 🚨 Troubleshooting

### Setting Not Taking Effect
```php
// Check what's loaded
echo KEYWORD_CLUSTERING_FACTOR;  // Should match database

// Force reload
unset($configService);
$configService = \Ksfraser\FaBankImport\Config\ConfigService::getInstance();
```

### UI Not Showing
```sql
-- Verify migration ran
SELECT COUNT(*) FROM 0_bi_config WHERE category = 'pattern_matching';
-- Should return 4

-- If 0, re-run migration:
SOURCE sql/add_pattern_matching_config.sql;
```

### Invalid Value Error
```
Error: Clustering factor must be between 0 and 1

Fix: Enter value like 0.3 (not 3 or 30)
```

---

## 📞 Need Help?

1. **Full Guide:** PATTERN_MATCHING_CONFIG_GUIDE.md
2. **Technical Details:** CLUSTERING_BONUS_EXPLAINED.md
3. **Algorithm:** KEYWORD_SCORING_SYSTEM.md
4. **Integration:** CONFIG_INTEGRATION_SUMMARY.md

---

## ✅ Quick Checklist

- [ ] Migration ran: `sql/add_pattern_matching_config.sql`
- [ ] UI accessible: GL → Module Configuration
- [ ] Pattern Matching section visible
- [ ] Default value: 0.2
- [ ] Test page works: search_partner_keywords.inc
- [ ] Can change value via UI
- [ ] Change appears in history
- [ ] New value takes effect immediately

---

**Version:** 1.0.0  
**Date:** 2025-10-20  
**License:** MIT
