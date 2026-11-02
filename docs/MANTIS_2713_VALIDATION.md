# Mantis #2713: Import Bank Transaction Files - Validate Entries

**Implementation Date:** October 18, 2025  
**Author:** Kevin Fraser / ChatGPT  
**Status:** ✅ Complete

## Overview

This feature validates that imported bank transaction data matches the associated GL (General Ledger) entries in FrontAccounting. It ensures data integrity and flags discrepancies for review.

## Requirements (from Mantis ticket)

> We want to ensure that our bank data matches our GLs.
>
> Have a routine that goes, and checks that the trans_type and Trans_no exist, and that the values match.
> *We already have a GL matching routine
>
> If they don't, flag the entry.
> *Extending the matching routine, we could suggest possible matches.
>
> Steps To Reproduce: Open screen, click button to run, display results.

## Implementation

### Files Created

#### 1. `src/Ksfraser/FaBankImport/services/TransactionGLValidator.php`

**Class:** `TransactionGLValidator`

**Purpose:** Service class that performs validation logic

**Key Methods:**

- `validateAllTransactions($smt_id = null)` - Validate all matched transactions or specific statement
- `validateTransaction($transaction)` - Validate a single transaction against its GL entry
- `checkGLTransactionExists($trans_type, $trans_no)` - Check if GL trans exists
- `getGLTransactionDetails($trans_type, $trans_no)` - Retrieve GL transaction details
- `validateAmounts($transaction, $glTrans)` - Compare bank amount vs GL amount
- `validateDates($transaction, $glTrans)` - Check date variance
- `validateBankAccount($transaction, $glTrans)` - Verify bank account matches
- `findPossibleGLMatches($transaction)` - Use existing matching routine to suggest alternatives
- `flagTransactionForReview($trans_id, $reason)` - Set status=-1 for flagged transactions
- `getFlaggedTransactions()` - Retrieve all flagged transactions
- `clearFlag($trans_id)` - Remove flag from transaction

#### 2. `validate_gl_entries.php`

**Purpose:** User interface for validation tool

**Features:**

- **Validate All** button - Run validation on all matched transactions
- **Validate Statement** dropdown - Run validation on specific statement
- **Flagged Transactions** section - View and manage flagged entries
- **Validation Results** table with:
  - Transaction ID
  - FA Type:No (with link to GL entry)
  - Bank Amount vs GL Amount
  - Variance calculation
  - Status (FAILED/WARNING)
  - Detailed errors and warnings
  - Suggested matches (top 3)
  - Actions (Flag for Review button)
- **Help section** explaining validation types

## Validation Checks

### 1. Missing GL Entries ❌ ERROR

**Check:** Does the GL transaction exist?

```sql
SELECT COUNT(*) FROM gl_trans 
WHERE type = {fa_trans_type} AND type_no = {fa_trans_no}
```

**Action if failed:**
- Flag as ERROR
- Use existing `fa_gl::find_matching_transactions()` to suggest possible matches
- Display up to 5 suggested matches with scores

### 2. Amount Mismatch ❌ ERROR

**Check:** Does bank import amount match GL entry amount?

**Logic:**
1. Find bank account entries in GL (accounts starting with "10")
2. Compare absolute values (allow 1 cent variance for rounding)
3. If no match, find closest amount and calculate variance

**Action if failed:**
- Flag as ERROR
- Display both amounts and variance
- Highlight large variances (>$1.00) in red

### 3. Date Variance ⚠️ WARNING

**Check:** Are GL date and bank date within reasonable range?

**Logic:**
- Calculate days difference between `gl_trans.tran_date` and `bi_transactions.valueTimestamp`
- Warn if difference > 7 days

**Action if failed:**
- Flag as WARNING (not critical)
- Display both dates and days apart

### 4. Bank Account Missing ⚠️ WARNING

**Check:** Is expected bank account found in GL entry line items?

**Logic:**
- Search GL line items for matching account code or account name
- Compare `bi_transactions.account` and `accountName` with `gl_trans.account`

**Action if failed:**
- Flag as WARNING
- Display expected vs actual accounts

## Database Schema

### Status Field Usage

The `bi_transactions.status` field now uses:

- `0` = Unprocessed (not matched)
- `1` = Matched to existing GL entry
- `2` = New GL entry created
- `-1` = **Flagged for review** ✨ NEW

### MatchInfo Field Usage

When a transaction is flagged, the `matchinfo` field stores the reason:

```sql
UPDATE bi_transactions 
SET status = -1, 
    matchinfo = 'Amount mismatch: Bank=100.00, GL=99.50 (variance: 0.50)'
WHERE id = 123;
```

## Integration with Existing Code

### Uses Existing GL Matching Routine ✅

