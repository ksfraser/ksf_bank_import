# Statement Validator - Business Rules Documentation

## Overview

The `StatementValidator` service validates parsed bank statements against 7 business rules before transformation into entities. All validation is non-destructive (collects errors in `ValidationResult`, does not throw exceptions).

## Business Rules

### Rule 1: Date Range Validation

**Name**: `validateDateRange()`

**Purpose**: Ensure statement period dates are valid and within acceptable range.

**Acceptance Criteria**:
- ✅ Both `startDate` and `endDate` are present (not null)
- ✅ Both dates are `DateTime` instances
- ✅ `startDate` ≤ `endDate`
- ✅ Date range ≤ 365 days (configurable)

**Rejection Criteria**:
- ❌ Either date is null
- ❌ Dates are not DateTime objects
- ❌ startDate > endDate (invalid range)
- ❌ Date range > maxDateRangeDays

**Example Valid Input**:
```php
startDate: 2024-01-01
endDate: 2024-01-31
Range: 30 days ✓
```

**Example Invalid Input**:
```php
startDate: 2024-12-31
endDate: 2024-01-01
Range: Invalid (start > end) ✗

startDate: 2023-01-01
endDate: 2025-02-01
Range: 397 days (exceeds 365) ✗
```

**Error Message**:
```
"Invalid date range: startDate (2024-12-31) is after endDate (2024-01-01)"
or
"Date range exceeds maximum: 397 days (max 365 allowed)"
```

---

### Rule 2: Amount Validation

**Name**: `validateAmounts()`

**Purpose**: Ensure all transactions have valid monetary amounts.

**Acceptance Criteria**:
- ✅ At least 1 transaction exists
- ✅ Every transaction has an amount field
- ✅ All amounts are numeric (int, float, or numeric string)
- ✅ Amounts should be non-zero (application-specific)

**Rejection Criteria**:
- ❌ No transactions in statement
- ❌ Any transaction missing amount field
- ❌ Any amount is null or non-numeric
- ❌ Invalid amount format (text, NaN, etc.)

**Example Valid Input**:
```php
Transaction 1: amount = 100.00 ✓
Transaction 2: amount = -50 ✓
Transaction 3: amount = "1250.50" (numeric string) ✓
```

**Example Invalid Input**:
```php
Transaction 1: amount = null ✗
Transaction 2: amount = "INVALID" ✗
Transaction 3: amount missing from array ✗
```

**Error Message**:
```
"No transactions present in statement"
or
"2 transaction(s) have missing or invalid amounts"
```

---

### Rule 3: Merchant Details Validation

**Name**: `validateMerchantDetails()`

**Purpose**: Ensure sufficient merchant/counterparty information for transaction tracking.

**Acceptance Criteria**:
- ✅ At least 50% of transactions have merchant OR beneficiary name
- ✅ Merchant field is non-empty string

**Rejection Criteria**:
- ❌ Fewer than 50% of transactions have merchant/beneficiary detail
- ❌ Merchant field is empty or all whitespace

**Example Valid Input**:
```php
Transaction 1: merchant = "Walmart" ✓              (50%)
Transaction 2: beneficiary = null ✓
Overall: 50% complete = Valid ✓
```

**Example Invalid Input**:
```php
Transaction 1: merchant = null ✗
Transaction 2: beneficiary = null ✗
Transaction 3: merchant = "" ✗
Overall: 0% complete = Invalid ✗
```

**Error Message**:
```
"Insufficient merchant details: only 25.0% of transactions have merchant/beneficiary"
```

**Rationale**: At least 50% merchant information allows for transaction categorization and reconciliation. Statements with >50% missing merchant names are typically corrupted or unusable.

---

### Rule 4: Transaction Count Validation

**Name**: `validateTransactionCount()`

**Purpose**: Ensure statement contains reasonable number of transactions (prevents corrupted/incomplete statements and resource exhaustion).

**Acceptance Criteria**:
- ✅ Transaction count ≥ `minTransactions` (default: 1)
- ✅ Transaction count ≤ `maxTransactions` (default: 10,000)

**Rejection Criteria**:
- ❌ Transaction count < minTransactions
- ❌ Transaction count > maxTransactions

**Example Valid Input**:
```php
Transaction count: 1,500
Min: 1, Max: 10,000
Result: Within range ✓
```

**Example Invalid Input**:
```php
Transaction count: 0
Min: 1
Result: Too low ✗

Transaction count: 20,000
Max: 10,000
Result: Too high ✗
```

**Error Message**:
```
"Transaction count too low: 0 (minimum 1 required)"
or
"Transaction count too high: 20000 (maximum 10000 allowed)"
```

**Configurable via**:
```php
$validator
    ->setMinTransactions(5)
    ->setMaxTransactions(5000);
```

---

### Rule 5: Account Reference Validation

**Name**: `validateAccountReference()`

**Purpose**: Ensure statement has identifiable source account.

**Acceptance Criteria**:
- ✅ Account reference is present (non-empty)
- ✅ Length between 8-20 characters
- ✅ Contains only alphanumeric, dash (-), or underscore (_)
- ✅ No spaces or special characters

**Rejection Criteria**:
- ❌ Account reference is empty or null
- ❌ Length < 8 or > 20 characters
- ❌ Contains invalid characters (spaces, symbols, etc.)

**Example Valid Input**:
```php
"ACC123456"     (8 chars, alphanumeric) ✓
"ACCOUNT-2024"  (12 chars, with dash) ✓
"ACC_001_US"    (10 chars, with underscore) ✓
```

**Example Invalid Input**:
```php
"ACC"           (3 chars, too short) ✗
"ACCOUNT_NUMBER_XYZ_2024" (23 chars, too long) ✗
"ACC #123"      (contains space and #) ✗
""              (empty) ✗
```

