# QFX Parser Integration Testing Guide

**Using Available Fixtures**: `/vendor/ksfraser/ksf_ofxparser/tests/fixtures/`

---

## 📁 Available Test Fixtures

| Fixture | Type | Description | Bank |
|---------|------|-------------|------|
| **ofxdata-atb-creditcard.ofx** | Credit Card | ✅ Real merchant data | ATB |
| **ofxdata-cibc-hisa.ofx** | HISA | ✅ Real transactions | CIBC |
| **ofxdata-cibc-visa.ofx** | Credit Card | ✅ Real credit card | CIBC |
| **ofxdata-capitalone-creditcard.ofx** | Credit Card | ✅ Real merchant data | CapitalOne |
| **ofxdata-rbc-savings.ofx** | Savings | ✅ Real statements | RBC |
| **ofxdata-rbc-visa-intl.ofx** | Credit Card | ✅ International | RBC |
| **ofxdata-FAKE-checking.ofx** | Checking | 🧪 Synthetic test data | FAKE |
| **ofxdata-FAKE-credit-card.ofx** | Credit Card | 🧪 Synthetic test data | FAKE |
| **ofxdata-FAKE-visa-intl.ofx** | Visa | 🧪 Synthetic test data | FAKE |
| **ofxdata-presco-mastercard.ofx** | MasterCard | ✅ Real merchant data | PRESCO |
| **ofxdata-google.ofx** | Test | 🧪 Known good data | Google |
| **ofxdata-simplii-savings.ofx** | Savings | ✅ Real transactions | Simplii |
| **ofx-multiple-accounts-xml.ofx** | Multi-Account | ✅ Multiple accounts | Generic |
| ... | | (32 more files) | |

---

## 🧪 Quick Testing Strategy

### Step 1: Manual Test with Single File

Use a real-world fixture to verify integration works:

**Test File**: `ofxdata-cibc-visa.ofx`  
**Why**: CIBC Visa includes:
- Real merchant names (good for deduplication testing)
- Mix of purchases and payments (both DEBIT and CREDIT)
- Multiple transactions to test transaction-to-contact linking

### Step 2: Test Different Scenarios

| Scenario | Fixture | Expected |
|----------|---------|----------|
| **Credit card purchases** | ofxdata-cibc-visa.ofx | DEBIT transactions → supplier contacts |
| **Card payments** | ofxdata-cibc-visa.ofx | CREDIT transactions → customer contacts |
| **Savings account** | ofxdata-rbc-savings.ofx | Mixed debits/credits → mixed contacts |
| **Multiple accounts** | ofx-multiple-accounts-xml.ofx | Multiple statements → multiple contacts |
| **Synthetic data** | ofxdata-FAKE-credit-card.ofx | Known data → predictable results |

---

## 🧪 Testing Checklist

### Manual Testing (30 min)

- [ ] **Setup**
  - [ ] Ensure database tables created (sql/update.sql executed)
  - [ ] Verify ContactService available
  - [ ] Clear 0_bi_contact table (for clean test)

- [ ] **Test 1: Single Import**
  - [ ] Open import_statements.php
  - [ ] Select QFX parser
  - [ ] Select bank account (or auto-detect)
  - [ ] Upload `ofxdata-cibc-visa.ofx`
  - [ ] Verify import succeeds
  - [ ] Check 0_bi_contact table: Should have 3-5 new rows
  - [ ] Check 0_bi_transactions: Should have new rows with contact_id populated

- [ ] **Test 2: Deduplication**
  - [ ] Import same file again
  - [ ] Check 0_bi_contact: Same 3-5 rows (no duplicates)
  - [ ] Check 0_bi_transactions: 2x transaction count, same contact_ids

- [ ] **Test 3: Transaction Direction**
  - [ ] For DEBIT transactions: Verify contact_type = 'supplier'
  - [ ] For CREDIT transactions: Verify contact_type = 'customer'
  - [ ] Query: `SELECT contact_type, COUNT(*) FROM 0_bi_contact GROUP BY contact_type`

- [ ] **Test 4: Error Handling**
  - [ ] Temporarily disable db connection
  - [ ] Verify import still works (graceful degradation)
  - [ ] Verify error logged in PHP error log
  - [ ] Restore db connection

---

## 📋 Command Line Testing (Optional)

### Run qfx_parser Against Fixture

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/qfx_parser.php';

// Read fixture
$content = file_get_contents(__DIR__ . '/vendor/ksfraser/ksf_ofxparser/tests/fixtures/ofxdata-cibc-visa.ofx');

// Parse
$parser = new qfx_parser();
$statements = $parser->parse($content, [
    'account' => '12345678',
    'account_code' => '1060',
    'currency' => 'CAD',
    'account_name' => 'Test Account'
], true);

// Verify
echo "Statements parsed: " . count($statements) . "\n";
foreach ($statements as $stmt) {
    echo "  Statement: {$stmt->statementId}\n";
    foreach ($stmt->transactions as $trx) {
        echo "    - {$trx->merchant}: ID={$trx->contact_id ?? 'NULL'}\n";
    }
}
?>
```

**Run**:
```bash
php test_qfx_parser.php
```

**Expected Output**:
```
Statements parsed: 1
  Statement: 2024-03-24-00000-1
    - AMAZON.COM: ID=1
    - WALMART SUPERMARKET: ID=2
    - CANADIAN AIRLINE: ID=3
    - AMAZON.COM: ID=1
```

---

## 🔍 Fixture File Inspection

### Read Fixture to Understand Format

```bash
# View first 50 lines of fixture
head -50 vendor/ksfraser/ksf_ofxparser/tests/fixtures/ofxdata-cibc-visa.ofx

