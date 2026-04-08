# Test Refactoring Guide - Next Steps

## Current Status Summary

### ✅ WORKING: EnrichmentService Tests
- **Status**: 11/11 PASSING
- **No action needed** - This service is correctly implemented
- **Command**: `php .\vendor\bin\phpunit tests/unit/Services/Enrichment/EnrichmentServiceTest.php --no-coverage`

### ❌ NEEDS FIXES

#### 1. StatementProcessingServiceTest (12 tests, 9 failing)
**Issue**: Tests use `$statement->addTransaction()` which doesn't exist

**Current Pattern (WRONG)**:
```php
$statement = $this->buildTestStatement();
$statement->addTransaction(BiTransaction::fromDatabase([...], $statement));
```

**Correct Pattern**:
```php
$statement = BiStatement::fromDatabase([
    'id' => 1,
    'bank' => 'Test Bank',
    'account' => 'Chequing',
    'statementId' => 'STMT-001',
    'acctid' => 'ACC-001',
    'fitid' => 'FIT-001',
    'bankid' => 'BANK-001',
    'intu_bid' => 'INTU-001',
    'currency' => 'CAD',
    'startBalance' => 1000.00,
    'endBalance' => 1500.00,
    'smtDate' => '2024-01-01'
], [
    BiTransaction::fromDatabase([
        'id' => 1,
        'smt_id' => 1,
        'fitid' => 'TXN-001',
        'acctid' => 'ACC-001',
        'transactionAmount' => 100.00,
        'transactionTitle' => 'Payment',
        'valueTimestamp' => '2024-01-01'
    ]),
    // Add more transactions to array as needed
]);
```

**Methods Affected**:
- testProcessStatementWithSingleTransaction() - line 48
- testProcessStatementWithMultipleTransactions() - line 76
- testProcessMaintainsTransactionOrder() - line 105
- testProcessDeduplicatesTransactions() - line 136
- testProcessAllowsNegativeAmountsForDebits() - line 164
- testProcessFiltersZeroAmountTransactions() - line 191
- testProcessSkipsTransactionsWithMissingData() - line 233
- testProcessLargeStatementWithManyTransactions() - line 261
- testProcessHandlesCurrencyEdgeCases() - line 322

#### 2. ValidationServiceTest (21 tests, severity varies)
**Issue 1**: `isValidDate()` receives DateTime but expects string
- **Location**: ValidationService.php line 100
- **Fix**: Convert DateTime to string before passing

**Issue 2**: Transaction creation missing `acctid` field
- **Affected lines**: Various transaction creations
- **Fix**: Use correct fromDatabase() pattern (see above)

**Patterns to Fix**:
```php
// WRONG:
$statement->addTransaction(BiTransaction::fromDatabase([...], $statement));

// RIGHT:
// Build transactions array FIRST
$transactions = [
    BiTransaction::fromDatabase([...]),
    BiTransaction::fromDatabase([...])
];

// Create statement WITH transactions
$statement = BiStatement::fromDatabase([...], $transactions);
```

## Service Code Issues (Lower Priority)

### StatementProcessingService.php - Line 75
**Error**: Call to undefined method `getAmount()`  
**Should be**: `getTransactionAmount()`

```php
// CURRENT (WRONG):
if ((float) $transaction->getAmount() !== 0.0) {

// SHOULD BE:
if ((float) $transaction->getTransactionAmount() !== 0.0) {
```

### ValidationService.php - Line 100
**Error**: Method signature expects string, receives DateTime

```php
// CURRENT (WRONG):
$this->isValidDate($transaction->getValueTimestamp());

// POSSIBLE FIX:
$date = $transaction->getValueTimestamp();
$this->isValidDate($date instanceof DateTime ? $date->format('Y-m-d') : '');
```

## Refactoring Checklist

### Step 1: Fix Field Names (DONE ✅)
- ✅ BiTransaction uses correct database field names
- ✅ `acctid` instead of `acctId`
- ✅ `smt_id` instead of `smtId`
- ✅ `transactionAmount` instead of `amount`

### Step 2: Fix Test Immutable Pattern (PENDING)
- [ ] StatementProcessingServiceTest - Rewrite 9 methods
- [ ] ValidationServiceTest - Rewrite/fix all methods using addTransaction()
- [ ] Integration tests - Use correct creation pattern

