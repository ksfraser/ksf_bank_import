# Browser Testing Checklist

**Date:** _______________  
**Tester:** _______________  
**Environment:** [ ] Production [ ] Staging [ ] Development  
**Browser:** [ ] Chrome [ ] Firefox [ ] Edge [ ] Safari  

---

## ⚙️ Pre-Testing Setup

- [ ] Confirm `USE_COMMAND_PATTERN = true` in `command_bootstrap.php`
- [ ] Run syntax check: `php -l process_statements.php`
- [ ] Run unit tests: `vendor\bin\phpunit tests\unit\Commands\`
- [ ] Check PHP error log location: _______________
- [ ] Open browser developer console (F12)
- [ ] Clear browser cache

---

## 🧪 Test 1: Unset Transaction

### Setup
1. Navigate to `process_statements.php`
2. Search for a processed transaction (status = 1)
3. Identify transaction ID: _______________

### Execute
- [ ] Click "Unset Transaction" button for selected transaction
- [ ] Wait for page refresh

### Verify ✅
- [ ] Success notification appears (green message)
- [ ] Message says: "Transaction(s) reset successfully"
- [ ] Transaction status changed to 0 (unprocessed)
- [ ] Table refreshes automatically
- [ ] Transaction row style changes (if applicable)
- [ ] No JavaScript errors in console
- [ ] No PHP errors in log

### Rollback Test ⏪
- [ ] Set `USE_COMMAND_PATTERN = false`
- [ ] Refresh page
- [ ] Click "Unset Transaction" again
- [ ] Verify it still works with legacy code
- [ ] Set `USE_COMMAND_PATTERN = true`

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 2: Add Customer

### Setup
1. Find an unprocessed transaction (status = 0)
2. Transaction should have customer data
3. Transaction ID: _______________
4. Expected customer name: _______________

### Execute
- [ ] Select transaction(s)
- [ ] Enter customer details if prompted
- [ ] Click "Add Customer" button
- [ ] Wait for confirmation

### Verify ✅
- [ ] Success notification appears
- [ ] Message says: "Customer created successfully" or "N customers created"
- [ ] New customer appears in FA customer list
- [ ] Customer name matches transaction data
- [ ] Transaction status updated (if applicable)
- [ ] Table refreshes automatically
- [ ] No JavaScript errors in console
- [ ] No PHP errors in log

### Edge Cases
- [ ] Try with multiple transactions selected
- [ ] Try with no transaction selected (should show error)
- [ ] Try with invalid data (should show error)

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 3: Add Vendor

### Setup
1. Find an unprocessed transaction (status = 0)
2. Transaction should have vendor/supplier data
3. Transaction ID: _______________
4. Expected vendor name: _______________

### Execute
- [ ] Select transaction(s)
- [ ] Enter vendor details if prompted
- [ ] Click "Add Vendor" button
- [ ] Wait for confirmation

### Verify ✅
- [ ] Success notification appears
- [ ] Message says: "Vendor created successfully" or "N vendors created"
- [ ] New vendor appears in FA supplier list
- [ ] Vendor name matches transaction data
- [ ] Transaction status updated (if applicable)
- [ ] Table refreshes automatically
- [ ] No JavaScript errors in console
- [ ] No PHP errors in log

### Edge Cases
- [ ] Try with multiple transactions selected
- [ ] Try with no transaction selected (should show error)
- [ ] Try with duplicate vendor (should handle gracefully)

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 4: Toggle Debit/Credit

### Setup
1. Find a transaction with DC indicator
2. Note current DC value: [ ] D [ ] C [ ] B
3. Transaction ID: _______________

### Execute
- [ ] Select transaction
- [ ] Click "Toggle DC" button
- [ ] Wait for confirmation

### Verify ✅
- [ ] Success notification appears
- [ ] Message says: "Toggled debit/credit for N transaction(s)"
- [ ] DC indicator changed (D↔C or D↔B or C↔B)
- [ ] Old value displayed in message: "Old: D, New: C"
- [ ] Transaction row updates
- [ ] Table refreshes automatically
- [ ] No JavaScript errors in console
- [ ] No PHP errors in log

### Edge Cases
- [ ] Toggle same transaction twice (should revert to original)
- [ ] Try with multiple transactions selected
- [ ] Try with no transaction selected (should show error)

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 5: Error Handling

### Test 5a: Empty Selection
- [ ] Click each button without selecting a transaction
- [ ] Verify error message displays for each
- [ ] Verify no database changes occur
- [ ] Verify table still works after error

### Test 5b: Invalid Data
- [ ] Try creating customer with missing required fields
- [ ] Verify validation error displays
- [ ] Verify form remains populated (no data loss)

### Test 5c: Partial Success (Multiple Selections)
- [ ] Select 3 transactions
- [ ] Ensure 1 will fail (e.g., duplicate customer)
- [ ] Click "Add Customer"
- [ ] Verify warning notification (not error)
- [ ] Verify successful ones created
- [ ] Verify failed ones reported

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 6: Other POST Actions (Unchanged)

Verify these still work (not modified by Command Pattern):

### ProcessBothSides (Paired Bank Transfer)
- [ ] Select paired transfer transaction
- [ ] Click "Process Both Sides" button
- [ ] Verify both sides recorded
- [ ] Verify GL entry link works

### ProcessTransaction (Main Processing)
- [ ] Process a supplier payment (SP)
- [ ] Process a customer payment (CU)
- [ ] Process a quick entry (QE)
- [ ] Process a bank transfer (BT)
- [ ] Process a manual allocation (MA)
- [ ] Process an auto-match (ZZ)

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 7: Performance

### Response Time
- [ ] Time "Unset Transaction": _______ seconds (should be < 1s)
- [ ] Time "Add Customer": _______ seconds (should be < 2s)
- [ ] Time "Add Vendor": _______ seconds (should be < 2s)
- [ ] Time "Toggle DC": _______ seconds (should be < 1s)

### Multiple Transactions
- [ ] Select 10 transactions
- [ ] Click "Unset Transaction"
- [ ] Time response: _______ seconds
- [ ] Verify all 10 processed

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 8: Browser Compatibility

Test in each browser:

### Chrome
- [ ] All 4 POST actions work
- [ ] No console errors
- [ ] Ajax refresh works

### Firefox
- [ ] All 4 POST actions work
- [ ] No console errors
- [ ] Ajax refresh works

### Edge
- [ ] All 4 POST actions work
- [ ] No console errors
- [ ] Ajax refresh works

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 9: Concurrent Users

### Setup
- [ ] Open browser in 2 windows
- [ ] Login as different users (if possible)

### Execute
- [ ] User 1: Click "Unset Transaction" on transaction A
- [ ] User 2: Click "Unset Transaction" on transaction B (different)
- [ ] Both should succeed

### Race Condition Test
- [ ] User 1: Start processing transaction C
- [ ] User 2: Start processing same transaction C (immediately after)
- [ ] Verify one succeeds, one gets handled gracefully

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 🧪 Test 10: Database Integrity

### Before Testing
- [ ] Count transactions with status=0: _______
- [ ] Count transactions with status=1: _______
- [ ] Count total customers: _______
- [ ] Count total vendors: _______

### After All Tests
- [ ] Verify transaction counts changed correctly
- [ ] Verify customer count increased by expected amount
- [ ] Verify vendor count increased by expected amount
- [ ] Check for orphaned records: _______
- [ ] Check for duplicate records: _______

### SQL Checks
```sql
-- Check transaction status distribution
SELECT status, COUNT(*) FROM bi_transactions GROUP BY status;