The validator leverages the existing matching logic in `ksf_modules_common/class.fa_gl.php`:

```php
$fa_gl = new fa_gl();
$fa_gl->set("amount", $transaction['transactionAmount']);
$fa_gl->set("transactionDC", $transaction['transactionDC']);
$fa_gl->set("days_spread", 7);
$fa_gl->set("startdate", $transaction['valueTimestamp']);
$fa_gl->set("enddate", $transaction['entryTimestamp']);
$fa_gl->set("memo_", $transaction['memo']);

$matches = $fa_gl->find_matching_transactions($transaction['memo']);
```

This provides **suggested matches** when validation fails, as requested in the Mantis ticket.

### Database Tables Used

**Read from:**
- `bi_transactions` - Imported bank transactions
- `bi_statements` - Statement information
- `gl_trans` - General Ledger transactions
- `chart_master` - Account names and codes

**Write to:**
- `bi_transactions.status` - Flag status
- `bi_transactions.matchinfo` - Reason for flagging

## Usage Examples

### Scenario 1: Validate All Transactions

```php
$validator = new TransactionGLValidator();
$results = $validator->validateAllTransactions();

// Results structure:
[
    'total_checked' => 150,
    'issues_found' => 12,
    'results' => [ /* array of validation results */ ],
    'summary' => [
        'missing_gl' => 3,
        'amount_mismatch' => 7,
        'date_warnings' => 2,
        'account_warnings' => 0,
        'total_variance' => 25.47
    ]
]
```

### Scenario 2: Validate Specific Statement

```php
$validator = new TransactionGLValidator();
$results = $validator->validateAllTransactions($smt_id = 456);
```

### Scenario 3: Flag Transaction for Review

```php
$validator = new TransactionGLValidator();
$validator->flagTransactionForReview(
    $trans_id = 789,
    $reason = "Manual review required: Customer dispute"
);
```

### Scenario 4: Get All Flagged Transactions

```php
$validator = new TransactionGLValidator();
$flagged = $validator->getFlaggedTransactions();

// Returns array:
[
    [
        'id' => 789,
        'bank' => 'SIMPLII',
        'transactionAmount' => 100.00,
        'fa_trans_type' => 12,
        'fa_trans_no' => 5678,
        'status' => -1,
        'matchinfo' => 'Amount mismatch: Bank=100.00, GL=99.50',
        ...
    ],
    ...
]
```

## UI Screenshots (Conceptual)

### Main Screen

```
╔════════════════════════════════════════════════════════════════╗
║ Validate Bank Import GL Entries                                ║
╠════════════════════════════════════════════════════════════════╣
║                                                                 ║
║ FLAGGED TRANSACTIONS (2)                                        ║
║ ┌────────────────────────────────────────────────────────────┐ ║
║ │ Trans ID │ Bank    │ Date       │ Amount  │ FA Type:No    │ ║
║ │ 789      │ SIMPLII │ 2025-10-15 │ 100.00  │ 12:5678       │ ║
║ │ 801      │ ATB     │ 2025-10-16 │  50.00  │ 0:9999 (DNE)  │ ║
║ └────────────────────────────────────────────────────────────┘ ║
║                                                                 ║
║ VALIDATION CONTROLS                                             ║
║ [Validate All Matched Transactions]                             ║
║                                                                 ║
║ OR Validate Specific Statement:                                ║
║ [Dropdown: SIMPLII - Chequing - Oct 15, 2025 (12/15 matched)] ║
║ [Validate Selected Statement]                                   ║
║                                                                 ║
╚════════════════════════════════════════════════════════════════╝
```

### Results Screen

```
╔════════════════════════════════════════════════════════════════╗
║ SUMMARY                                                         ║
║ Total Transactions Checked: 150                                 ║
║ Issues Found: 12                                                ║
║ Missing GL Entries: 3                                           ║
║ Amount Mismatches: 7                                            ║
║ Date Warnings: 2                                                ║
║ Total Amount Variance: $25.47                                   ║
╠════════════════════════════════════════════════════════════════╣
║ VALIDATION DETAILS                                              ║
║ ┌────────────────────────────────────────────────────────────┐ ║
║ │ Trans │ FA      │ Bank   │ GL    │ Var.  │ Status         │ ║
║ │ ID    │ Type:No │ Amount │ Amt   │       │                │ ║
║ ├────────────────────────────────────────────────────────────┤ ║
║ │ 789   │ 12:5678 │ 100.00 │ 99.50 │ 0.50  │ ❌ FAILED      │ ║
║ │       │         │        │       │       │ Error: Amount  │ ║
║ │       │         │        │       │       │ mismatch       │ ║
║ │       │         │        │       │       │ [Flag]         │ ║
║ ├────────────────────────────────────────────────────────────┤ ║
║ │ 801   │ 0:9999  │  50.00 │  N/A  │   -   │ ❌ FAILED      │ ║
║ │       │         │        │       │       │ Error: GL      │ ║
║ │       │         │        │       │       │ does not exist │ ║
║ │       │         │        │       │       │ Suggested:     │ ║
║ │       │         │        │       │       │ • 12:5690 (85) │ ║
║ │       │         │        │       │       │ • 1:3456 (75)  │ ║
║ │       │         │        │       │       │ [Flag]         │ ║
║ └────────────────────────────────────────────────────────────┘ ║
╚════════════════════════════════════════════════════════════════╝
```