# Search for merchant names in fixture
grep -i "NAME\|PAYEE\|DESC" vendor/ksfraser/ksf_ofxparser/tests/fixtures/ofxdata-cibc-visa.ofx | head -20
```

### Suggested Fixture Breakdown

**Real-World Fixtures (Use These First)**:
- ofxdata-cibc-visa.ofx → CIBC credit card with real merchants
- ofxdata-atb-creditcard.ofx → ATB with different merchant patterns
- ofxdata-capitalone-creditcard.ofx → CapitalOne with known brands

**Synthetic Fixtures (Use for Failure Testing)**:
- ofxdata-FAKE-credit-card.ofx → Predictable test data
- ofxdata-google.ofx → Known Google transaction

**Complex Fixtures (Use for Edge Cases)**:
- ofx-multiple-accounts-xml.ofx → Multiple simultaneous accounts
- ofxdata-memoWithAmpersand.ofx → Special characters
- ofxdata-memoWithQuotes.ofx → Quote handling

---

## 🚀 Test Execution Flow

### Quick Test (5 min)

```
1. Upload ofxdata-cibc-visa.ofx
2. Verify import completes
3. Query: SELECT COUNT(*) FROM 0_bi_contact WHERE created_at > NOW() - INTERVAL 10 MINUTE
4. Verify: Result > 0
```

### Complete Test (30 min)

```
1. Clear 0_bi_contact (for baseline)
2. Upload ofxdata-cibc-visa.ofx
3. Verify contacts created
4. Verify transaction linking
5. Upload same file again
6. Verify deduplication (no new contacts)
7. Check direction (DEBIT=supplier, CREDIT=customer)
8. Test error handling
```

### Full Test Suite (2 hours)

```
1. Test each of 5-10 fixture files
2. Verify each produces expected contact count
3. Test deduplication across files
4. Test error scenarios
5. Verify performance (large files)
6. Document results
```

---

## 📊 Expected Results by Fixture

### ofxdata-cibc-visa.ofx

**Input**: CIBC Visa credit card statement  
**Expected Transactions**: ~5-15  
**Expected Contacts**: ~3-8 (varies by deduplication)  
**Merchants**: Mix of retail, online, bills

**Query Results**:
```sql
SELECT 
    name, 
    contact_type, 
    COUNT(*) as transaction_count
FROM 0_bi_contact
GROUP BY name, contact_type
ORDER BY transaction_count DESC;

-- Expected:
-- AMAZON.COM CANADA | supplier | 2
-- WALMART CANADA | supplier | 1
-- BANK PAYMENT | customer | 1
-- ... etc
```

### ofxdata-FAKE-credit-card.ofx

**Input**: Synthetic test data  
**Expected Transactions**: Predictable  
**Expected Contacts**: Exact (no dedup variation)  
**Merchants**: Generic names (MERCHANT A, MERCHANT B, etc)

---

## 🐛 Troubleshooting

### Issue: No contacts created

```php
// Check if ContactService available
var_dump(class_exists('\Ksfraser\FaBankImport\Services\ContactService'));

// Check if db available
var_dump(isset($GLOBALS['db']));

// Check error_log
tail -f /var/log/php-errors.log
```

### Issue: Contacts created but not linked to transactions

```sql
-- Check if transactions have contact_id
SELECT COUNT(*) FROM 0_bi_transactions WHERE contact_id IS NOT NULL;
SELECT COUNT(*) FROM 0_bi_transactions WHERE contact_id IS NULL;

-- If all NULL: extractContactForTransaction not being called
-- If some NULL: Extraction failing for specific transactions
```

### Issue: Deduplication not working

```sql
-- Check if same name being created multiple times
SELECT name, COUNT(*) as count 
FROM 0_bi_contact 
GROUP BY name 
HAVING count > 1;

-- If results: Deduplication failing
-- Check dedup_hash and dedup logic
```

---

## 📝 Test Result Documentation

After running tests, document results:

```markdown
# QFX Parser Contact Extraction Test Results

## Test Date
2026-03-22

## Environment
- PHP: 8.0+
- Database: MySQL 5.7+
- ContactService: ✓ Available
- ContactDeduplicationService: ✓ Available

## Test Cases

### Test 1: ofxdata-cibc-visa.ofx
- Status: ✅ PASS
- Transactions: 12
- Contacts Created: 5
- Deduplication: ✅ Working
- Contact Types: 3 supplier, 2 customer

### Test 2: ofxdata-FAKE-credit-card.ofx
- Status: ✅ PASS
- Transactions: 8
- Contacts Created: 4
- Deduplication: ✅ Working
- Contact Types: 4 supplier

## Summary
- All tests passed ✅
- Contact extraction working correctly
- Deduplication functional
- Ready for production ✅
```

---

## 🔗 Related Files

- Contact Extraction: `/includes/qfx_parser.php` (lines 481-560)
- ContactService: `src/Ksfraser/FaBankImport/Services/ContactService.php`
- ContactDeduplicationService: `src/Ksfraser/FaBankImport/Services/ContactDeduplicationService.php`
- Test Fixtures: `vendor/ksfraser/ksf_ofxparser/tests/fixtures/`

---

## ✅ Verification Checklist

Before declaring integration complete:

- [ ] Imported multiple QFX files
- [ ] Verified contacts created in database
- [ ] Verified transactions linked to contacts
- [ ] Verified deduplication working
- [ ] Verified correct contact type (supplier/customer)
- [ ] Verified error handling graceful
- [ ] Checked error logs for issues
- [ ] Tested with real-world fixture
- [ ] Tested with synthetic fixture
- [ ] Documented results

---

**Status**: ✅ Testing fixtures available and ready to use  
**Next Action**: Run manual test with real-world fixture