-- Check for orphaned transactions (should be none)
SELECT * FROM bi_transactions WHERE status = 1 AND fa_trans_no IS NULL;

-- Check recent customers
SELECT * FROM debtors_master ORDER BY debtor_no DESC LIMIT 5;

-- Check recent vendors
SELECT * FROM suppliers ORDER BY supplier_id DESC LIMIT 5;
```

**Status:** [ ] Pass [ ] Fail  
**Notes:** _______________________________________________

---

## 📊 Test Summary

| Test | Pass | Fail | Notes |
|------|------|------|-------|
| 1. Unset Transaction | [ ] | [ ] | |
| 2. Add Customer | [ ] | [ ] | |
| 3. Add Vendor | [ ] | [ ] | |
| 4. Toggle DC | [ ] | [ ] | |
| 5. Error Handling | [ ] | [ ] | |
| 6. Other POST Actions | [ ] | [ ] | |
| 7. Performance | [ ] | [ ] | |
| 8. Browser Compatibility | [ ] | [ ] | |
| 9. Concurrent Users | [ ] | [ ] | |
| 10. Database Integrity | [ ] | [ ] | |

**Total Passed:** ______ / 10  
**Total Failed:** ______ / 10  
**Pass Rate:** ______%

---

## 🚨 If Tests Fail

### Immediate Actions
1. **Don't panic!** - Rollback is 10 seconds away
2. **Toggle feature flag:**
   - Edit `command_bootstrap.php` line 20
   - Set `USE_COMMAND_PATTERN = false`
   - Save and refresh browser
3. **Verify legacy code works**
4. **Check error logs:**
   - PHP error log: _______________
   - Browser console: (F12)
5. **Document the issue:**
   - Which test failed: _______________
   - Error message: _______________
   - Steps to reproduce: _______________

### Investigation Steps
- [ ] Check unit tests: `vendor\bin\phpunit tests\unit\Commands\`
- [ ] Verify autoload: `composer dump-autoload`
- [ ] Check file permissions
- [ ] Verify database connection
- [ ] Check PHP version: `php -v`
- [ ] Review recent git commits

### Reporting
Create a detailed report including:
- [ ] Test name that failed
- [ ] Expected behavior
- [ ] Actual behavior
- [ ] Screenshots
- [ ] Error messages
- [ ] Browser console output
- [ ] PHP error log excerpt

---

## ✅ Sign-Off

### Deployment Approved
- [ ] All tests passed
- [ ] Performance acceptable
- [ ] No errors observed
- [ ] Database integrity verified
- [ ] Documentation complete

**Tested By:** _______________  
**Date:** _______________  
**Time:** _______________  
**Signature:** _______________

### Deployment Rejected
- [ ] Tests failed (see notes above)
- [ ] Performance issues
- [ ] Errors detected
- [ ] Rollback performed

**Reason for Rejection:** _______________________________________________

**Next Steps:** _______________________________________________

---

## 📝 Notes & Observations

Use this space for any additional notes, observations, or recommendations:

```
_________________________________________________________________

_________________________________________________________________

_________________________________________________________________

_________________________________________________________________

_________________________________________________________________
```

---

*Checklist Version: 1.0.0*  
*Generated: October 21, 2025*  
*Command Pattern Integration Testing*
