# Integration Testing Guide - V2 PartnerType Views

**Date**: 2025-10-25  
**Feature**: V2 Views with ViewFactory (USE_V2_PARTNER_VIEWS = true)  
**Status**: 🔄 **READY FOR TESTING**

---

## Overview

This guide provides step-by-step instructions for integration testing the refactored PartnerType Views. The refactoring includes:

1. ✅ Strategy Pattern implementation (PartnerTypeDisplayStrategy)
2. ✅ ViewFactory with dependency injection
3. ✅ PartnerFormData for $_POST abstraction
4. ✅ HTML library classes for type-safe HTML generation
5. ✅ TDD with comprehensive test suite (13 tests)

---

## Prerequisites

### 1. Feature Flag Status
```php
// In class.bi_lineitem.php (line 55)
define('USE_V2_PARTNER_VIEWS', true);  // ✅ V2 Views ENABLED
```

### 2. FA Development Environment
- FrontAccounting installed and running
- ksf_bank_import module installed
- Database accessible
- Web server running (Apache/PHP)

### 3. Sample Test Files
Available in `includes/` directory:
- `ATB.qfx` - ATB bank statements
- `CIBC_SAVINGS.qfx` - CIBC savings account
- `CIBC_VISA.qfx` - CIBC Visa credit card
- `MANU.qfx` - Manulife statements
- `PCMC.qfx` - PC Mastercard
- `RBC.qfx` - RBC statements
- `SIMPLII.qfx` - Simplii Financial

---

## Test Scenarios

### Scenario 1: Supplier Partner Type (SP)

**What to test**: SupplierPartnerTypeView rendering and form submission

**Steps**:
1. Navigate to `process_statements.php`
2. Import a QFX file with supplier transactions (e.g., `CIBC_VISA.qfx`)
3. For a transaction, select Partner Type: **Supplier**
4. Verify UI displays:
   - ✅ "Supplier:" label
   - ✅ Supplier dropdown (populated from `supplier_list()`)
   - ✅ "Other Bank Account:" field pre-filled
   - ✅ Partner ID field (if supplier selected)
5. Select a supplier from dropdown
6. Click "Submit"
7. Verify:
   - ✅ Form submission successful
   - ✅ Data saved to `bank_import_line_items` table
   - ✅ `partnerId` column contains correct supplier ID
   - ✅ `partner_type` column = 'SP'
   - ✅ No PHP errors in log

**Expected Behavior**:
- V2 view matches V1 layout exactly
- Supplier dropdown populated correctly
- Form data persists correctly
- PartnerFormData handles $_POST properly

**SQL Check**:
```sql
SELECT id, partner_type, partnerId, otherBankAccount 
FROM bank_import_line_items 
WHERE partner_type = 'SP' 
ORDER BY id DESC LIMIT 5;
```

---

### Scenario 2: Customer Partner Type (CU)

**What to test**: CustomerPartnerTypeView with branch support

**Steps**:
1. Navigate to `process_statements.php`
2. Import a QFX file with customer transactions
3. For a transaction, select Partner Type: **Customer**
4. Verify UI displays:
   - ✅ "Customer:" label
   - ✅ Customer dropdown (populated from `customer_list()`)
   - ✅ "Branch:" dropdown (if branches exist)
   - ✅ "Other Bank Account:" field pre-filled
   - ✅ Partner ID and Partner Detail ID fields
5. Select a customer from dropdown
6. If branches exist, select a branch
7. Click "Submit"
8. Verify:
   - ✅ Form submission successful
   - ✅ Data saved correctly
   - ✅ `partnerId` contains customer ID
   - ✅ `partnerDetailId` contains branch ID (if selected)
   - ✅ `partner_type` = 'CU'

**Expected Behavior**:
- Customer dropdown populated correctly
- Branch dropdown appears if customer has branches
- Branch selection persists correctly
- All form data saved properly

**SQL Check**:
```sql
SELECT id, partner_type, partnerId, partnerDetailId, otherBankAccount 
FROM bank_import_line_items 
WHERE partner_type = 'CU' 
ORDER BY id DESC LIMIT 5;
```

---

### Scenario 3: Bank Transfer Partner Type (BT)

**What to test**: BankTransferPartnerTypeView rendering

