# Pattern Matching Configuration Installation Guide

## Overview

The keyword clustering factor and other pattern matching settings are now stored in the database and can be configured through the admin UI without editing code.

## Installation Steps

### 1. Run Database Migration

```bash
mysql your_database_name < sql/add_pattern_matching_config.sql
```

Or via FrontAccounting SQL interface:
1. Navigate to **Setup → Install/Update**
2. Paste contents of `sql/add_pattern_matching_config.sql`
3. Click Execute

### 2. Verify Configuration

```sql
-- Check if pattern matching configs were added
SELECT * FROM 0_bi_config WHERE category = 'pattern_matching';
```

Expected results:
```
pattern_matching.keyword_clustering_factor → 0.2
pattern_matching.min_confidence_threshold → 30
pattern_matching.max_suggestions → 5
pattern_matching.min_keyword_length → 3
```

### 3. Access Configuration UI

1. Log in as admin
2. Navigate to: **GL → Module Configuration**
3. Scroll to **Pattern Matching Configuration** section
4. You should see:
   - Keyword Clustering Factor (default: 0.2)
   - Min Confidence Threshold (default: 30%)
   - Max Suggestions (default: 5)
   - Min Keyword Length (default: 3)

## Configuration Settings Explained

### 1. Keyword Clustering Factor
**Default:** `0.2` (balanced)

**What it does:** Controls how much bonus to give when multiple keywords match the same partner.

**Formula:** `score = base_score * (1 + (keyword_count - 1) * CLUSTERING_FACTOR)`

**Values:**
- `0.1` - Conservative (subtle 10% boost per keyword)
- `0.2` - Balanced (20% boost per keyword)
- `0.3` - Aggressive (30% boost per keyword)

**When to adjust:**
- **Increase to 0.3** if single high-occurrence keywords are winning when they shouldn't
- **Decrease to 0.1** if missing valid matches due to aggressive clustering
- **Keep at 0.2** for balanced behavior (recommended)

**Example:**
```
Search: "Internet Domain Registration" (3 keywords)

With 0.2:
  Partner A: base=107, mult=1.4, final=150
  Partner B: base=100, mult=1.0, final=100
  Winner: Partner A

With 0.1:
  Partner A: base=107, mult=1.2, final=128
  Partner B: base=100, mult=1.0, final=100
  Winner: Partner A (but less confident)

With 0.3:
  Partner A: base=107, mult=1.6, final=171
  Partner B: base=100, mult=1.0, final=100
  Winner: Partner A (very confident)
```

### 2. Min Confidence Threshold
**Default:** `30` (percent)

**What it does:** Minimum confidence score to show a suggestion.

**Usage:**
```php
$suggestions = search_partner_by_keywords(PT_SUPPLIER, $text, 5);

foreach ($suggestions as $suggestion) {
    if ($suggestion['confidence'] >= $min_confidence_threshold) {
        // Show suggestion
    } else {
        // Filter out low-confidence matches
    }
}
```

**When to adjust:**
- **Increase to 50%** if getting too many false positive suggestions
- **Decrease to 20%** if missing valid but rare matches
- **Keep at 30%** for balanced filtering

### 3. Max Suggestions
**Default:** `5`

**What it does:** Maximum number of partner suggestions to return.

**When to adjust:**
- **Increase to 10** if users need to see more options (ambiguous cases)
- **Decrease to 3** to simplify UI and reduce clutter
- **Keep at 5** for good balance

### 4. Min Keyword Length
**Default:** `3` characters

**What it does:** Minimum length for a word to be indexed as a keyword.

**Examples:**
```
min_length = 3:
  "TD Bank Transfer" → ["bank", "transfer"]  (TD excluded)
  
min_length = 2:
  "TD Bank Transfer" → ["td", "bank", "transfer"]  (TD included)
```

**When to adjust:**
- **Increase to 4** if database is too large or too many noise words
- **Decrease to 2** if important 2-letter keywords (TD, PC, BC, etc.)
- **Keep at 3** for balanced keyword extraction

## Configuration Change Workflow

### Via UI (Recommended)

1. **Navigate to Config:**
   - GL → Module Configuration

2. **Modify Setting:**
   - Find "Pattern Matching" section
   - Change "Keyword Clustering Factor" from 0.2 to 0.3

3. **Document Change:**
   - Enter reason: "Increased clustering to reduce false positives from high-occurrence single keywords"

4. **Save:**
   - Click "Save Configuration"
   - System records change in audit trail

5. **Verify:**
   - Check "Recent Configuration Changes" at bottom of page
   - Should show your change with timestamp and reason

### Via Database (Advanced)

```sql
-- Update clustering factor
UPDATE 0_bi_config 
SET config_value = '0.3',
    updated_at = NOW(),
    updated_by = 'admin'
WHERE config_key = 'pattern_matching.keyword_clustering_factor';

-- Record in history
INSERT INTO 0_bi_config_history 
(config_key, old_value, new_value, changed_by, change_reason)
VALUES (
    'pattern_matching.keyword_clustering_factor',
    '0.2',
    '0.3',
    'admin',
    'Adjusted via direct SQL for testing'
);
```

### Via Code (Development/Testing Only)

```php
// Override for testing specific value
define('KEYWORD_CLUSTERING_FACTOR', 0.3);

// Then include search function
require_once 'includes/search_partner_keywords.inc';
```

