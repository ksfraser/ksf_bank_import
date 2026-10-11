# Configuration Integration Summary

## Changes Made (2025-10-20)

### 🎯 Goal
Move `KEYWORD_CLUSTERING_FACTOR` from hardcoded constant to database-backed configuration accessible via admin UI.

### ✅ Files Created

1. **`sql/add_pattern_matching_config.sql`** - Migration script
   - Adds `pattern_matching.keyword_clustering_factor` (default: 0.2)
   - Adds `pattern_matching.min_confidence_threshold` (default: 30%)
   - Adds `pattern_matching.max_suggestions` (default: 5)
   - Adds `pattern_matching.min_keyword_length` (default: 3 chars)

2. **`PATTERN_MATCHING_CONFIG_GUIDE.md`** - Complete admin guide
   - Installation instructions
   - Configuration settings explained
   - Change workflow (UI, DB, code)
   - Testing procedures
   - Rollback plan
   - Troubleshooting

### ✅ Files Modified

1. **`module_config.php`** - Added Pattern Matching section
   - New UI section between Performance and Security
   - Float input for clustering factor (0-1 range)
   - Integer inputs for other settings
   - Audit trail (reason for change)

2. **`includes/search_partner_keywords.inc`** - Dynamic config loading
   - Tries ConfigService first (database)
   - Falls back to get_company_pref() (FrontAccounting)
   - Ultimate fallback to 0.2 hardcoded
   - Graceful degradation if config unavailable

3. **`build_partner_keyword_data.php`** - Uses config for min length
   - Reads `pattern_matching.min_keyword_length` from config
   - Falls back to 3 if unavailable
   - Consistent with search function

4. **`KEYWORD_SCORING_SYSTEM.md`** - Updated tuning section
   - Changed to reference UI configuration
   - Removed code-based configuration instructions
   - Added database configuration note

5. **`CLUSTERING_BONUS_EXPLAINED.md`** - Updated "How to Change"
   - UI method (recommended)
   - Database method (advanced)
   - Code method (development only)

### 🗄️ Database Structure

**Table:** `0_bi_config`
```sql
config_key: pattern_matching.keyword_clustering_factor
config_value: 0.2
config_type: float
description: Clustering bonus multiplier...
category: pattern_matching
is_system: 0 (user-modifiable)
```

**Audit Trail:** `0_bi_config_history`
- Tracks all changes
- Records: old_value, new_value, changed_by, change_reason, changed_at

### 🔄 Loading Priority

```
1. ConfigService::get('pattern_matching.keyword_clustering_factor')
   ↓ (if unavailable)
2. get_company_pref('bi_keyword_clustering_factor')
   ↓ (if unavailable)
3. Hardcoded fallback: 0.2
```

### 🎨 UI Location

**Path:** GL → Module Configuration → Pattern Matching Configuration

**Fields:**
- Keyword Clustering Factor (float, 0-1, step 0.1)
- Min Confidence Threshold (integer, 0-100)
- Max Suggestions (integer)
- Min Keyword Length (integer)

Each field has:
- Label + description
- Input control (appropriate type)
- Reason field (for audit trail)

### 📊 Configuration Values

| Setting | Default | Type | Range | Purpose |
|---------|---------|------|-------|---------|
| keyword_clustering_factor | 0.2 | float | 0-1 | Co-occurrence bonus multiplier |
| min_confidence_threshold | 30 | int | 0-100 | Filter low-confidence suggestions |
| max_suggestions | 5 | int | 1-20 | Limit result count |
| min_keyword_length | 3 | int | 2-10 | Minimum chars to index |

### 🧪 Testing Checklist

- [ ] Run migration: `sql/add_pattern_matching_config.sql`
- [ ] Verify configs in database: `SELECT * FROM 0_bi_config WHERE category='pattern_matching'`
- [ ] Access UI: GL → Module Configuration
- [ ] See "Pattern Matching Configuration" section
- [ ] Change clustering factor to 0.3
- [ ] Enter reason for change
- [ ] Save configuration
- [ ] Verify in "Recent Configuration Changes"
- [ ] Test search: `/modules/ksf_bank_import/includes/search_partner_keywords.inc`
- [ ] Verify new clustering factor is used
- [ ] Change back to 0.2
- [ ] Verify change in history