**Steps**:
1. Navigate to `process_statements.php`
2. Import a QFX file
3. For an inter-bank transaction, select Partner Type: **Bank Transfer**
4. Verify UI displays:
   - ✅ "Bank Account:" label
   - ✅ Bank account dropdown (populated from `bank_accounts_list()`)
   - ✅ "Other Bank Account:" field pre-filled
   - ✅ Partner ID field
5. Select a bank account from dropdown
6. Click "Submit"
7. Verify:
   - ✅ Form submission successful
   - ✅ `partnerId` contains bank account ID
   - ✅ `partner_type` = 'BT'

**Expected Behavior**:
- Bank account dropdown populated
- Selection persists correctly
- Inter-bank transfers handled properly

**SQL Check**:
```sql
SELECT id, partner_type, partnerId, otherBankAccount 
FROM bank_import_line_items 
WHERE partner_type = 'BT' 
ORDER BY id DESC LIMIT 5;
```

---

### Scenario 4: Quick Entry Partner Type (QE)

**What to test**: QuickEntryPartnerTypeView rendering

**Steps**:
1. Navigate to `process_statements.php`
2. Import a QFX file
3. For a transaction, select Partner Type: **Quick Entry**
4. Verify UI displays:
   - ✅ "Quick Entry:" label
   - ✅ Quick entry dropdown (populated from `quick_entries_list()`)
   - ✅ "Other Bank Account:" field pre-filled
   - ✅ Partner ID field
5. Select a quick entry from dropdown
6. Click "Submit"
7. Verify:
   - ✅ Form submission successful
   - ✅ `partnerId` contains quick entry ID
   - ✅ `partner_type` = 'QE'

**Expected Behavior**:
- Quick entry dropdown populated
- Selection persists correctly
- Quick entry templates work

**SQL Check**:
```sql
SELECT id, partner_type, partnerId, otherBankAccount 
FROM bank_import_line_items 
WHERE partner_type = 'QE' 
ORDER BY id DESC LIMIT 5;
```

---

### Scenario 5: Matched Manual Partner Type (MA)

**What to test**: Manual matching with existing GL transactions

**Steps**:
1. Navigate to `process_statements.php`
2. Import a QFX file
3. For a transaction with potential matches, select Partner Type: **Matched**
4. Verify UI displays:
   - ✅ "Existing Entry Type:" dropdown (system types)
   - ✅ Table of matching transactions with:
     - Trans # (clickable link to FA transaction)
     - Type (e.g., "Bank Payment")
     - Date
     - Amount
     - Radio button for selection
   - ✅ "Other Bank Account:" field
5. Select a transaction via radio button
6. Click "Submit"
7. Verify:
   - ✅ Form submission successful
   - ✅ `partner_type` = 'MA'
   - ✅ `matching_trans` field populated with selected transaction details

**Expected Behavior**:
- Matching transactions displayed correctly
- Radio button selection works
- Link to FA transaction opens correct page
- Manual match recorded properly

**SQL Check**:
```sql
SELECT id, partner_type, matching_trans 
FROM bank_import_line_items 
WHERE partner_type = 'MA' 
ORDER BY id DESC LIMIT 5;
```

---

### Scenario 6: Matched Existing Partner Type (ZZ)

**What to test**: Auto-matched transactions with hidden fields

**Steps**:
1. Navigate to `process_statements.php`
2. Import a QFX file with transactions that auto-match existing GL entries
3. For an auto-matched transaction:
4. Verify UI displays:
   - ✅ Hidden fields:
     - `partnerId_<id>` = 'existing'
     - `matching_trans_type_<id>` = transaction type
     - `matching_trans_typeno_<id>` = transaction number
5. Verify form data persists on page reload

**Expected Behavior**:
- Hidden fields generated correctly
- Auto-matched transactions don't require user selection
- Data persists across page reloads

**SQL Check**:
```sql
SELECT id, partner_type, matching_trans 
FROM bank_import_line_items 
WHERE partner_type = 'ZZ' 
ORDER BY id DESC LIMIT 5;
```

---

## Regression Testing

### Check for Regressions

**UI/UX**:
- ✅ Layout matches V1 exactly
- ✅ Field labels unchanged
- ✅ Dropdown populations identical
- ✅ Form styling consistent
- ✅ No visual glitches

**Functionality**:
- ✅ All partner types work
- ✅ Form submissions successful
- ✅ Data persists correctly
- ✅ Validation works
- ✅ Error handling unchanged

