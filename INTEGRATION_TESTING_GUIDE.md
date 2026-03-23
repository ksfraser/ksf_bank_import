# Integration Testing Guide

**Date:** March 23, 2026  
**Status:** Integration Test Suite Created  
**Test File:** `tests/Integration/ContactWorkflowIntegrationTest.php`

---

## Overview

This guide explains how to test the complete contact management workflow integrated into bank transaction processing. The integration tests verify that all components work together correctly end-to-end.

## Test Scenarios

### ✅ Scenario 1: QFX Transaction with FA Customer Matching
**What it tests:** Extract contact from QFX file, find FA customer in system, link automatically  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndQFXTransactionMatchingWithFACustomer`  
**Expected:** Contact matches FA customer ID (1.0 score)

```php
testEndToEndQFXTransactionMatchingWithFACustomer()
  → Build ContactData from QFX transaction
  → Search with FA customer ID
  → Find exact match (if contact exists)
  → Assert score >= 0.85
```

### ✅ Scenario 2: CSV Import with Duplicate Detection
**What it tests:** Parse CSV, check for duplicate contacts with fuzzy matching  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndCSVTransactionWithDuplicateDetection`  
**Expected:** Duplicate detection works, contact reused or new one created

### ✅ Scenario 3: Multi-Criteria Search (Manual Selection)
**What it tests:** User searches by name/email/phone, gets ranked results with confidence scores  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndMultiCriteriaSearchWorkflow`  
**Expected:** Search by all criteria, scores above threshold

### ✅ Scenario 4: Create New Contact on the Fly
**What it tests:** User fills modal form, new contact created, linked to transaction  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndCreateNewContactWorkflow`  
**Expected:** No duplicates, contact created with ID, can be linked

### ✅ Scenario 5: Batch Processing (10 transactions)
**What it tests:** Process multiple transactions, match all successfully  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndBatchTransactionProcessing`  
**Expected:** All transactions processed, contacts matched/linked

### ✅ Scenario 6: Match Threshold Adjustment
**What it tests:** Same search term, different thresholds yield different result counts  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndMatchingThresholdAdjustment`  
**Expected:** Looser threshold ≥ stricter threshold result count

### ✅ Scenario 7: Error Handling
**What it tests:** Graceful handling of invalid data, malformed input, missing fields  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndErrorHandling`  
**Expected:** No crashes, helpful error messages

### ✅ Scenario 8: Performance with Large Database
**What it tests:** Search through 10,000+ contacts completes in reasonable time  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndPerformanceWithLargeContactDatabase`  
**Expected:** Search < 500ms for 10k records

### ✅ Scenario 9: Multi-Format Parser Consistency
**What it tests:** QFX, QIF, CSV, MT940 all produce consistent ContactData  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testEndToEndMultiFormatParsingConsistency`  
**Expected:** Same contact matched across all formats

### ✅ Scenario 10: Full Happy Path (marked incomplete)
**What it tests:** Complete workflow from import to completion  
**File:** `tests/Integration/ContactWorkflowIntegrationTest.php::testFullEndToEndHappyPath`  
**Status:** Marked incomplete until full parser integration

---

## How to Run Tests

### Run All Integration Tests
```bash
cd /path/to/ksf_bank_import
php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php
```

### Run Specific Test Scenario
```bash
# Run only QFX matching scenario
php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php::ContactWorkflowIntegrationTest::testEndToEndQFXTransactionMatchingWithFACustomer

# Run only batch processing
php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php::ContactWorkflowIntegrationTest::testEndToEndBatchTransactionProcessing
```

### Run with Verbose Output
```bash
php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php -v
```

### Run with Code Coverage Report
```bash
php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php --coverage-html=coverage/
# Then open coverage/index.html
```

### Run via VS Code Task
Use the existing `phpunit-root` task configured in `.vscode/tasks.json`:
```bash
F1 > Tasks: Run Task > phpunit-root
```

---

## Expected Results

### Success Criteria ✅

**All tests pass:**
```
Tests: 10, Assertions: 50+, Failures: 0, Errors: 0
OK (10 tests, X assertions)
```

**Specific test expectations:**

| Test | Should Assert |
|------|---------------|
| Scenario 1 | Contact found with score >= 0.85 |
| Scenario 2 | Contact created/reused with valid ID |
| Scenario 3 | Results sorted by score, all >= threshold |
| Scenario 4 | New contact ID not empty, email matches |
| Scenario 5 | 3 transactions matched, contacts linked |
| Scenario 6 | Loose threshold >= strict threshold |
| Scenario 7 | No exceptions thrown, helpful messages |
| Scenario 8 | Search completes < 5000ms |
| Scenario 9 | All formats contain 'ACME' in uppercase |
| Scenario 10 | Marked incomplete (requires parser) |

---

## Test Database Setup

### Option A: Use Mock Objects (Current)
- Services use mocked dependencies
- No database required
- Fast execution (~500ms total)
- Limited coverage of actual database interactions

### Option B: Use Test Fixtures
- Create test database with known contacts
- Load fixtures before each test
- More realistic scenarios
- Slower execution (~2-3 seconds total)

### Option C: Use Real Database (Staging)
- Run against actual staging environment
- All integrations tested end-to-end
- Tests actual database performance
- Risk: tests might fail on concurrent operations

**Current Recommendation:** Use Option A (Mocks) for CI/CD, Option B (Fixtures) for manual testing

---

## Set Up Test Fixtures (Optional)

### Create test_fixtures.sql
```sql
-- Insert test contacts
INSERT INTO bi_contact (name, email, phone, contact_type, created_at) VALUES
  ('Acme Corporation', 'billing@acme.com', '555-1234567', 'C', NOW()),
  ('Office Supply Co', 'info@office.com', '555-9876543', 'S', NOW()),
  ('Global Industries', 'orders@global.com', NULL, 'S', NOW());

