# How to Pull and Use Mantis #3178 Analysis Branch

## Quick Reference

### On Production Server

```bash
# 1. Fetch the new branch
cd /path/to/frontaccounting/modules/ksf_bank_import
git fetch origin

# 2. Checkout the analysis branch
git checkout mantis-3178-analysis

# 3. Verify the files are present
ls -la analyze_interest_transactions.php
ls -la sql/mantis_3178_interest_analysis.sql
ls -la docs/MANTIS_3178_INVESTIGATION.md

# 4. Run the analysis
php analyze_interest_transactions.php > interest_analysis_$(date +%Y%m%d_%H%M%S).txt

# 5. View the results
cat interest_analysis_*.txt

# 6. Save results for review
cp interest_analysis_*.txt ~/reports/
```

### On Development Machine

```bash
# Pull the branch
cd c:\Users\prote\Documents\ksf_bank_import
git fetch origin
git checkout mantis-3178-analysis

# View the files
dir analyze_interest_transactions.php
dir sql\mantis_3178_interest_analysis.sql
dir docs\MANTIS_3178_INVESTIGATION.md

# Read the documentation
notepad docs\MANTIS_3178_INVESTIGATION.md
```

## Branch Information

- **Branch Name:** `mantis-3178-analysis`
- **Remote URL:** https://github.com/ksfraser/ksf_bank_import/tree/mantis-3178-analysis
- **Commit:** 1d24005
- **Created:** 2025-10-18

## Files in This Branch

1. **sql/mantis_3178_interest_analysis.sql** (350+ lines)
   - 10 SQL queries for production database analysis
   - Can be run directly in MySQL/MariaDB client
   - Read-only queries (no data modifications)

2. **analyze_interest_transactions.php** (370+ lines)
   - PHP script that runs analysis programmatically
   - Uses FrontAccounting database connection
   - Outputs formatted results
   - Safe to run (read-only)

3. **docs/MANTIS_3178_INVESTIGATION.md** (600+ lines)
   - Complete investigation guide
   - Problem description
   - Usage instructions
   - Implementation plan
   - Testing strategy

## Usage Workflow

### Step 1: Pull the Branch

```bash
git fetch origin
git checkout mantis-3178-analysis
```

### Step 2: Run Analysis on Production

```bash
# SSH to production
ssh user@production-server

# Navigate to module
cd /var/www/html/frontaccounting/modules/ksf_bank_import

# Pull branch
git fetch origin
git checkout mantis-3178-analysis

# Run analysis
php analyze_interest_transactions.php | tee interest_report_$(date +%Y%m%d).txt
```

### Step 3: Review Results

```bash
# View the report
less interest_report_20251018.txt

# Look for these sections:
# - SUMMARY: Total suspect transaction count
# - AFFECTED BANK ACCOUNTS: Which accounts have the issue
# - PROCESSING STATUS: Pending vs Processed breakdown
# - SAMPLE SUSPECT TRANSACTIONS: Examples to review
# - RECOMMENDATIONS: What to do next
```

### Step 4: Save Results

```bash
# Copy to reports directory
mkdir -p ~/mantis_3178_reports
cp interest_report_*.txt ~/mantis_3178_reports/

# Email to accountant (if needed)
# Or copy to shared drive
```

### Step 5: Document Findings

Create `docs/MANTIS_3178_ANALYSIS_RESULTS.md` with:
- Summary of findings
- Number of affected transactions
- Affected bank accounts
- Processing status breakdown
- Recommendations
- Accountant approval status

## Optional: Run SQL Queries Directly

If you prefer SQL over PHP:

```bash
# Connect to database
mysql -u fa_user -p frontaccounting

# Run the SQL file
source sql/mantis_3178_interest_analysis.sql

# Or run individual queries
SELECT COUNT(*) 
FROM 0_bi_transactions 
WHERE transactionDC = 'D' 
  AND LOWER(transactionTitle) LIKE '%interest%';
```

## After Analysis Complete

### Merge to Main (If Needed)

```bash
# Switch to main
git checkout main

# Merge the analysis branch
git merge mantis-3178-analysis

# Push to origin
git push origin main
```

### Or Keep Separate

```bash
# Keep analysis branch separate
# Just reference it when needed
git checkout mantis-3178-analysis  # To use
git checkout main                  # To go back
```

## Branch Contents Summary

```
mantis-3178-analysis branch contains:
├── analyze_interest_transactions.php    (NEW - Analysis script)
├── docs/
│   └── MANTIS_3178_INVESTIGATION.md    (NEW - Investigation guide)
└── sql/
    └── mantis_3178_interest_analysis.sql (NEW - SQL queries)

All other files remain as in main branch.
```

## Pull Request (Optional)

To create a pull request for review:

1. Visit: https://github.com/ksfraser/ksf_bank_import/pull/new/mantis-3178-analysis
2. Add description of analysis tools
3. Request review
4. Merge when ready

## Notes

- ✅ Branch is pushed to origin
- ✅ All files committed
- ✅ Safe to pull on production
- ✅ Read-only analysis (no data changes)
- ✅ Can run multiple times
- ⚠️ Requires database access (FrontAccounting credentials)
- ⚠️ May take a few minutes on large datasets

## Troubleshooting

### Branch not found

```bash
# Make sure you fetched first
git fetch origin

# List remote branches
git branch -r

# Should see: origin/mantis-3178-analysis
```

### Cannot run PHP script

```bash
# Check PHP is available
php --version

# Check file permissions
chmod +x analyze_interest_transactions.php

# Check path
which php
```

### Database connection errors

```bash
# Make sure you're in the right directory
pwd
# Should be: .../frontaccounting/modules/ksf_bank_import

# Check FA is working
ls -la ../../includes/session.inc
```

## Support

If you encounter issues:
1. Check `docs/MANTIS_3178_INVESTIGATION.md` for details
2. Review commit message for context
3. Contact development team

---

**Branch:** mantis-3178-analysis  
**Created:** 2025-10-18  
**Status:** Ready for production analysis  
**Next Step:** Run on production and document results