## Testing

### Test Cases

1. **✅ Valid Transaction**
   - Bank trans: $100.00, Type 12:5678
   - GL entry: $100.00, Type 12:5678
   - **Expected:** No errors

2. **❌ Missing GL Entry**
   - Bank trans: $50.00, Type 0:9999
   - GL entry: Does not exist
   - **Expected:** ERROR with suggested matches

3. **❌ Amount Mismatch**
   - Bank trans: $100.00, Type 12:5678
   - GL entry: $99.50, Type 12:5678
   - **Expected:** ERROR showing variance

4. **⚠️ Date Warning**
   - Bank trans: 2025-10-01, Type 12:5678
   - GL entry: 2025-10-15 (14 days apart)
   - **Expected:** WARNING (>7 days)

5. **⚠️ Account Warning**
   - Bank trans: Account "1060.simplii"
   - GL entry: No line item for "1060.simplii"
   - **Expected:** WARNING

### SQL Test Queries

```sql
-- Find transactions with missing GL entries
SELECT t.id, t.fa_trans_type, t.fa_trans_no
FROM 0_bi_transactions t
LEFT JOIN 0_gl_trans g ON g.type = t.fa_trans_type AND g.type_no = t.fa_trans_no
WHERE t.fa_trans_type > 0 AND t.fa_trans_no > 0
AND g.type IS NULL;

-- Find transactions with amount mismatches
SELECT t.id, t.transactionAmount, 
       ABS(g.amount) as gl_amount,
       ABS(t.transactionAmount - ABS(g.amount)) as variance
FROM 0_bi_transactions t
JOIN 0_gl_trans g ON g.type = t.fa_trans_type AND g.type_no = t.fa_trans_no
WHERE t.fa_trans_type > 0 AND t.fa_trans_no > 0
AND ABS(t.transactionAmount - ABS(g.amount)) > 0.01
AND g.account LIKE '10%';  -- Bank accounts

-- Get all flagged transactions
SELECT * FROM 0_bi_transactions WHERE status = -1;
```

## Future Enhancements

1. **Automated Flagging**
   - Run validation automatically after import
   - Flag suspicious transactions before manual review

2. **Batch Re-matching**
   - Allow bulk re-assignment of transactions to suggested GL entries
   - "Accept Suggestion" button in UI

3. **Variance Tolerance**
   - Configurable variance threshold (currently hard-coded at $0.01)
   - Different thresholds for different account types

4. **Email Notifications**
   - Alert accountant when validation failures exceed threshold
   - Daily summary of flagged transactions

5. **Audit Trail**
   - Log all flagging/unflagging actions with timestamps and user
   - Track who cleared flags and why

6. **Export to CSV**
   - Export validation results for external review
   - Include in month-end reconciliation reports

## Security

**Permission Required:** `SA_BANKTRANSVIEW`

Same permission as viewing bank transactions is required to run validation.

## Performance Considerations

- Validation runs on-demand (not real-time during import)
- For large datasets (>1000 transactions), consider running validation overnight
- Indexes on `bi_transactions(fa_trans_type, fa_trans_no)` recommended
- Indexes on `gl_trans(type, type_no)` should already exist

## Documentation References

- **Mantis Ticket:** #2713
- **Related Mantis:** #2778 (ATB handling), #3178 (SIMPLII/MANU interest)
- **Existing Matching:** `ksf_modules_common/class.fa_gl.php`
- **GL Display:** `../../gl/view/gl_trans_view.php`

## Summary

✅ **Implementation complete for Mantis #2713**

**Key Features Delivered:**
1. ✅ Validation routine checks trans_type and trans_no exist
2. ✅ Validates amounts match between bank and GL
3. ✅ Flags mismatches for review (status=-1)
4. ✅ Uses existing GL matching routine to suggest alternatives
5. ✅ UI with "Open screen, click button, display results"

**Additional Features Beyond Requirements:**
- Date variance warnings
- Bank account verification
- Flagged transactions management
- Detailed error/warning messages
- Clickable links to GL entries
- Statement-specific validation