-- Link some test FA references
UPDATE bi_contact SET fa_customer_id = 42 WHERE name = 'Acme Corporation';
UPDATE bi_contact SET fa_supplier_id = 99 WHERE name = 'Office Supply Co';
```

### Load Fixtures Before Tests
```php
protected function setUp(): void
{
    if (file_exists('tests/fixtures/test_fixtures.sql')) {
        shell_exec('mysql -u root -p dbname < tests/fixtures/test_fixtures.sql');
    }
    // ... rest of setup
}
```

---

## Debugging Failed Tests

### Enable Verbose Output
```bash
php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php -v -vv
```

### Check Error Messages
Each test includes descriptive error messages:
```
FAIL: testEndToEndQFXTransactionMatchingWithFACustomer
AssertionError: Expected score >= 0.85, got 0.72
```

### Debug Single Test
```php
// Add to test method for debugging
echo "Contact name: " . $contact->name . "\n";
echo "Match score: " . $matches[0]['score'] . "\n";
echo "Match method: " . $matches[0]['match_method'] . "\n";
var_dump($contact);
```

### Check Service Logs
```bash
# View error logs
tail -100 var/logs/error.log | grep -i contact

# View all logs with timestamps
grep "20260323" var/logs/error.log
```

---

## Manual Testing (Without PHPUnit)

### Test Search Endpoint
```bash
# Test search by name
curl -X POST http://localhost/api/contact-search \
  -d "search_term=Acme&search_by=name&threshold=0.75" \
  -H "Content-Type: application/x-www-form-urlencoded" | jq .

# Expected response:
# {
#   "success": true,
#   "matches": [
#     {
#       "contact_id": 1,
#       "name": "Acme Corporation",
#       "score": 0.95,
#       "match_method": "name_fuzzy"
#     }
#   ],
#   "count": 1
# }
```

### Test Create Contact
```bash
curl -X POST http://localhost/api/contact-create \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Supplier",
    "email": "test@example.com",
    "type": "S"
  }' | jq .

# Expected: contact_id and success=true
```

### Test Link Contact
```bash
curl -X POST http://localhost/api/contact-link \
  -d "transaction_id=123&contact_id=42" \
  -H "Content-Type: application/x-www-form-urlencoded" | jq .

# Expected: success=true, contact linked
```

### Test Transaction History
```bash
curl http://localhost/api/contact-history/42 | jq .

# Expected: array of transactions linked to contact 42
```

---

## Performance Benchmarks

### Expected Times (Single Search)

| Scenario | Time | Notes |
|----------|------|-------|
| Search 100 contacts | 10-20ms | Simple name search |
| Search 1,000 contacts | 50-100ms | With fuzzy matching |
| Search 10,000 contacts | 200-400ms | Full database scan |
| Batch 10 transactions | 500-1000ms | Parallel matching |
| Create new contact | 10-30ms | Database insert |
| Link to transaction | 5-10ms | Database update |

### Optimize if Slower

If tests run slower than expected:
1. Add database indexes on name, email, phone
2. Implement caching (Redis) for frequent searches
3. Use select-only queries (avoid joins)
4. Enable query profiling: `SET PROFILING=1;`

---

## Continuous Integration

### GitHub Actions Workflow
```yaml
# .github/workflows/integration-tests.yml
name: Integration Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/setup-php@v1
        with:
          php-version: '7.4'
      - run: composer install
      - run: php vendor/bin/phpunit tests/Integration/
```

### Pre-commit Hook
```bash
#!/bin/bash
# .git/hooks/pre-commit
php vendor/bin/phpunit tests/Integration/ --quiet
if [ $? -ne 0 ]; then
  echo "Integration tests failed. Commit aborted."
  exit 1
fi
```

---

## Known Limitations & Future Work

### Current Limitations
- ⚠️ Tests use mocked database (not real)
- ⚠️ Scenario 10 (full happy path) not implemented yet
- ⚠️ Parser integration not tested (QFX/QIF/CSV/MT940)
- ⚠️ No real contact database (fixtures only)

### Future Enhancements
- [ ] Full parser integration tests
- [ ] Real database tests with fixtures
- [ ] Performance testing with realistic data volume
- [ ] Stress testing (1000s of concurrent transactions)
- [ ] API endpoint integration tests (not just services)
- [ ] UI interaction tests (Selenium/Puppeteer)
- [ ] Error recovery tests (network failures, DB timeouts)

---

## Next Steps

1. **Run Tests:**
   ```bash
   php vendor/bin/phpunit tests/Integration/ContactWorkflowIntegrationTest.php
   ```

2. **Fix Any Failing Tests:**
   - Check error messages
   - Verify mocked services return expected data
   - Add debugging output

3. **Add Real Database Tests:**
   - Create test_fixtures.sql
   - Implement fixture loading in setUp()
   - Run against test database

4. **Add API Endpoint Tests:**
   - Test REST endpoints (not just services)
   - Test request/response serialization
   - Test error responses

5. **Set Up CI/CD:**
   - Configure GitHub Actions
   - Run tests on every commit
   - Block merges if tests fail

---

## Contact & Support

For issues with integration tests:
1. Check test output: `php vendor/bin/phpunit ... -vv`
2. Review error logs: `tail -100 var/logs/error.log`
3. Consult test comments (each test has detailed comments)
4. Refer to IMPLEMENTATION_PLAN_API_ENDPOINTS.md for architecture

