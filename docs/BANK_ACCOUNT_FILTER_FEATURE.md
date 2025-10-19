# Bank Account Filter Feature - Mantis Bug #3188

**Date:** October 18, 2025  
**Mantis Bug:** #3188  
**Feature:** Add bank account filter to process_statements screen

---

## Overview

Enhanced the transaction filtering on the `process_statements.php` screen to allow users to filter imported transactions by "Our bank account" in addition to existing date range and status filters.

---

## Problem Statement

**User Request:**
Users needed the ability to filter imported transactions by specific bank accounts. Previously, they could only filter by:
- Date range (From/To dates)
- Status (Settled/Unsettled/All)

This was insufficient when dealing with multiple bank accounts, as users had to scroll through all transactions across all accounts.

---

## Solution Implemented

### 1. Created TransactionFilterService (SRP)

**File:** `Services/TransactionFilterService.php` (268 lines)

**Design Pattern:** Single Responsibility Principle
- **Single job:** Build SQL WHERE clauses for transaction filtering
- **Benefits:**
  - Separates filtering logic from data access
  - Testable in isolation
  - Reusable across different views

**Key Methods:**
```php
// Build WHERE clause with all filter parameters
public function buildWhereClause($startDate, $endDate, $status, $bankAccount): string

// Extract filter parameters from POST data with defaults
public function extractFiltersFromPost($postData): array
```

**UML Class Diagram:**
```
┌─────────────────────────────────────────┐
│    TransactionFilterService             │
├─────────────────────────────────────────┤
│ + buildWhereClause(...): string         │
│ + extractFiltersFromPost(...): array    │
│ - validateDate(date, field): void       │
│ - shouldFilterByBankAccount(...): bool  │
│ - shouldFilterByStatus(...): bool       │
└─────────────────────────────────────────┘
```

### 2. Updated bi_transactions Model

**File:** `class.bi_transactions.php`

**Changes:**
- Added `$bankAccount` parameter to `get_transactions()` method
- Integrated `TransactionFilterService` to build WHERE clause
- Removed manual SQL WHERE construction (DRY principle)

**Before:**
```php
function get_transactions($status = null, $transAfterDate = null, $transToDate = null, ...) 
{
    $sql = " SELECT ... WHERE t.valueTimestamp >= ... AND t.valueTimestamp < ...";
    if ($status !== null) {
        $sql .= "  AND t.status = ...";
    }
    // Manual SQL construction
}
```

**After:**
```php
function get_transactions($status = null, $transAfterDate = null, $transToDate = null, ..., $bankAccount = null) 
{
    $filterService = new \KsfBankImport\Services\TransactionFilterService();
    $sql = " SELECT ...";
    $sql .= $filterService->buildWhereClause($transAfterDate, $transToDate, $status, $bankAccount);
    // Service handles all WHERE logic
}
```

### 3. Updated UI Header

**File:** `header_table.php`

**Changes:**
- Added bank account dropdown filter
- Default value: "ALL" (show all accounts)
- Uses existing `fa_bank_accounts_VIEW` component
- Properly positioned between Status filter and Search button

**New Filter UI:**
```
[From: date] [To: date] [Status: dropdown] [Bank Account: dropdown] [Search button]
```

---

## TDD Approach

### Step 1: RED - Write Failing Tests

Created `tests/TransactionFilterServiceTest.php` with 11 comprehensive tests:
1. ✅ Build WHERE clause with all filters
2. ✅ Build WHERE clause without bank account filter (ALL)
3. ✅ Build WHERE clause with null bank account
4. ✅ Build WHERE clause without status filter (255 = ALL)
5. ✅ Build WHERE clause with null status
6. ✅ Sanitize bank account input (SQL injection protection)
7. ✅ WHERE clause starts with WHERE keyword
8. ✅ Extract filters from POST data
9. ✅ Extract filters with defaults
10. ✅ Validate date format (start date)
11. ✅ Validate date format (end date)