**Performance**:
- ✅ Page load time acceptable
- ✅ No noticeable slowdown
- ✅ Database queries efficient

**Compatibility**:
- ✅ Works with existing FA processing
- ✅ PartnerFormData compatible with $_POST
- ✅ No breaking changes to downstream code

---

## Debugging Guide

### Common Issues

**Issue 1: Dropdown Not Populating**

**Symptoms**: Partner type dropdown empty

**Causes**:
- FA function not available (e.g., `supplier_list()`)
- Database connection issue
- ViewFactory not instantiating view correctly

**Debug**:
```php
// In Views/SupplierPartnerTypeView.php
error_log("Supplier list: " . print_r($this->supplierList, true));
```

**Issue 2: Form Data Not Persisting**

**Symptoms**: After submit, form resets or data not saved

**Causes**:
- PartnerFormData not reading $_POST correctly
- Field name mismatch
- Database INSERT failing

**Debug**:
```php
// In class.bi_lineitem.php
error_log("POST data: " . print_r($_POST, true));
error_log("Partner type: " . $this->formData->getPartnerType());
error_log("Partner ID: " . $this->partnerId);
```

**Issue 3: Strategy Pattern Not Dispatching**

**Symptoms**: "Unknown partner type" error

**Causes**:
- Invalid partner type code
- Strategy not loaded
- Exception in display method

**Debug**:
```php
// In Views/PartnerTypeDisplayStrategy.php
error_log("Partner type received: " . $partnerType);
error_log("Available strategies: " . print_r($this->getAvailablePartnerTypes(), true));
```

**Issue 4: ViewFactory Not Creating Views**

**Symptoms**: Blank output or error in view rendering

**Causes**:
- USE_V2_PARTNER_VIEWS flag issue
- View class not found
- Constructor arguments incorrect

**Debug**:
```php
// In Views/ViewFactory.php
error_log("Creating view: " . $viewType);
error_log("USE_V2_PARTNER_VIEWS: " . (USE_V2_PARTNER_VIEWS ? 'true' : 'false'));
```

---

## PHP Error Log Locations

**Windows (XAMPP)**:
```
C:\xampp\apache\logs\error.log
C:\xampp\php\logs\php_error_log
```

**Linux**:
```
/var/log/apache2/error.log
/var/log/php/error.log
```

**Check FA Log**:
```php
// In FA installation
$path_to_root/tmp/errors.log
```

---

## SQL Testing Queries

### Check Line Items
```sql
-- All line items with partner info
SELECT 
    id,
    valueTimestamp,
    transactionDC,
    amount,
    partner_type,
    partnerId,
    partnerDetailId,
    otherBankAccount,
    memo
FROM bank_import_line_items
ORDER BY id DESC
LIMIT 20;
```

### Partner Type Distribution
```sql
-- Count by partner type
SELECT 
    partner_type,
    COUNT(*) as count
FROM bank_import_line_items
GROUP BY partner_type
ORDER BY count DESC;
```

### Recent Submissions
```sql
-- Last 10 submissions
SELECT 
    id,
    valueTimestamp,
    partner_type,
    partnerId,
    SUBSTRING(memo, 1, 30) as memo_preview
FROM bank_import_line_items
WHERE partner_type IS NOT NULL
ORDER BY id DESC
LIMIT 10;
```

### Matching Transactions
```sql
-- Matched entries
SELECT 
    id,
    partner_type,
    matching_trans
FROM bank_import_line_items
WHERE partner_type IN ('MA', 'ZZ')
ORDER BY id DESC
LIMIT 10;
```

---

## Test Checklist

### Pre-Testing
- [ ] Feature flag `USE_V2_PARTNER_VIEWS` = `true`
- [ ] FA development environment running
- [ ] Database accessible
- [ ] Sample QFX files ready
- [ ] Error logging enabled

### Supplier Testing (SP)
- [ ] Dropdown populates with suppliers
- [ ] Supplier selection persists
- [ ] Form submission successful
- [ ] Data saved to database
- [ ] `partner_type` = 'SP'
- [ ] `partnerId` correct
- [ ] No PHP errors

### Customer Testing (CU)
- [ ] Dropdown populates with customers
- [ ] Branch dropdown appears (if applicable)
- [ ] Customer + Branch selection persists
- [ ] Form submission successful
- [ ] Data saved correctly
- [ ] `partner_type` = 'CU'
- [ ] `partnerId` and `partnerDetailId` correct
- [ ] No PHP errors