**Note:** Code override is NOT recommended for production. Use UI instead.

## Testing Changes

### 1. Test Search Function

Navigate to: `/modules/ksf_bank_import/includes/search_partner_keywords.inc`

This runs built-in tests showing:
- Base scores
- Clustering bonuses
- Final scores
- Confidence percentages

### 2. Test with Real Data

```php
require_once 'includes/search_partner_keywords.inc';

$test_transactions = [
    'SHOPPERS DRUG MART',
    'Internet Domain Registration',
    'TD Bank Transfer',
    'Bell Mobility Payment'
];

foreach ($test_transactions as $text) {
    echo "<h3>Search: $text</h3>";
    $results = search_partner_by_keywords(0, $text, 5);
    
    foreach ($results as $i => $result) {
        echo sprintf(
            "%d. Partner %d (Type %d): Score=%d (%d base + %d bonus), Confidence=%.1f%%<br>",
            $i+1,
            $result['partner_id'],
            $result['partner_type'],
            $result['score'],
            $result['base_score'],
            $result['clustering_bonus'],
            $result['confidence']
        );
    }
}
```

### 3. Monitor Accuracy

Track when users override suggestions:

```sql
-- Add to your transaction processing
-- When user selects different partner than top suggestion:
INSERT INTO bi_suggestion_feedback (
    transaction_id,
    suggested_partner_id,
    selected_partner_id,
    confidence_score,
    feedback_date
) VALUES (...);

-- Then analyze:
SELECT 
    CASE 
        WHEN suggested_partner_id = selected_partner_id THEN 'Correct'
        ELSE 'Overridden'
    END as outcome,
    COUNT(*) as count
FROM bi_suggestion_feedback
GROUP BY outcome;

-- Adjust clustering factor based on override rate:
-- If override rate > 30%: Increase clustering factor
-- If override rate < 10%: Decrease clustering factor (system too conservative)
```

## Rollback Plan

### Revert to Previous Value

1. **Via UI:**
   - Check "Recent Configuration Changes"
   - Note the old value
   - Update setting back to old value
   - Enter reason: "Reverting due to [reason]"

2. **Via Database:**
```sql
-- Get history
SELECT * FROM 0_bi_config_history 
WHERE config_key = 'pattern_matching.keyword_clustering_factor'
ORDER BY changed_at DESC LIMIT 5;

-- Revert to previous value
UPDATE 0_bi_config 
SET config_value = '[old_value_from_history]'
WHERE config_key = 'pattern_matching.keyword_clustering_factor';
```

### Restore Defaults

```sql
UPDATE 0_bi_config 
SET config_value = '0.2'
WHERE config_key = 'pattern_matching.keyword_clustering_factor';

UPDATE 0_bi_config 
SET config_value = '30'
WHERE config_key = 'pattern_matching.min_confidence_threshold';

UPDATE 0_bi_config 
SET config_value = '5'
WHERE config_key = 'pattern_matching.max_suggestions';

UPDATE 0_bi_config 
SET config_value = '3'
WHERE config_key = 'pattern_matching.min_keyword_length';
```

## Troubleshooting

### Problem: Setting Not Taking Effect

**Check 1:** Verify config in database
```sql
SELECT * FROM 0_bi_config 
WHERE config_key = 'pattern_matching.keyword_clustering_factor';
```

**Check 2:** Verify code is loading config
```php
// In search_partner_keywords.inc
echo "Clustering factor: " . KEYWORD_CLUSTERING_FACTOR . "<br>";
```

**Check 3:** Clear any caching
```php
// If ConfigService caches:
$configService = \Ksfraser\FaBankImport\Config\ConfigService::getInstance();
$configService->clearCache();  // If method exists
```

### Problem: UI Not Showing Pattern Matching Section

**Check 1:** Migration ran successfully
```sql
SELECT COUNT(*) FROM 0_bi_config WHERE category = 'pattern_matching';
-- Should return 4
```

**Check 2:** Re-run migration
```bash
mysql database < sql/add_pattern_matching_config.sql
```

**Check 3:** Check module_config.php file
```php
// Should have this section:
if (isset($allConfigs['pattern_matching'])) {
    // Pattern matching UI code
}
```

### Problem: Invalid Values

**Symptom:** Errors when saving config

**Solution:** Add validation in ConfigService:
```php
public function set(string $key, $value, ...) {
    if ($key === 'pattern_matching.keyword_clustering_factor') {
        $value = (float)$value;
        if ($value < 0 || $value > 1) {
            throw new \InvalidArgumentException(
                "Clustering factor must be between 0 and 1"
            );
        }
    }
    // ... continue with save
}
```

## Best Practices

1. **Document Changes:** Always enter a reason when changing configuration
2. **Test Before Prod:** Test new values in staging environment first
3. **Monitor Impact:** Track suggestion accuracy after changes
4. **Gradual Adjustments:** Change factor by 0.1 increments, not large jumps
5. **Audit Trail:** Review configuration history monthly
6. **Backup Before Change:** Note old value before modifying
7. **User Feedback:** Ask users if suggestions improve/worsen

## Security Notes

- Configuration UI requires `SA_SETUPCOMPANY` permission
- All changes logged with username and timestamp
- System configs (security.*) cannot be modified via UI
- Database direct access should be restricted to DBAs only

## License

MIT License - Copyright 2025 KSF