### 📚 Documentation

1. **PATTERN_MATCHING_CONFIG_GUIDE.md** - Complete admin guide
   - Installation
   - Configuration explained
   - Change workflows
   - Testing
   - Troubleshooting

2. **KEYWORD_SCORING_SYSTEM.md** - Updated tuning section
   - References UI configuration
   - Removed hardcoded instructions

3. **CLUSTERING_BONUS_EXPLAINED.md** - Updated "How to Change"
   - UI method first
   - Database method
   - Code override deprecated

### 🔐 Security

- **Permission Required:** `SA_SETUPCOMPANY` (admin only)
- **Audit Trail:** All changes logged with user and reason
- **System Configs:** Security settings locked (is_system=1)
- **Validation:** ConfigService validates value ranges

### 🚀 Deployment Steps

**For Production:**
```bash
# 1. Backup database
mysqldump database > backup_$(date +%Y%m%d).sql

# 2. Run migration
mysql database < sql/add_pattern_matching_config.sql

# 3. Verify
mysql database -e "SELECT * FROM 0_bi_config WHERE category='pattern_matching'"

# 4. Test UI access
# Navigate to: GL → Module Configuration

# 5. Monitor
# Check audit trail after first change
```

**For Development:**
```bash
# 1. Pull latest code
git pull origin main

# 2. Run migration
mysql dev_database < sql/add_pattern_matching_config.sql

# 3. Test locally
# Modify clustering factor via UI
# Run tests

# 4. Commit
git add .
git commit -m "Add pattern matching configuration UI"
```

### ⚡ Impact

**Before:**
- Clustering factor hardcoded in PHP
- Required code change + deployment to adjust
- No audit trail
- No production-safe tuning

**After:**
- Clustering factor in database
- Changed via admin UI (no code change)
- Full audit trail (who, when, why)
- Production-safe with rollback

**Performance:**
- ConfigService caches config values
- Single DB query on first access
- No performance impact

**Compatibility:**
- Backward compatible (falls back to 0.2)
- Works if ConfigService unavailable
- Existing code continues working

### 🎉 Benefits

1. **Production-Safe:** Change without code deployment
2. **Audit Trail:** Track who changed what and why
3. **Rollback:** Easy to revert via UI or DB
4. **Testing:** Test different values without code changes
5. **Documentation:** Built-in descriptions in UI
6. **Validation:** ConfigService enforces valid ranges
7. **Multi-Tenant:** Different values per company (future)

### 📝 Next Steps (Optional)

1. **Add Validation UI:**
   ```javascript
   // Client-side validation
   <input type="number" step="0.1" min="0" max="1" ...>
   ```

2. **Add Real-Time Preview:**
   ```php
   // Show impact of factor change
   "If you change to 0.3, scores will be:"
   ```

3. **Add Feedback Loop:**
   ```sql
   -- Track suggestion accuracy
   CREATE TABLE bi_suggestion_feedback ...
   ```

4. **Add A/B Testing:**
   ```php
   // Test different factors with random users
   $factor = user_id % 2 == 0 ? 0.2 : 0.3;
   ```

### 🐛 Known Issues

- None currently

### 📞 Support

- **Documentation:** See PATTERN_MATCHING_CONFIG_GUIDE.md
- **Troubleshooting:** See guide's Troubleshooting section
- **Questions:** Contact module maintainer

## Summary

The keyword clustering factor is now fully integrated with the module's configuration system! Admins can adjust pattern matching behavior through a production-safe UI with full audit trail, no code changes required.

**Installation:** Run `sql/add_pattern_matching_config.sql`  
**Access:** GL → Module Configuration → Pattern Matching  
**Default:** 0.2 (balanced)  
**Documentation:** PATTERN_MATCHING_CONFIG_GUIDE.md  
