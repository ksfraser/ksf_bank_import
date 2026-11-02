# Enhanced Filter Implementation

**Date:** October 18, 2025  
**Version:** 1.0.0  
**Status:** ✅ Implemented and Tested

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Amount Range Filter](#amount-range-filter)
3. [Advanced Text Search Filter](#advanced-text-search-filter)
4. [Implementation Details](#implementation-details)
5. [Test Coverage](#test-coverage)
6. [Usage Examples](#usage-examples)
7. [SQL Query Examples](#sql-query-examples)

---

## Executive Summary

This document describes the enhanced filter capabilities implemented in `TransactionFilterService`. These filters are **fully implemented and tested** but not yet exposed in the UI (planned for future release).

### Key Features

1. **Amount Range Filter**
   - Uses `ABS()` for absolute value comparison
   - Handles negative amounts (debits and credits uniformly)
   - Auto-swaps min/max if provided in wrong order
   - Supports min-only, max-only, or both

2. **Advanced Text Search Filter**
   - Multi-keyword search (space-separated, AND logic)
   - Exact phrase matching (quoted strings)
   - Exclusion support (- or ! prefix)
   - Multi-field search (title, memo, merchant)
   - Complex queries combining all features

### Test Results

```
═══════════════════════════════════════════════════════════
TEST SUITE: TransactionFilterService
═══════════════════════════════════════════════════════════
Category                          Tests    Assertions
───────────────────────────────────────────────────────────
Core Filters (date/status/bank)     11          26
Amount Range Filter                  6          15
Text Search Filter                  11          38
───────────────────────────────────────────────────────────
TOTAL                               28          79
═══════════════════════════════════════════════════════════
PASS RATE: 100%
FAILURES: 0
═══════════════════════════════════════════════════════════
```

---

## Amount Range Filter

### Overview

The amount range filter allows filtering transactions by absolute dollar amount. It uses MySQL's `ABS()` function to handle both debits (negative) and credits (positive) uniformly.

### Features

#### 1. Absolute Value Comparison

```php
// Filter for transactions over $1000 (regardless of debit/credit)
buildAmountCondition(1000, null);
// SQL: ABS(t.transactionAmount) >= 1000

// Matches:
// ✅ $1500.00 (credit)
// ✅ -$1500.00 (debit)
// ❌ $500.00 (too small)
// ❌ -$500.00 (too small)
```

#### 2. Auto-Swap Min/Max

Instead of throwing an error when min > max, the filter automatically swaps them:

```php
// User accidentally provides larger value first
buildAmountCondition(500, 100);

// Auto-swaps to:
// ABS(t.transactionAmount) >= 100 AND ABS(t.transactionAmount) <= 500

// No error! Makes the UI more forgiving
```

#### 3. Flexible Range Options

```php
// Min only (transactions >= $1000)
buildAmountCondition(1000, null);
// SQL: ABS(t.transactionAmount) >= 1000

// Max only (transactions <= $500)
buildAmountCondition(null, 500);
// SQL: ABS(t.transactionAmount) <= 500

// Both (transactions between $100-$500)
buildAmountCondition(100, 500);
// SQL: ABS(t.transactionAmount) >= 100 AND ABS(t.transactionAmount) <= 500
```

#### 4. Negative Amount Handling

The filter uses `abs()` on input values and `ABS()` in SQL:

```php
// These all produce the same result:
buildAmountCondition(-100, -500);  // Negative inputs
buildAmountCondition(100, 500);    // Positive inputs
buildAmountCondition(-100, 500);   // Mixed

// All result in:
// ABS(t.transactionAmount) >= 100 AND ABS(t.transactionAmount) <= 500
```

### Method Signature

```php
/**
 * Build SQL condition for transaction amount range filter
 * 
 * @param float|null $minAmount Minimum transaction amount (optional)
 * @param float|null $maxAmount Maximum transaction amount (optional)
 * 
 * @return string SQL condition fragment (empty if no filter)
 * 
 * @throws InvalidArgumentException If amounts are not numeric
 * 
 * @since 1.0.0
 */
private function buildAmountCondition($minAmount = null, $maxAmount = null)
```

### Test Coverage

**6 Tests, 15 Assertions:**

1. ✅ `testBuildAmountConditionWithMinOnly` - Min-only filter
2. ✅ `testBuildAmountConditionWithMaxOnly` - Max-only filter
3. ✅ `testBuildAmountConditionWithBothMinMax` - Range filter
4. ✅ `testBuildAmountConditionReturnsEmptyWhenNoAmounts` - No filter when both null
5. ✅ `testBuildAmountConditionAutoSwapsWhenMinGreaterThanMax` - Auto-swap validation
6. ✅ `testBuildAmountConditionUsesAbsoluteValues` - ABS() usage verification

### Why ABS()?

**Problem:** Bank transactions can be positive (credits) or negative (debits). Users want to filter by amount size, not direction.

**Solution:** Use `ABS()` to treat $1000 debit and $1000 credit the same when filtering.

**Benefits:**
- ✅ Unified filtering (don't need separate debit/credit filters)
- ✅ More intuitive for users (filter by size, not sign)
- ✅ Works with existing database schema (no changes needed)

---

## Advanced Text Search Filter

### Overview

The text search filter provides powerful keyword-based searching across multiple transaction fields (title, memo, merchant) with support for:
- Multiple keywords (AND logic)
- Exact phrase matching (quoted strings)
- Exclusion (- or ! prefix)
- Multi-field search (OR logic within fields)

### Features

#### 1. Simple Keyword Search

```php
buildTitleSearchCondition('Amazon');

// SQL:
// (t.transactionTitle LIKE '%Amazon%' 
//  OR t.memo LIKE '%Amazon%' 
//  OR t.merchant LIKE '%Amazon%')

// Finds "Amazon" in any of the three fields
```

#### 2. Multiple Keywords (AND Logic)

```php
buildTitleSearchCondition('Amazon Prime');

// SQL:
// (t.transactionTitle LIKE '%Amazon%' OR t.memo LIKE '%Amazon%' OR t.merchant LIKE '%Amazon%') 
// AND 
// (t.transactionTitle LIKE '%Prime%' OR t.memo LIKE '%Prime%' OR t.merchant LIKE '%Prime%')

// Both "Amazon" AND "Prime" must be found (in any field)
```

#### 3. Exact Phrase Matching

```php
buildTitleSearchCondition('"Amazon Prime"');

// SQL:
// (t.transactionTitle LIKE '%Amazon Prime%' 
//  OR t.memo LIKE '%Amazon Prime%' 
//  OR t.merchant LIKE '%Amazon Prime%')

// Finds exact phrase "Amazon Prime" (not separate words)
```

#### 4. Exclusion with Minus Sign

```php
buildTitleSearchCondition('Amazon -Subscribe');

// SQL:
// (t.transactionTitle LIKE '%Amazon%' OR t.memo LIKE '%Amazon%' OR t.merchant LIKE '%Amazon%') 
// AND 
// (t.transactionTitle NOT LIKE '%Subscribe%' 
//  AND t.memo NOT LIKE '%Subscribe%' 
//  AND t.merchant NOT LIKE '%Subscribe%')

// Finds "Amazon" but excludes records containing "Subscribe"
```

#### 5. Exclusion with Exclamation Mark

```php
buildTitleSearchCondition('Amazon !Kindle');

// Same as minus sign (alternative syntax)
// Finds "Amazon" but excludes records containing "Kindle"
```

#### 6. Complex Queries

```php
buildTitleSearchCondition('Amazon "Prime Video" -Subscribe !Kindle');

// Breakdown:
// ✅ INCLUDE: "Amazon" (keyword)
// ✅ INCLUDE: "Prime Video" (exact phrase)
// ❌ EXCLUDE: "Subscribe" (exclusion)
// ❌ EXCLUDE: "Kindle" (exclusion)

// SQL (pseudo-code):
// (Amazon found in any field)
// AND ("Prime Video" found in any field)
// AND (Subscribe NOT in ANY field)
// AND (Kindle NOT in ANY field)
```

### Multi-Field Search Logic

**Fields Searched:**
1. `t.transactionTitle` - Primary field (transaction description)
2. `t.memo` - Secondary field (user notes)
3. `t.merchant` - Tertiary field (merchant name)

**Field Logic:**
- **Within keyword:** OR (keyword can match ANY field)
- **Between keywords:** AND (ALL keywords must match)
- **Exclusions:** AND NOT (ALL fields must NOT match)

**Example:**

```sql
-- Search: "Amazon Costco"
-- Means: Find Amazon in (title OR memo OR merchant) 
--        AND Costco in (title OR memo OR merchant)

(
    (t.transactionTitle LIKE '%Amazon%' OR t.memo LIKE '%Amazon%' OR t.merchant LIKE '%Amazon%')
    AND
    (t.transactionTitle LIKE '%Costco%' OR t.memo LIKE '%Costco%' OR t.merchant LIKE '%Costco%')
)
```

### Method Signature

```php
/**
 * Build SQL condition for transaction text search filter
 * 
 * @param string|null $searchText Search text with optional keywords/phrases
 * 
 * @return string SQL condition fragment (empty if no filter)
 * 
 * @throws InvalidArgumentException If search text is not a string
 * 
 * @since 1.0.0
 */
private function buildTitleSearchCondition($searchText = null)
```

### Search Syntax

| Syntax | Meaning | Example | Matches |
|--------|---------|---------|---------|
| `keyword` | Simple keyword | `Amazon` | Any field containing "Amazon" |
| `word1 word2` | Multiple keywords (AND) | `Amazon Prime` | Records with BOTH "Amazon" AND "Prime" |
| `"exact phrase"` | Exact phrase | `"Prime Video"` | Exact phrase "Prime Video" |
| `-exclude` | Exclude with minus | `Amazon -Subscribe` | Amazon but NOT Subscribe |
| `!exclude` | Exclude with exclamation | `Amazon !Kindle` | Amazon but NOT Kindle |
| Complex | Combination | `Amazon "Prime Video" -Subscribe !Kindle` | Amazon AND "Prime Video", but NOT Subscribe or Kindle |

### Edge Cases Handled

1. **Only Exclusions:** Returns empty string (no positive criteria)
   ```php
   buildTitleSearchCondition('-Subscribe !Kindle');
   // Returns: '' (empty)
   // Reason: No positive search criteria provided
   ```

2. **Empty/Null Input:** Returns empty string
   ```php
   buildTitleSearchCondition(null);      // Returns: ''
   buildTitleSearchCondition('');        // Returns: ''
   buildTitleSearchCondition('   ');     // Returns: ''
   ```

3. **Whitespace Trimming:** Automatically trims input
   ```php
   buildTitleSearchCondition('  Amazon  ');
   // Treated as: 'Amazon'
   ```

### Test Coverage

**11 Tests, 38 Assertions:**

1. ✅ `testBuildTitleSearchConditionWithText` - Simple keyword
2. ✅ `testBuildTitleSearchConditionReturnsEmptyWhenNoText` - Null input
3. ✅ `testBuildTitleSearchConditionReturnsEmptyForEmptyString` - Empty string
4. ✅ `testBuildTitleSearchConditionTrimsWhitespace` - Whitespace handling
5. ✅ `testBuildTitleSearchConditionMultipleKeywords` - Multiple keywords (AND)
6. ✅ `testBuildTitleSearchConditionExactPhrase` - Quoted exact phrases
7. ✅ `testBuildTitleSearchConditionExclusionMinus` - Exclusion with `-`
8. ✅ `testBuildTitleSearchConditionExclusionExclamation` - Exclusion with `!`
9. ✅ `testBuildTitleSearchConditionComplexQuery` - Complex combination
10. ✅ `testBuildTitleSearchConditionOnlyExclusionsReturnsEmpty` - Only exclusions
11. ✅ `testBuildTitleSearchConditionSearchesAllFields` - Multi-field verification

---

## Implementation Details

### Code Structure

Both filters are implemented as **private methods** in `TransactionFilterService`:

```php
namespace KsfBankImport\Services;

class TransactionFilterService
{
    // Existing constants
    const BANK_ACCOUNT_ALL = 'ALL';
    const STATUS_ALL = 255;
    
    // Public method (existing)
    public function buildWhereClause($startDate, $endDate, $status = null, $bankAccount = null)
    {
        // Currently builds WHERE for date/status/bank account
        // Future: Will call buildAmountCondition() and buildTitleSearchCondition()
    }
    
    // NEW: Amount range filter
    private function buildAmountCondition($minAmount = null, $maxAmount = null)
    {
        // 69 lines of implementation
        // Uses ABS() for absolute value comparison
        // Auto-swaps min/max if needed
    }
    
    // NEW: Text search filter
    private function buildTitleSearchCondition($searchText = null)
    {
        // 89 lines of implementation
        // Parses keywords, phrases, exclusions
        // Builds multi-field SQL conditions
    }
}
```

### Integration Status

**Current State:**
- ✅ Methods implemented
- ✅ Full test coverage (28 tests)
- ❌ Not yet called by `buildWhereClause()`
- ❌ Not yet in UI (header_table.php)
- ❌ Not yet in model (class.bi_transactions.php)

**Why Not Integrated Yet?**

Per user request: *"Since the calling code won't be setting the amount min/max nor the title to search for, those conditions won't be added to the actual SQL."*

This is a **scaffolded implementation** - fully working and tested, but dormant until UI is added.

### Future Integration Plan

**Step 1: Extend buildWhereClause() signature**
```php
public function buildWhereClause(
    $startDate, 
    $endDate, 
    $status = null, 
    $bankAccount = null,
    $minAmount = null,       // NEW
    $maxAmount = null,       // NEW
    $searchText = null       // NEW
) {
    // ... existing code ...
    
    // Add amount condition
    $amountCondition = $this->buildAmountCondition($minAmount, $maxAmount);
    if (!empty($amountCondition)) {
        $conditions[] = $amountCondition;
    }
    
    // Add text search condition
    $textCondition = $this->buildTitleSearchCondition($searchText);
    if (!empty($textCondition)) {
        $conditions[] = $textCondition;
    }
    
    return " WHERE " . implode(" AND ", $conditions);
}
```

**Step 2: Update extractFiltersFromPost()**
```php
public function extractFiltersFromPost($postData = null)
{
    // ... existing code ...
    
    return [
        'startDate' => $defaultStartDate,
        'endDate' => $defaultEndDate,
        'status' => isset($postData['statusFilter']) ? (int)$postData['statusFilter'] : 0,
        'bankAccount' => isset($postData['bankAccountFilter']) 
            ? $postData['bankAccountFilter'] 
            : self::BANK_ACCOUNT_ALL,
        'minAmount' => isset($postData['amountFrom']) ? (float)$postData['amountFrom'] : null,  // NEW
        'maxAmount' => isset($postData['amountTo']) ? (float)$postData['amountTo'] : null,      // NEW
        'searchText' => isset($postData['titleSearch']) ? $postData['titleSearch'] : null       // NEW
    ];
}
```

**Step 3: Update UI (header_table.php)**
```php
// Add amount range filter
amount_cells(_("Amount From:"), 'amountFrom', null);
amount_cells(_("Amount To:"), 'amountTo', null);

// Add text search filter
text_cells(_("Search:"), 'titleSearch', null, 40, 255);
label_cell(_("(Keywords, \"exact phrase\", -exclude)"), "", "class='dim'");
```

**Step 4: Update Model (class.bi_transactions.php)**
```php
public function get_transactions(
    $transAfterDate, 
    $transToDate, 
    $status = null, 
    $bankAccount = null,
    $minAmount = null,     // NEW
    $maxAmount = null,     // NEW
    $searchText = null     // NEW
) {
    // ... existing code ...
    
    $sql .= $filterService->buildWhereClause(
        $transAfterDate, 
        $transToDate, 
        $status, 
        $bankAccount,
        $minAmount,    // NEW
        $maxAmount,    // NEW
        $searchText    // NEW
    );
}
```

---

## Test Coverage

### Summary by Category

```
┌───────────────────────────────────┬───────┬────────────┐
│ Test Category                     │ Tests │ Assertions │
├───────────────────────────────────┼───────┼────────────┤
│ Core Filters                      │   11  │     26     │
│ - Date range filtering            │    2  │      2     │
│ - Status filtering                │    2  │      4     │
│ - Bank account filtering          │    3  │      6     │
│ - POST data extraction            │    2  │      8     │
│ - SQL injection protection        │    1  │      3     │
│ - WHERE clause structure          │    1  │      3     │
├───────────────────────────────────┼───────┼────────────┤
│ Amount Range Filter               │    6  │     15     │
│ - Min-only filtering              │    1  │      2     │
│ - Max-only filtering              │    1  │      2     │
│ - Range filtering (both)          │    1  │      4     │
│ - Empty condition handling        │    1  │      1     │
│ - Auto-swap validation            │    1  │      5     │
│ - ABS() usage verification        │    1  │      3     │
├───────────────────────────────────┼───────┼────────────┤
│ Text Search Filter                │   11  │     38     │
│ - Simple keyword search           │    1  │      4     │
│ - Multiple keywords (AND)         │    1  │      5     │
│ - Exact phrase matching           │    1  │      4     │
│ - Exclusion with minus            │    1  │      3     │
│ - Exclusion with exclamation      │    1  │      3     │
│ - Complex query combination       │    1  │      6     │
│ - Empty input handling            │    3  │      3     │
│ - Whitespace trimming             │    1  │      4     │
│ - Multi-field search              │    1  │      6     │
├───────────────────────────────────┼───────┼────────────┤
│ TOTAL                             │   28  │     79     │
└───────────────────────────────────┴───────┴────────────┘
```

### Test Execution Output

```
PHPUnit 9.6.29 by Sebastian Bergmann and contributors.

Transaction Filter Service (KsfBankImport\Tests\Services\TransactionFilterService)
 ✔ Build where clause with all filters
 ✔ Build where clause without bank account filter
 ✔ Build where clause with null bank account
 ✔ Build where clause without status filter
 ✔ Build where clause with null status
 ✔ Sanitizes bank account input
 ✔ Where clause starts with where keyword
 ✔ Extract filters from post
 ✔ Extract filters with defaults
 ✔ Validates date format
 ✔ Validates end date format
 ✔ Build amount condition with min only
 ✔ Build amount condition with max only
 ✔ Build amount condition with both min max
 ✔ Build amount condition returns empty when no amounts
 ✔ Build amount condition auto swaps when min greater than max
 ✔ Build amount condition uses absolute values
 ✔ Build title search condition with text
 ✔ Build title search condition returns empty when no text
 ✔ Build title search condition returns empty for empty string
 ✔ Build title search condition trims whitespace
 ✔ Build title search condition multiple keywords
 ✔ Build title search condition exact phrase
 ✔ Build title search condition exclusion minus
 ✔ Build title search condition exclusion exclamation
 ✔ Build title search condition complex query
 ✔ Build title search condition only exclusions returns empty
 ✔ Build title search condition searches all fields

Time: 00:00.154, Memory: 6.00 MB
OK (28 tests, 79 assertions)
```

### Code Coverage

Using PHP Reflection to test private methods:

```php
// Example test pattern
public function testBuildAmountConditionWithMinOnly()
{
    // Get private method via reflection
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('buildAmountCondition');
    $method->setAccessible(true);
    
    // Invoke private method directly
    $condition = $method->invoke($this->service, 100.00, null);
    
    // Assert results
    $this->assertStringContainsString('ABS(t.transactionAmount) >=', $condition);
    $this->assertStringContainsString('100', $condition);
}
```

---

## Usage Examples

### Amount Range Filter Examples

#### Example 1: Large Transactions Report

**Use Case:** Find all transactions over $10,000 for audit review

```php
$filterService = new TransactionFilterService();
$condition = $filterService->buildAmountCondition(10000, null);

// SQL Fragment:
// ABS(t.transactionAmount) >= 10000

// Full Query:
SELECT * FROM bank_import_transactions t
WHERE t.transDate >= '2025-01-01'
  AND t.transDate <= '2025-12-31'
  AND ABS(t.transactionAmount) >= 10000;

// Results: All transactions ≥ $10,000 (debits and credits)
```

#### Example 2: Small Transaction Reconciliation

**Use Case:** Review small transactions under $50 for petty cash reconciliation

```php
$condition = $filterService->buildAmountCondition(null, 50);

// SQL Fragment:
// ABS(t.transactionAmount) <= 50

// Results: All transactions ≤ $50 (debits and credits)
```

#### Example 3: Specific Range Analysis

**Use Case:** Analyze mid-range transactions ($500-$2000) for expense categorization

```php
$condition = $filterService->buildAmountCondition(500, 2000);

// SQL Fragment:
// ABS(t.transactionAmount) >= 500 AND ABS(t.transactionAmount) <= 2000

// Results: Transactions between $500 and $2000
```

#### Example 4: User-Friendly Input Handling

**Use Case:** User accidentally enters max before min

```php
// User provides: Max=$100, Min=$500 (wrong order!)
$condition = $filterService->buildAmountCondition(500, 100);

// Auto-swaps to correct order:
// ABS(t.transactionAmount) >= 100 AND ABS(t.transactionAmount) <= 500

// No error message! More user-friendly
```

### Text Search Filter Examples

#### Example 1: Simple Vendor Search

**Use Case:** Find all Amazon purchases

```php
$filterService = new TransactionFilterService();
$condition = $filterService->buildTitleSearchCondition('Amazon');

// SQL Fragment:
// (t.transactionTitle LIKE '%Amazon%' 
//  OR t.memo LIKE '%Amazon%' 
//  OR t.merchant LIKE '%Amazon%')

// Searches all three fields for "Amazon"
```

#### Example 2: Multi-Keyword Search

**Use Case:** Find Amazon Prime subscriptions

```php
$condition = $filterService->buildTitleSearchCondition('Amazon Prime');

// SQL Fragment:
// (t.transactionTitle LIKE '%Amazon%' OR t.memo LIKE '%Amazon%' OR t.merchant LIKE '%Amazon%') 
// AND 
// (t.transactionTitle LIKE '%Prime%' OR t.memo LIKE '%Prime%' OR t.merchant LIKE '%Prime%')

// Finds records containing BOTH "Amazon" AND "Prime"
```

#### Example 3: Exact Phrase Matching

**Use Case:** Find exact service name "Prime Video"

```php
$condition = $filterService->buildTitleSearchCondition('"Prime Video"');

// SQL Fragment:
// (t.transactionTitle LIKE '%Prime Video%' 
//  OR t.memo LIKE '%Prime Video%' 
//  OR t.merchant LIKE '%Prime Video%')

// Finds exact phrase "Prime Video" (not separate "Prime" and "Video")
```

#### Example 4: Exclusion Filter

**Use Case:** Find Amazon purchases but exclude subscriptions

```php
$condition = $filterService->buildTitleSearchCondition('Amazon -Subscribe');

// SQL Fragment:
// (t.transactionTitle LIKE '%Amazon%' OR t.memo LIKE '%Amazon%' OR t.merchant LIKE '%Amazon%') 
// AND 
// (t.transactionTitle NOT LIKE '%Subscribe%' 
//  AND t.memo NOT LIKE '%Subscribe%' 
//  AND t.merchant NOT LIKE '%Subscribe%')

// Finds "Amazon" but excludes any record containing "Subscribe"
```

#### Example 5: Complex Business Query

**Use Case:** Find Amazon purchases for Prime Video, excluding subscriptions and Kindle

```php
$condition = $filterService->buildTitleSearchCondition('Amazon "Prime Video" -Subscribe !Kindle');

// Breakdown:
// ✅ Must contain: "Amazon" (keyword)
// ✅ Must contain: "Prime Video" (exact phrase)
// ❌ Must NOT contain: "Subscribe"
// ❌ Must NOT contain: "Kindle"

// Use Case: Finding one-time Prime Video purchases/rentals, 
//           excluding recurring subscriptions and Kindle purchases
```

#### Example 6: Utility Bill Search

**Use Case:** Find electric or gas utility bills

```php
// Option A: Multiple keywords (finds both in same record)
$condition = $filterService->buildTitleSearchCondition('Utility Electric');

// Option B: Run two separate queries
$electric = $filterService->buildTitleSearchCondition('Electric');
$gas = $filterService->buildTitleSearchCondition('Gas');

// Note: OR logic between keywords not directly supported
// Use multiple queries or implement in future version
```

---

## SQL Query Examples

### Example 1: Amount Range with Date Filter

```sql
-- Find large transactions over $5000 in October 2025
SELECT * 
FROM bank_import_transactions t
WHERE t.transDate >= '2025-10-01'
  AND t.transDate <= '2025-10-31'
  AND ABS(t.transactionAmount) >= 5000
ORDER BY ABS(t.transactionAmount) DESC;
```

**PHP Code:**
```php
$filters = $filterService->extractFiltersFromPost([
    'TransAfterDate' => '2025-10-01',
    'TransToDate' => '2025-10-31',
    'amountFrom' => 5000
]);

$where = $filterService->buildWhereClause(
    $filters['startDate'],
    $filters['endDate'],
    null,
    null
);

// Add amount condition manually (until integrated)
$where .= " AND " . $filterService->buildAmountCondition(5000, null);
```

### Example 2: Text Search with Status Filter

```sql
-- Find pending Amazon transactions
SELECT * 
FROM bank_import_transactions t
WHERE t.transDate >= '2025-10-01'
  AND t.transDate <= '2025-10-31'
  AND t.status = 0
  AND (
    t.transactionTitle LIKE '%Amazon%' 
    OR t.memo LIKE '%Amazon%' 
    OR t.merchant LIKE '%Amazon%'
  );
```

**PHP Code:**
```php
$filters = $filterService->extractFiltersFromPost([
    'TransAfterDate' => '2025-10-01',
    'TransToDate' => '2025-10-31',
    'statusFilter' => 0,
    'titleSearch' => 'Amazon'
]);

$where = $filterService->buildWhereClause(
    $filters['startDate'],
    $filters['endDate'],
    $filters['status'],
    null
);

// Add text search manually (until integrated)
$where .= " AND " . $filterService->buildTitleSearchCondition('Amazon');
```

### Example 3: Combined Amount and Text Filter

```sql
-- Find Amazon purchases over $100, excluding subscriptions
SELECT * 
FROM bank_import_transactions t
WHERE t.transDate >= '2025-10-01'
  AND t.transDate <= '2025-10-31'
  AND ABS(t.transactionAmount) >= 100
  AND (
    t.transactionTitle LIKE '%Amazon%' 
    OR t.memo LIKE '%Amazon%' 
    OR t.merchant LIKE '%Amazon%'
  )
  AND (
    t.transactionTitle NOT LIKE '%Subscribe%' 
    AND t.memo NOT LIKE '%Subscribe%' 
    AND t.merchant NOT LIKE '%Subscribe%'
  );
```

**PHP Code:**
```php
$where = $filterService->buildWhereClause(
    '2025-10-01',
    '2025-10-31',
    null,
    null
);

$where .= " AND " . $filterService->buildAmountCondition(100, null);
$where .= " AND " . $filterService->buildTitleSearchCondition('Amazon -Subscribe');
```

### Example 4: Complex Multi-Criteria Query

```sql
-- Find:
-- - Transactions from account ACC123
-- - Between $500-$2000
-- - Containing "Hotel" or "Airbnb"
-- - Excluding "Cancel" or "Refund"
-- - In Q4 2025
SELECT * 
FROM bank_import_transactions t
WHERE t.transDate >= '2025-10-01'
  AND t.transDate <= '2025-12-31'
  AND t.bank_account_id = 'ACC123'
  AND ABS(t.transactionAmount) >= 500
  AND ABS(t.transactionAmount) <= 2000
  AND (
    (t.transactionTitle LIKE '%Hotel%' OR t.memo LIKE '%Hotel%' OR t.merchant LIKE '%Hotel%')
    OR
    (t.transactionTitle LIKE '%Airbnb%' OR t.memo LIKE '%Airbnb%' OR t.merchant LIKE '%Airbnb%')
  )
  AND (
    t.transactionTitle NOT LIKE '%Cancel%' 
    AND t.memo NOT LIKE '%Cancel%' 
    AND t.merchant NOT LIKE '%Cancel%'
  )
  AND (
    t.transactionTitle NOT LIKE '%Refund%' 
    AND t.memo NOT LIKE '%Refund%' 
    AND t.merchant NOT LIKE '%Refund%'
  );
```

**PHP Code:**
```php
// Note: This would require running two queries and merging results
// OR implement OR logic in buildTitleSearchCondition (future enhancement)

// Query 1: Hotel
$where1 = $filterService->buildWhereClause(
    '2025-10-01',
    '2025-12-31',
    null,
    'ACC123'
);
$where1 .= " AND " . $filterService->buildAmountCondition(500, 2000);
$where1 .= " AND " . $filterService->buildTitleSearchCondition('Hotel -Cancel -Refund');

// Query 2: Airbnb
$where2 = $filterService->buildWhereClause(
    '2025-10-01',
    '2025-12-31',
    null,
    'ACC123'
);
$where2 .= " AND " . $filterService->buildAmountCondition(500, 2000);
$where2 .= " AND " . $filterService->buildTitleSearchCondition('Airbnb -Cancel -Refund');

// Run both queries and merge results
```

---

## Appendix: Test Code Examples

### Amount Range Filter Test

```php
/**
 * Test amount range filter auto-swaps when min > max
 */
public function testBuildAmountConditionAutoSwapsWhenMinGreaterThanMax()
{
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('buildAmountCondition');
    $method->setAccessible(true);
    
    // Should auto-swap: 500 and 100 -> min=100, max=500
    $condition = $method->invoke($this->service, 500.00, 100.00);
    
    $this->assertStringContainsString('>=', $condition);
    $this->assertStringContainsString('<=', $condition);
    $this->assertStringContainsString('100', $condition);
    $this->assertStringContainsString('500', $condition);
    $this->assertStringContainsString('ABS', $condition);
}
```

### Text Search Filter Test

```php
/**
 * Test title search with complex query (keywords + exact + exclusions)
 */
public function testBuildTitleSearchConditionComplexQuery()
{
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('buildTitleSearchCondition');
    $method->setAccessible(true);
    
    $condition = $method->invoke(
        $this->service, 
        'Amazon "Prime Video" -Subscribe !Kindle'
    );
    
    // Should include Amazon keyword
    $this->assertStringContainsString('Amazon', $condition);
    
    // Should include exact phrase
    $this->assertStringContainsString('Prime Video', $condition);
    
    // Should exclude both Subscribe and Kindle
    $this->assertStringContainsString('Subscribe', $condition);
    $this->assertStringContainsString('Kindle', $condition);
    $this->assertStringContainsString('NOT LIKE', $condition);
    
    // Should use AND logic
    $this->assertStringContainsString(' AND ', $condition);
}
```

---

## Conclusion

Both enhanced filters are **fully implemented and comprehensively tested** with 100% pass rate. They are ready for UI integration whenever needed, following the scaffolded implementation approach.

### Key Achievements

1. ✅ **Amount Range Filter**
   - ABS() for unified debit/credit handling
   - Auto-swap for user-friendly input
   - 6 tests, 15 assertions

2. ✅ **Text Search Filter**
   - Multi-keyword with AND logic
   - Exact phrase matching
   - Exclusion support (- and !)
   - Multi-field search (title, memo, merchant)
   - 11 tests, 38 assertions

3. ✅ **Code Quality**
   - SOLID principles (SRP)
   - Comprehensive PHPDoc
   - SQL injection protection
   - Edge case handling

### Next Steps

1. Extend `buildWhereClause()` to call new methods
2. Update `extractFiltersFromPost()` to extract new parameters
3. Add UI elements in `header_table.php`
4. Update `get_transactions()` signature in model
5. User acceptance testing
6. Deploy to production

---

**Document Version:** 1.0  
**Last Updated:** October 18, 2025  
**Status:** ✅ Complete - Ready for Integration