**Initial test run:** FAILED (service didn't exist)

### Step 2: GREEN - Implement Service

Created `TransactionFilterService.php` with:
- `buildWhereClause()` method
- `extractFiltersFromPost()` method
- Input validation
- SQL injection protection
- Proper handling of "ALL" and null values

**Second test run:** ALL PASSED ✅

### Step 3: REFACTOR - Integrate Into Code

- Updated `class.bi_transactions.php` to use service
- Updated `header_table.php` to add UI filter
- Maintained backward compatibility

**Final test run:** ALL TESTS STILL PASSING ✅

---

## Test Results

```
PHPUnit 9.6.29 by Sebastian Bergmann and contributors.

Transaction Filter Service
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

OK (11 tests, 26 assertions)
```

---

## SOLID Principles Applied

### Single Responsibility Principle (SRP)
✅ **TransactionFilterService** has one job: build WHERE clauses
- Not responsible for database access
- Not responsible for rendering UI
- Not responsible for business logic

### Open/Closed Principle (OCP)
✅ Service is open for extension, closed for modification
- New filter types can be added without changing existing code
- Private helper methods make logic extensible

### Liskov Substitution Principle (LSP)
✅ Service can be replaced with mock for testing
- Dependency injection ready (though not currently using DI)

### Interface Segregation Principle (ISP)
✅ Small, focused public interface
- Only two public methods
- Each method has single, clear purpose

### Dependency Inversion Principle (DIP)
✅ Depends on abstractions (FA functions) not concrete implementations
- Uses function_exists() to check for FA functions
- Falls back to validation if FA functions not available

---

## MVC Architecture

**Model:** `class.bi_transactions.php`
- Data access layer
- Uses TransactionFilterService to construct queries
- Returns filtered transaction data

**View:** `header_table.php`
- Presentation layer
- Renders filter UI elements
- Collects user input

**Controller:** `process_statements.php`
- Application logic layer
- Orchestrates Model and View
- Handles form submission

**Service:** `TransactionFilterService.php`
- Business logic layer
- Encapsulates filtering rules
- Reusable across controllers

---

## SQL Injection Protection

The service properly escapes all user inputs:

**Test Case:**
```php
$bankAccount = "ACC123'; DROP TABLE bi_transactions; --";
$whereClause = $service->buildWhereClause('2025-10-01', '2025-10-31', 0, $bankAccount);
```

**Result:**
```sql
WHERE ... AND s.account = 'ACC123\'; DROP TABLE bi_transactions; --'
```

Single quotes are escaped, preventing SQL injection.

---

## Backward Compatibility

✅ **Fully backward compatible**
- `$bankAccount` parameter is optional (default: null)
- Null or 'ALL' values don't add bank account filter
- Existing code continues to work without changes
- Default behavior unchanged (shows all bank accounts)

---

## User Documentation

### How to Use Bank Account Filter

1. Navigate to **Bank Import > Process Statements**
2. Set date range using From/To date pickers
3. Select Status filter (Unsettled/Settled/All)
4. **NEW:** Select Bank Account filter
   - "ALL" - Show transactions from all bank accounts (default)
   - Specific account - Show only transactions from that account
5. Click **Search** button

### Example Use Cases

**Use Case 1: Review USD account transactions only**
- From: 2025-10-01
- To: 2025-10-31
- Status: Unsettled
- Bank Account: "USD Business Account"
- Result: Only unsettled USD account transactions in October

**Use Case 2: Reconcile specific CAD account**
- From: 2025-09-01
- To: 2025-10-01
- Status: All
- Bank Account: "CAD Operating Account"
- Result: All CAD operating account transactions in September

---

## Files Modified

### New Files:
1. **Services/TransactionFilterService.php** (268 lines)
   - SRP service for building WHERE clauses
   - Comprehensive PHPDoc and UML documentation
   
2. **tests/TransactionFilterServiceTest.php** (273 lines)
   - 11 comprehensive unit tests
   - 26 assertions
   - 100% pass rate

### Modified Files:
1. **class.bi_transactions.php**
   - Added `$bankAccount` parameter to `get_transactions()`
   - Integrated TransactionFilterService
   - Lines modified: ~20 lines (409-429)

2. **header_table.php**
   - Added bank account filter UI element
   - Added default value handling
   - Lines modified: ~10 lines (78-104)

---

## Next Steps

### Immediate:
- ✅ Unit tests passing
- ✅ Service created
- ✅ Model updated
- ✅ View updated
- ⏳ **Ready for real-world testing**

### Testing Checklist:
1. Test with "ALL" bank accounts (default behavior)
2. Test filtering by specific bank account
3. Test combination: Date + Status + Bank Account
4. Test with accounts that have no transactions
5. Test with special characters in account names
6. Test SQL injection attempts (security validation)

### Future Enhancements:
- Add "Recent Accounts" quick filter
- Add multi-account selection (checkbox list)
- Add account balance display in filter
- Cache bank account list for performance

---

##Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│              process_statements.php (Controller)         │
│                                                          │
│  1. Collect POST data                                    │
│  2. Pass to Model                                        │
│  3. Render View with data                                │
└──────────────┬───────────────────────────────┬───────────┘
               │                               │
               │ calls                         │ calls
               ▼                               ▼
┌──────────────────────────────┐   ┌────────────────────────┐
│   class.bi_transactions      │   │   header_table.php     │
│   (Model)                    │   │   (View)               │
│                              │   │                        │
│  - get_transactions()        │   │  - bank_import_header()│
│  - Uses FilterService        │   │  - Renders filters     │
└──────────────┬───────────────┘   └────────────────────────┘
               │
               │ uses
               ▼
┌────────────────────────────────────────┐
│   TransactionFilterService (Service)   │
│                                        │
│  - buildWhereClause()                  │
│  - extractFiltersFromPost()            │
│  - validateDate()                      │
│  - shouldFilterByBankAccount()         │
│  - shouldFilterByStatus()              │
└────────────────────────────────────────┘
```

---

## Commit Message

```
Add bank account filter to process_statements - Mantis #3188

FEATURE: Add bank account filter to transaction processing screen
MANTIS BUG: #3188
USER REQUEST: Filter imported transactions by specific bank account

SOLUTION:
- Created TransactionFilterService (SRP) for WHERE clause building
- Updated bi_transactions model to accept bankAccount parameter
- Updated header_table view to render bank account dropdown
- Default value: "ALL" (show all accounts)

TDD APPROACH:
- Step 1 (RED): Created 11 failing tests
- Step 2 (GREEN): Implemented TransactionFilterService
- Step 3 (REFACTOR): Integrated into existing code
- Result: All 11 tests passing (26 assertions)

SOLID PRINCIPLES:
- SRP: TransactionFilterService has one job
- OCP: Open for extension (new filters can be added)
- DIP: Depends on FA function abstractions
- MVC: Clean separation of concerns

SECURITY:
- SQL injection protection via db_escape()
- Input validation for all parameters
- Test coverage for malicious inputs

TESTING:
- TransactionFilterService: 11 tests, 26 assertions - ALL PASSING
- Coverage: filters, validation, defaults, SQL injection

FILES:
- Services/TransactionFilterService.php (NEW - 268 lines with UML)
- tests/TransactionFilterServiceTest.php (NEW - 273 lines)
- class.bi_transactions.php (MODIFIED - added $bankAccount param)
- header_table.php (MODIFIED - added filter UI)

RELATED: Mantis Bug #3188
```

---

**Status:** ✅ COMPLETE - Ready for testing  
**Tests:** 11/11 PASSING  
**Code Coverage:** Service fully tested  
**Documentation:** Complete with UML diagrams  

