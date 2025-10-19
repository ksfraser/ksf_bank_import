# Mantis #3178: Interest Transactions Incorrectly Marked as Debit

**Issue ID:** 3178  
**Reporter:** User  
**Date Reported:** October 18, 2025  
**Status:** Analysis Required  
**Priority:** Medium  
**Severity:** Data Accuracy Issue

---

## Table of Contents

1. [Problem Description](#problem-description)
2. [Impact Assessment](#impact-assessment)
3. [Analysis Tools](#analysis-tools)
4. [Investigation Steps](#investigation-steps)
5. [Potential Solutions](#potential-solutions)
6. [Implementation Plan](#implementation-plan)

---

## Problem Description

### Issue

Some banks are sending **Interest earned** and **Promotional Interest** transactions with a Debit (D) indicator when they should be marked as Credit (C).

### Expected Behavior

Interest transactions should be recorded as **Credits** because they increase the account balance:
- Interest earned = Money added to account = Credit (C)
- Promotional interest = Money added to account = Credit (C)

### Actual Behavior

Some bank statement imports (QFX, MT940, CSV) are recording interest transactions as **Debits** (D), which is incorrect.

### Symptoms

1. Interest transactions appear as negative amounts in reports
2. Account balances may be incorrect after processing
3. GL entries for interest may have reversed signs
4. Bank reconciliation discrepancies

### Affected Transaction Types

- Regular interest earned
- Promotional interest
- Bonus interest
- Savings account interest
- High-interest promotional periods

---

## Impact Assessment

### Data Integrity

- **Severity:** Medium to High
- **Scope:** Unknown (requires analysis)
- **Financial Impact:** Potentially affects all interest-bearing accounts

### Processing Status

Need to determine:
1. How many transactions are affected?
2. Which bank accounts have this issue?
3. How many have already been processed into GL?
4. What is the total dollar amount impact?

### Time Period

Need to analyze:
- When did this issue start?
- Is it ongoing or historical?
- Does it affect specific date ranges?

---

## Analysis Tools

### 1. SQL Analysis Script

**File:** `sql/mantis_3178_interest_analysis.sql`

**Purpose:** Comprehensive SQL queries to analyze production data

**Queries Included:**
1. Count suspect transactions
2. Detailed list of suspect transactions
3. Group by bank account
4. Group by transaction code patterns
5. Debit vs Credit comparison
6. Title/description patterns
7. Promotional interest patterns
8. Processing status breakdown
9. Sample correct transactions
10. Year-over-year trends

**Usage:**
```bash
mysql -u username -p database_name < sql/mantis_3178_interest_analysis.sql > analysis_results.txt
```

### 2. PHP Analysis Script

**File:** `analyze_interest_transactions.php`

**Purpose:** Programmatic analysis with formatted output

**Features:**
- Connects to production database via FrontAccounting
- Runs multiple analysis queries
- Displays formatted results
- Provides recommendations

**Usage:**
```bash
cd /path/to/frontaccounting/modules/ksf_bank_import
php analyze_interest_transactions.php > interest_analysis_report.txt
```

**Output Includes:**
- Summary statistics
- Affected bank accounts
- Processing status breakdown
- Debit vs Credit comparison
- Sample suspect transactions
- Recommendations for correction

---

## Investigation Steps

### Step 1: Run Analysis Scripts

**BEFORE making any code changes, gather data:**

1. **Run SQL analysis:**
   ```bash
   # SSH to production server
   ssh user@prod-server
   
   # Navigate to database
   mysql -u fa_user -p frontaccounting
   
   # Run analysis
   source /path/to/sql/mantis_3178_interest_analysis.sql
   ```

2. **Run PHP analysis:**
   ```bash
   cd /var/www/html/frontaccounting/modules/ksf_bank_import
   php analyze_interest_transactions.php | tee interest_report_$(date +%Y%m%d).txt
   ```

3. **Export results:**
   ```bash
   # Save results for review
   cp interest_report_*.txt ~/reports/
   ```

### Step 2: Review Results

**Answer these questions:**

1. **How many transactions are affected?**
   - Total count
   - By bank account
   - By date range

2. **Which banks/accounts have the issue?**
   - CIBC? RBC? Manulife? ATB?
   - Savings accounts? Checking accounts?
   - Specific account numbers?

3. **What are the patterns?**
   - Transaction codes
   - Transaction descriptions
   - Memo field contents

4. **Processing status?**
   - How many pending (status = 0)?
   - How many processed (status = 1)?

5. **Financial impact?**
   - Total dollar amount
   - Average transaction size
   - Date range affected

### Step 3: Validate with Samples

**Manual verification:**

1. Pick 5-10 sample transactions from analysis
2. Check original bank statement files
3. Confirm they are indeed interest transactions
4. Verify they should be Credits, not Debits
5. Document findings

### Step 4: Identify Root Cause

**Check parser logic:**

1. **QFX Parser** (`qfx_parser.php`, `class.CibcQfxParser.php`, etc.):
   - How is `transactionDC` determined?
   - Is it reading from `<TRNTYPE>` field?
   - Is there logic that interprets transaction types?

2. **MT940 Parser** (`mt940_parser.php`, `ro_brd_mt940_parser.php`):
   - How are debits/credits parsed?
   - Is `transactionDC` set correctly?

3. **CSV Parsers** (`ro_bcr_csv_parser.php`, `ro_ing_csv_parser.php`, etc.):
   - How is the D/C indicator read?
   - Is there any transformation logic?

**Possible causes:**
- Parser reads `<TRNTYPE>DEBIT</TRNTYPE>` literally without checking context
- Bank uses non-standard codes for interest
- Parser doesn't have special handling for interest transactions
- Negative amount interpretation (some banks use negative for debits, positive for credits)

---

## Potential Solutions

### Solution 1: Detection and Flagging

**Approach:** Detect suspect transactions and flag for manual review

**Implementation:**
1. Add detection logic in parser
2. Check if transaction contains "interest" keywords
3. Check if marked as Debit
4. Flag transaction with warning
5. Set status to require manual review

**Pros:**
- Conservative approach
- Allows human verification
- No automatic changes

**Cons:**
- Requires manual intervention
- Slows down processing

### Solution 2: Auto-Correction in Parser

**Approach:** Automatically correct D→C for interest transactions

**Implementation:**
1. During parsing, detect interest keywords
2. If `transactionDC == 'D'` AND contains "interest", flip to 'C'
3. Log the correction
4. Continue processing normally

**Pros:**
- Automated fix
- Fast processing
- No manual intervention needed

**Cons:**
- Risk of incorrect auto-correction
- May miss edge cases

### Solution 3: Post-Import Validation

**Approach:** Validate after import, before processing

**Implementation:**
1. Import transactions as-is
2. Run validation check on pending transactions
3. Flag suspect transactions
4. Provide correction UI
5. Process after correction

**Pros:**
- Allows review before GL impact
- Can batch-correct
- Audit trail

**Cons:**
- Extra step in workflow
- Requires UI changes

### Solution 4: Hybrid Approach (Recommended)

**Combination of detection, auto-correction, and validation:**

1. **In Parser:**
   - Detect interest + debit combination
   - Log warning
   - Apply auto-correction if high confidence
   - Flag if uncertain

2. **In process_statements.php:**
   - Add validation check before processing
   - Display warning for flagged transactions
   - Require confirmation or manual override

3. **Add Filter in TransactionFilterService:**
   - Add method to find suspect interest transactions
   - Allow filtering in UI
   - Batch review and correction

**Implementation:**
```php
// In parser (e.g., CibcQfxParser.php)
private function detectAndCorrectInterestDebit($transaction)
{
    // Check if transaction contains interest keywords
    $isInterest = (
        stripos($transaction['transactionTitle'], 'interest') !== false ||
        stripos($transaction['transactionCodeDesc'], 'interest') !== false ||
        stripos($transaction['memo'], 'interest') !== false
    );
    
    // If interest is marked as debit, it's likely an error
    if ($isInterest && $transaction['transactionDC'] === 'D') {
        // Log the issue
        error_log("MANTIS #3178: Suspect interest debit detected: " . 
                  print_r($transaction, true));
        
        // Auto-correct if high confidence
        if ($this->isHighConfidenceInterest($transaction)) {
            $transaction['transactionDC'] = 'C';
            $transaction['memo'] .= ' [Auto-corrected D→C]';
        } else {
            // Flag for manual review
            $transaction['status'] = 0; // Pending
            $transaction['memo'] .= ' [REVIEW: Interest marked as Debit]';
        }
    }
    
    return $transaction;
}

private function isHighConfidenceInterest($transaction)
{
    // High confidence if contains specific keywords
    $highConfidenceKeywords = [
        'interest earned',
        'promotional interest',
        'bonus interest',
        'savings interest',
        'interest credit'
    ];
    
    $text = strtolower(
        $transaction['transactionTitle'] . ' ' . 
        $transaction['transactionCodeDesc'] . ' ' . 
        $transaction['memo']
    );
    
    foreach ($highConfidenceKeywords as $keyword) {
        if (stripos($text, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}
```

---

## Implementation Plan

### Phase 1: Analysis (Do First - No Code Changes)

**Deliverables:**
- [ ] Run SQL analysis script
- [ ] Run PHP analysis script
- [ ] Document results in `docs/MANTIS_3178_ANALYSIS_RESULTS.md`
- [ ] Export sample transactions for accountant review
- [ ] Get confirmation from accountant

**Estimated Time:** 2-4 hours

### Phase 2: Detection Logic (Safe Changes)

**Tasks:**
- [ ] Add detection method to base parser class
- [ ] Implement in all parser subclasses (CIBC, Manu, RBC, etc.)
- [ ] Add logging for suspect transactions
- [ ] Add unit tests for detection logic

**Files to Modify:**
- `class.AbstractQfxParser.php` - Add detection method
- `class.CibcQfxParser.php` - Call detection
- `class.ManuQfxParser.php` - Call detection
- `class.PcmcQfxParser.php` - Call detection
- `mt940_parser.php` - Add detection for MT940
- `ro_*_csv_parser.php` - Add detection for CSV parsers

**Estimated Time:** 4-6 hours

### Phase 3: Auto-Correction (After Testing)

**Tasks:**
- [ ] Implement auto-correction logic
- [ ] Add configuration option (enable/disable auto-correct)
- [ ] Add comprehensive logging
- [ ] Create unit tests for correction logic
- [ ] Test with sample data

**Estimated Time:** 4-6 hours

### Phase 4: UI Enhancement (Optional)

**Tasks:**
- [ ] Add "Suspect Transactions" filter to TransactionFilterService
- [ ] Add UI indicator in process_statements.php
- [ ] Add batch correction capability
- [ ] Add audit trail

**Estimated Time:** 6-8 hours

### Phase 5: Correcting Existing Data (If Needed)

**Tasks:**
- [ ] Create correction script for processed transactions
- [ ] Coordinate with accountant
- [ ] Run on test data
- [ ] Create GL adjustment entries if needed
- [ ] Deploy to production

**Estimated Time:** 4-8 hours (depends on scope)

---

## Testing Plan

### Unit Tests

```php
// Test detection
public function testDetectInterestDebit()
{
    $transaction = [
        'transactionTitle' => 'Interest Earned',
        'transactionDC' => 'D',
        'transactionAmount' => 5.25
    ];
    
    $this->assertTrue($this->parser->isInterestTransaction($transaction));
    $this->assertTrue($this->parser->isSuspectDebit($transaction));
}

// Test auto-correction
public function testAutoCorrectInterestDebit()
{
    $transaction = [
        'transactionTitle' => 'Promotional Interest',
        'transactionDC' => 'D',
        'transactionAmount' => 25.00
    ];
    
    $corrected = $this->parser->detectAndCorrectInterestDebit($transaction);
    
    $this->assertEquals('C', $corrected['transactionDC']);
    $this->assertStringContainsString('Auto-corrected', $corrected['memo']);
}
```

### Integration Tests

1. Import sample bank statement with interest debit
2. Verify detection triggers
3. Verify correction applied (if enabled)
4. Verify logging
5. Process transaction
6. Verify GL entry is correct

### Production Testing

1. Enable detection only (no auto-correct)
2. Monitor logs for 1 week
3. Review flagged transactions
4. If pattern confirmed, enable auto-correct
5. Monitor for 1 week
6. Review results

---

## Documentation

### Files Created

1. `sql/mantis_3178_interest_analysis.sql` - SQL analysis queries
2. `analyze_interest_transactions.php` - PHP analysis script
3. `docs/MANTIS_3178_INVESTIGATION.md` - This document

### Files to Create After Analysis

1. `docs/MANTIS_3178_ANALYSIS_RESULTS.md` - Production data analysis results
2. `docs/MANTIS_3178_CORRECTION_PLAN.md` - Detailed correction strategy
3. `docs/MANTIS_3178_IMPLEMENTATION.md` - Code changes and testing results

---

## Next Steps

### Immediate Actions (Before Any Code Changes)

1. **Run analysis scripts on production:**
   ```bash
   ssh prod-server
   cd /var/www/html/frontaccounting/modules/ksf_bank_import
   php analyze_interest_transactions.php > ~/interest_analysis_$(date +%Y%m%d_%H%M%S).txt
   ```

2. **Review results:**
   - How many transactions affected?
   - Which accounts?
   - What's the pattern?

3. **Get accountant confirmation:**
   - Show sample transactions
   - Confirm they are errors
   - Get approval for correction approach

4. **Document findings:**
   - Create `docs/MANTIS_3178_ANALYSIS_RESULTS.md`
   - Include screenshots
   - Record decisions

5. **Plan implementation:**
   - Choose solution approach
   - Estimate effort
   - Schedule deployment

### Do NOT Do Yet

- ❌ Don't modify parser code until analysis complete
- ❌ Don't run correction scripts on production
- ❌ Don't change existing processed transactions
- ❌ Don't enable auto-correction until tested

---

## Questions to Answer from Analysis

1. **Scope:**
   - How many transactions are affected?
   - What percentage of total interest transactions?

2. **Pattern:**
   - All from one bank or multiple?
   - Specific account types?
   - Specific date ranges?

3. **Impact:**
   - Total dollar amount?
   - How many already processed?
   - Are GL entries incorrect?

4. **Urgency:**
   - Is it ongoing (new imports)?
   - Or historical only?
   - Reconciliation impact?

5. **Correction:**
   - Can we auto-correct safely?
   - Do we need manual review?
   - What's the confidence level?

---

**Status:** Awaiting production data analysis  
**Next Action:** Run `analyze_interest_transactions.php` on production  
**Document Version:** 1.0  
**Last Updated:** October 18, 2025