**Error Message**:
```
"Account reference is missing or empty"
or
"Account reference format invalid: 5 chars (8-20 required)"
or
"Account reference contains invalid characters"
```

---

### Rule 6: Currency Format Validation

**Name**: `validateCurrencyFormat()`

**Purpose**: Ensure statement currency is valid ISO 4217 code.

**Acceptance Criteria**:
- ✅ Currency code is present (non-empty)
- ✅ Exactly 3 uppercase letters (A-Z)
- ✅ Valid ISO 4217 code format

**Rejection Criteria**:
- ❌ Currency is empty or null
- ❌ Not exactly 3 characters
- ❌ Contains non-alphabetic or lowercase characters
- ❌ Not uppercase

**Example Valid Input**:
```php
"USD" ✓
"EUR" ✓
"GBP" ✓
"JPY" ✓
"CHF" ✓
```

**Example Invalid Input**:
```php
"usd"       (lowercase) ✗
"USDA"      (4 chars) ✗
"US"        (2 chars) ✗
"U$D"       (special char) ✗
""          (empty) ✗
```

**Error Message**:
```
"Currency code is missing or empty"
or
"Currency format invalid: \"USDA\" (must be 3-letter ISO code like USD, EUR)"
```

**Common ISO 4217 Codes**:
- **USD** - United States Dollar
- **EUR** - Euro
- **GBP** - British Pound
- **JPY** - Japanese Yen
- **CHF** - Swiss Franc
- **CAD** - Canadian Dollar
- **AUD** - Australian Dollar
- **CNY** - Chinese Yuan

---

### Rule 7: Duplicate Detection

**Name**: `validateDuplicateDetection()`

**Purpose**: Identify potential duplicate transactions (common import error).

**Acceptance Criteria**:
- ✅ No two transactions have identical amount + date + merchant (heuristic)

**Rejection Criteria** (Generates Warning, not Error):
- ⚠️ Two or more transactions match on: amount + date + merchant combination
- ⚠️ Suggests data quality issue but doesn't prevent import

**Example Valid Input**:
```php
Transaction 1: 2024-01-15, Walmart, 100.00 ✓
Transaction 2: 2024-01-15, Walmart, 99.99 ✓ (different amount)
Transaction 3: 2024-01-16, Walmart, 100.00 ✓ (different date)
No duplicates detected ✓
```

**Example Invalid Input - Generates Warning**:
```php
Transaction 1: 2024-01-15, Walmart, 100.00
Transaction 2: 2024-01-15, Walmart, 100.00 ⚠️ (exact duplicate)
Transaction 3: 2024-01-20, Store, 50.00

Potential duplicates detected ⚠️
```

**Warning Message**:
```
"Potential duplicates detected: 1 transaction(s) have matching amount/date/merchant"
```

**Important**:
- Duplicates generate **warnings** (not errors)
- Statement is still valid and will be imported
- User is alerted to review potential duplicates in reconciliation phase

---

## Validation Result Object

All rules return a `ValidationResult` object with:

```php
$result = $validator->validate($statement);

// Check if valid
$result->isValid();                  // true/false

// Get errors (blocking issues)
$result->hasErrors();                // true/false
$result->getErrorCount();            // int
$result->getErrors();                // array<string>

// Get warnings (non-blocking)
$result->hasWarnings();              // true/false
$result->getWarningCount();          // int
$result->getWarnings();              // array<string>

// Get rule summary
$result->getRulesSummary();          // ['dateRange' => 'pass', 'amounts' => 'fail', ...]

// Get validation timestamp
$result->getValidatedAt();           // DateTime

// Get human-readable summary
echo $result->getSummary();
// Output:
// Validation FAILED: 7 rules checked
// 2 error(s) found:
//   - Invalid date range: startDate (2024-12-31) is after endDate (2024-01-01)
//   - Currency format invalid: "INVALID" (must be 3-letter ISO code like USD, EUR)
// Warnings: 1 issue(s) found
//   - Potential duplicates detected: 1 transaction(s) have matching amount/date/merchant
```

---

## Usage Example

```php
use Ksfraser\FaBankImport\Import\Validators\StatementValidator;

$validator = new StatementValidator();

// Configure thresholds (optional)
$validator
    ->setMinTransactions(1)
    ->setMaxTransactions(5000)
    ->setMaxDateRangeDays(365);

// Validate statement
$result = $validator->validate($parsedStatement);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        logger()->error("Validation failed: $error");
    }
    // Do not import statement
    return false;
}

// Optional: check warnings
if ($result->hasWarnings()) {
    foreach ($result->getWarnings() as $warning) {
        logger()->warning("Validation warning: $warning");
    }
}

// Import statement (all rules passed)
$transformer->transform($parsedStatement);
```

---

## Bank-Specific Constraints (Future)

The validator can be extended with bank-specific rules:

| Bank | Min Transactions | Max Date Range | Required Fields |
|------|------------------|-----------------|-----------------|
| Generic | 1 | 365 days | amount, merchant, date |
| WMMC | 1 | 365 days | amount, reference | 
| BCR | 5 | 90 days | amount, merchant, account |
| ING | 1 | 365 days | amount, merchant, currency |

---

## Implementation Details

- **File**: `src/Ksfraser/FaBankImport/Import/Validators/StatementValidator.php`
- **Test File**: `tests/Unit/Validators/StatementValidatorTest.php`
- **Result DTO**: `src/Ksfraser/FaBankImport/Import/Results/ValidationResult.php`
- **Phase**: Phase 2.2.2
- **Dependency**: ParsedStatementDTO from Phase 2.2.1 (Parsers)