### Step 3: Fix Service Methods (PENDING)
- [ ] StatementProcessingService - Change `getAmount()` → `getTransactionAmount()`
- [ ] ValidationService - Handle DateTime → string conversion

### Step 4: Validation (PENDING)
- [ ] Run ProcessingService tests
- [ ] Run ValidationService tests
- [ ] Run Integration tests
- [ ] Run E2E tests
- [ ] Run full suite

## Key Learning: BiStatement Immutability

**CRITICAL**: BiStatement and BiTransaction are immutable value objects!

```php
// ✅ CORRECT: Create with all data upfront
$stmt = BiStatement::fromDatabase(
    [statement_data],
    [transaction1, transaction2, transaction3]  // Transactions in array!
);

// ❌ WRONG: Cannot add after creation
$stmt = BiStatement::fromDatabase([statement_data], []);
$stmt->addTransaction($tx);  // ERROR: Method doesn't exist!
```

## Quick Reference: Field Names

### BiStatement
```php
BiStatement::fromDatabase([
    'id' => 1,
    'bank' => 'Test Bank',
    'account' => 'Chequing',
    'statementId' => 'STMT-001',
    'acctid' => 'ACC-001',        // ← lowercase!
    'fitid' => 'FIT-001',         // ← lowercase!
    'bankid' => 'BANK-001',       // ← lowercase!
    'intu_bid' => 'INTU-001',     // ← lowercase!
    'currency' => 'CAD',
    'startBalance' => 1000.00,
    'endBalance' => 1500.00,
    'smtDate' => '2024-01-01'
], [])
```

### BiTransaction
```php
BiTransaction::fromDatabase([
    'id' => 1,
    'smt_id' => 1,                // ← underscore, not camelCase!
    'fitid' => 'TXN-001',         // ← lowercase!
    'acctid' => 'ACC-001',        // ← lowercase! REQUIRED!
    'transactionAmount' => 100.00,// ← camelCase here
    'transactionTitle' => 'Desc', // ← camelCase here
    'valueTimestamp' => '2024-01-01'
])
```

## Files to Modify

1. `tests/unit/Services/Processing/StatementProcessingServiceTest.php`
   - 9 methods need rewriting
   - Estimated 20-30 minutes

2. `tests/unit/Services/Validation/ValidationServiceTest.php`
   - Multiple methods need fixing
   - Also needs service code updates
   - Estimated 30-45 minutes

3. `src/Ksfraser/FaBankImport/Import/Services/Processing/StatementProcessingService.php`
   - Change `getAmount()` to `getTransactionAmount()`
   - Estimated 5 minutes

4. `src/Ksfraser/FaBankImport/Import/Services/Validation/ValidationService.php`
   - Handle DateTime → string conversion
   - Estimated 10-15 minutes

## Testing Commands

```bash
# Test individual service
php .\vendor\bin\phpunit tests/unit/Services/Enrichment/EnrichmentServiceTest.php --no-coverage

# Test all unit services (once fixed)
php .\vendor\bin\phpunit tests/unit/Services/ --no-coverage

# Run with XML output for parsing
php .\vendor\bin\phpunit tests/unit/Services/ --no-coverage --log-junit=results.xml

# Check syntax before running
php -l tests/unit/Services/Processing/StatementProcessingServiceTest.php
```

## Expected Results After Fixes

```
EnrichmentService:       11/11 PASSING ✅
ProcessingService:       12/12 PASSING (after fixes)
ValidationService:       21/21 PASSING (after fixes) 
Integration Tests:       5/5 PASSING (after service fixes)
E2E Tests:              8/8 PASSING (after service fixes)

TOTAL: 57 tests PASSING
```

## Final Notes

- **BiStatement/BiTransaction are immutable** - This is intentional design!
- **No backward compat method**: `addTransaction()` will never exist
- **All data must be provided at creation**: This is how these entities work
- **Entity pattern is correct**: No changes needed to entity code
- **Service code has bugs**: Wrong method names need fixing

The test files are now correctly structured; just need refactoring for immutability pattern.