### Bank Transfer Testing (BT)
- [ ] Dropdown populates with bank accounts
- [ ] Bank account selection persists
- [ ] Form submission successful
- [ ] Data saved correctly
- [ ] `partner_type` = 'BT'
- [ ] `partnerId` correct
- [ ] No PHP errors

### Quick Entry Testing (QE)
- [ ] Dropdown populates with quick entries
- [ ] Quick entry selection persists
- [ ] Form submission successful
- [ ] Data saved correctly
- [ ] `partner_type` = 'QE'
- [ ] `partnerId` correct
- [ ] No PHP errors

### Manual Match Testing (MA)
- [ ] Matching transactions table displays
- [ ] Trans # links work
- [ ] Radio button selection works
- [ ] Form submission successful
- [ ] `partner_type` = 'MA'
- [ ] `matching_trans` populated
- [ ] No PHP errors

### Auto-Match Testing (ZZ)
- [ ] Hidden fields generated
- [ ] Auto-match data persists
- [ ] `partner_type` = 'ZZ'
- [ ] `matching_trans` populated
- [ ] No user interaction required

### Regression Testing
- [ ] UI matches V1 exactly
- [ ] All dropdowns populate correctly
- [ ] Form submissions work
- [ ] Data persists correctly
- [ ] No visual regressions
- [ ] No functional regressions
- [ ] Performance acceptable

---

## Success Criteria

✅ **All 6 partner types render correctly**  
✅ **Form submissions successful for all types**  
✅ **Data persists to database correctly**  
✅ **PartnerFormData $_POST handling works**  
✅ **No regressions in UI/UX**  
✅ **No regressions in functionality**  
✅ **No PHP errors or warnings**  
✅ **ViewFactory creates views correctly**  
✅ **Strategy pattern dispatches correctly**  
✅ **HTML library classes render proper HTML**

---

## Test Results

### Date: _________

**Tester**: _________

**Environment**:
- FA Version: _________
- PHP Version: _________
- Database: _________

**Results**:

| Test Scenario | Status | Notes |
|--------------|--------|-------|
| Supplier (SP) | ⬜ Pass / ⬜ Fail | |
| Customer (CU) | ⬜ Pass / ⬜ Fail | |
| Bank Transfer (BT) | ⬜ Pass / ⬜ Fail | |
| Quick Entry (QE) | ⬜ Pass / ⬜ Fail | |
| Manual Match (MA) | ⬜ Pass / ⬜ Fail | |
| Auto-Match (ZZ) | ⬜ Pass / ⬜ Fail | |
| Regression Tests | ⬜ Pass / ⬜ Fail | |

**Issues Found**:
1. 
2. 
3. 

**Overall Status**: ⬜ **PASS** / ⬜ **FAIL**

---

## Next Steps After Testing

### If All Tests Pass ✅
1. Update documentation with test results
2. Mark integration testing todo as complete
3. Consider removing legacy `display*PartnerType()` methods
4. Create pull request for review
5. Merge to main branch
6. Deploy to production

### If Tests Fail ❌
1. Document failing scenarios in detail
2. Create bug report with reproduction steps
3. Debug using debugging guide above
4. Fix issues
5. Re-run tests
6. Repeat until all pass

---

## Additional Resources

**Related Documentation**:
- `REFACTOR_STRATEGY_PATTERN.md` - Strategy pattern implementation
- `REFACTOR_TDD_STRATEGY.md` - TDD approach and test coverage
- `REFACTOR_HTML_LIBRARY_LINE338.md` - HTML library refactoring
- `REFACTORING_NOTES.md` - General refactoring notes

**Code Files**:
- `class.bi_lineitem.php` - Main model class
- `Views/PartnerTypeDisplayStrategy.php` - Strategy pattern implementation
- `Views/ViewFactory.php` - View factory with DI
- `Views/*PartnerTypeView.php` - Individual view classes
- `src/Ksfraser/PartnerFormData.php` - $_POST abstraction

**Test Files**:
- `tests/unit/Views/PartnerTypeDisplayStrategyTest.php` - Strategy tests (13 tests)

---

**Author**: GitHub Copilot  
**Date**: 2025-10-25  
**Version**: 1.0
