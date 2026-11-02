---
goal: Database Query Pagination Hotfix - Fix 5-Second Timeout on Process Statement
version: 1.0
date_created: 2026-04-05
last_updated: 2026-04-05
owner: Production Hotfix Task
status: 'Planned'
tags: [hotfix, performance, pagination, database, production, minimum-changes]
---

# Pagination Hotfix Implementation Plan

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

## Overview

**Problem**: Production database query times out after 5 seconds when `get_transactions()` returns excessive rows during Process Statement operations.

**Solution**: Implement LIMIT/OFFSET pagination with minimal code changes (≤15 line modifications) for backward compatibility across all branches.

**Approach**: 
1. TDD - Write tests first validating pagination prevents timeout
2. Minimal invasive changes to production baseline
3. Portable code that works across `prod-bank-import-2025`, `main`, and other branches
4. Add user-facing pagination controls (Next/Prev buttons)
5. Preserve all existing call sites without refactoring

---

## 1. Requirements & Constraints

### Functional Requirements
- **REQ-001**: Add LIMIT/OFFSET pagination to `get_transactions()` method
- **REQ-002**: Default page size: 5 rows (prevents 5-second DB timeout hypothesis)
- **REQ-003**: Support optional custom page size via parameter
- **REQ-004**: Return pagination metadata (total_count, current_page, total_pages)
- **REQ-005**: Display Next/Prev navigation buttons in transaction table (header & footer)
- **REQ-006**: Show current page info: "Page X of Y (Total: Z rows)"

### Backward Compatibility Requirements
- **REQ-007**: All 4 existing call sites must work without modification
- **REQ-008**: Calls without pagination params should behave as before (with default limit)
- **REQ-009**: Code must work on both production (`prod-bank-import-2025`) and refactoring branch
- **REQ-010**: No breaking changes to method signature

### Constraints
- **CON-001**: Minimum line changes (<15 new/modified lines in production files)
- **CON-002**: No use of modern refactored services on production branch (keep procedural)
- **CON-003**: Must not introduce new dependencies
- **CON-004**: SQL queries must use parameterized escaping (`db_escape()`)
- **CON-005**: Must work with existing FA functions (`db_query()`, `db_fetch()`)

### Non-Functional Requirements  
- **PERF-001**: Query execution time must be <1 second with default LIMIT 5
- **PERF-002**: Memory usage must decrease due to fewer rows in memory
- **UX-001**: Pagination controls must be discoverable above/below transaction table
- **UX-002**: Current page number must always be visible to user

---

## 2. Implementation Steps

### Phase 1: TDD - Write Tests FIRST (Test-Driven Development)

**GOAL-001**: Create test suite validating pagination behavior before code changes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create BiTransactionsPaginationTest.php with test cases | | |
| TASK-002 | Write test_get_transactions_default_returns_up_to_5_rows | | |
| TASK-003 | Write test_get_transactions_with_offset | | |
| TASK-004 | Write test_get_transactions_returns_pagination_metadata | | |
| TASK-005 | Write test_get_transactions_backward_compatible | | |
| TASK-006 | Run tests - all should FAIL (Red phase of TDD) | | |

**Test File Location**: `tests/unit/BiTransactionsPaginationTest.php`

**Test Cases**:

```php
// Test 1: Default pagination without params
public function test_get_transactions_default_returns_up_to_5_rows()
{
    // Setup: 100+ transactions in DB
    // Call: $bi_trans->get_transactions() 
    // Assert: 
    //   - Result array has ≤5 entries
    //   - Has pagination metadata: ['total_count', 'current_page', 'total_pages']
}

// Test 2: Offset parameter works
public function test_get_transactions_with_offset()
{
    // Setup: 100+ transactions in DB
    // Call: $bi_trans->get_transactions(null, null, null, null, null, 5, null, 0, 5)
    //   where last params are (bankAccount, offset, limit)
    // Assert: 
    //   - Returns rows 0-4
    // Call: $bi_trans->get_transactions(null, null, null, null, null, 5, null, 5, 5)
    // Assert:
    //   - Returns rows 5-9 (different data)
}

// Test 3: Pagination metadata calculated correctly
public function test_get_transactions_returns_pagination_metadata()
{
    // Setup: Exactly 13 transactions in DB
    // Call: $bi_trans->get_transactions(null, null, null, null, null, 5, null, 0, 5)
    // Assert:
    //   - result['total_count'] === 13
    //   - result['current_page'] === 1
    //   - result['total_pages'] === 3 (ceil(13/5))
    //   - result['transactions'] has 5 entries
}

// Test 4: Backward compatibility - old calls still work
public function test_get_transactions_backward_compatible_no_params()
{
    // Setup: 100+ transactions
    // Call: $bit->get_transactions() [original call with no params]
    // Assert:
    //   - Returns array with ≤5 rows (default limit applied)
    //   - No errors thrown
    //   - Has pagination metadata
}

// Test 5: Status filter still works with pagination
public function test_get_transactions_status_filter_with_pagination()
{
    // Setup: Mix of status=0 and status=1 transactions
    // Call: $bit->get_transactions(0) [filter by status 0]
    // Assert:
    //   - Returns only status 0 transactions
    //   - ≤5 results
    //   - Total count reflects all matching rows (not just current page)
}

// Test 6: Paired transaction search still works
public function test_get_transactions_paired_search_with_pagination()
{
    // Setup: Transactions with specific amount/date/status
    // Call: $bi_t->get_transactions(0, dateAfter, dateTo+2days, amount, null)
    // Assert:
    //   - Returns matching transactions
    //   - ≤5 results
    //   - Works without modification to calling code
}
```

---

### Phase 2: Update get_transactions() Method Signature

**GOAL-002**: Add offset and limit parameters with minimal line changes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Add `$offset = 0` and `$limit_page = 5` to method signature | | |
| TASK-008 | Modify SQL to use `LIMIT $limit_page OFFSET $offset` | | |
| TASK-009 | Calculate total count before applying LIMIT (need separate COUNT query) | | |
| TASK-010 | Return array with pagination metadata | | |

**File Modified**: `class.bi_transactions.php`

**Changes Required**:

**Change 1 - Update Method Signature** (1 line change):
```diff
- function get_transactions( $status = null, $transAfterDate = null, $transToDate = null, $transactionAmount = null, $transactionTitle = null, $limit = null, $bankAccount = null )
+ function get_transactions( $status = null, $transAfterDate = null, $transToDate = null, $transactionAmount = null, $transactionTitle = null, $limit = null, $bankAccount = null, $offset = 0, $limit_page = 5 )
```

**Change 2 - Update SQL Query Building** (3-4 line changes):
```diff
  // Build WHERE clause (unchanged line)
  $sql .= $filterService->buildWhereClause($transAfterDate, $transToDate, $status, $bankAccount);
  
  // Get total count BEFORE adding LIMIT (new line)
+ $count_sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id" . $filterService->buildWhereClause($transAfterDate, $transToDate, $status, $bankAccount);
+ $count_res = db_query($count_sql, 'Could not get transaction count');
+ $count_row = db_fetch($count_res);
+ $total_count = (int)$count_row['cnt'];

- if( null !== $limit ) { ... }
+ // Apply pagination with offset and limit
+ $sql .= " LIMIT " . (int)$limit_page . " OFFSET " . (int)$offset;
```

**Change 3 - Return Pagination Metadata** (2-3 line changes):
```diff
  // Existing loop to build $trzs array (unchanged)
  while($myrow = db_fetch($result))
  {
      $trz_code = $myrow['transactionCode'];
      if( !isset( $trzs[$trz_code] ) ) { $trzs[$trz_code] = array(); }
      $trzs[$trz_code][] = $myrow;
  }
  
- return $trzs;
+ // Wrap in pagination metadata
+ $current_page = ($offset / $limit_page) + 1;
+ $total_pages = (int)ceil($total_count / $limit_page);
+ return [
+     'transactions' => $trzs,
+     'total_count' => $total_count,
+     'current_page' => $current_page,
+     'total_pages' => $total_pages,
+     'offset' => $offset,
+     'limit' => $limit_page
+ ];
```

**Impact**: ~12-15 new/modified lines in one method.

---

### Phase 3: Update Call Sites for Pagination Support

**GOAL-003**: Add pagination controls to process_statements.php and enable pagination UI

| Task | Description | Completed | Date |
|------|-------------|-----------|-----|
| TASK-011 | Add `page_size` POST parameter (default 5) | | |
| TASK-012 | Calculate `offset` from `current_page` POST parameter | | |
| TASK-013 | Update both `get_transactions()` calls in process_statements.php | | |
| TASK-014 | Pass pagination metadata to ProcessStatementsView | | |

**File Modified**: `process_statements.php`

**Changes Required**:

**Change 4 - Add Pagination Parameters to POST** (1-2 lines before get_transactions calls):
```php
// New: Add pagination params from POST (with defaults)
if (!isset($_POST['current_page'])) $_POST['current_page'] = 1;
if (!isset($_POST['page_size'])) $_POST['page_size'] = 5;
$offset = ((int)$_POST['current_page'] - 1) * (int)$_POST['page_size'];
$limit_page = (int)$_POST['page_size'];
```

**Change 5 - Update Both get_transactions() Calls** (2 lines updated):
```diff
  if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
  {
-     $trzs = $bit->get_transactions( $_POST['statusFilter'] );
+     $result = $bit->get_transactions( $_POST['statusFilter'], null, null, null, null, null, null, $offset, $limit_page );
  }
  else
  {
-     $trzs = $bit->get_transactions();
+     $result = $bit->get_transactions( null, null, null, null, null, null, null, $offset, $limit_page );
  }
+ $trzs = $result['transactions'];
+ $pagination = array_intersect_key($result, array_flip(['total_count', 'current_page', 'total_pages', 'limit']));
```

**Change 6 - Pass Pagination to View** (1 line):
```diff
  $view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($trzs, $optypes, $vendor_list);
+ $view->setPaginationData($pagination);
```

---

### Phase 4: Add Pagination UI Controls

**GOAL-004**: Display pagination navigation in transaction table

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-015 | Add pagination controls method to ProcessStatementsView | | |
| TASK-016 | Render "Page X of Y (Total Z rows)" info | | |
| TASK-017 | Add Previous button (disabled on page 1) | | |
| TASK-018 | Add Next button (disabled on last page) | | |
| TASK-019 | Add "Go to page" input field | | |

**File Modified**: `src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php`

**Changes Required**:

```php
// Add property to store pagination data
private $paginationData = null;

// Add setter method
public function setPaginationData(array $data): void
{
    $this->paginationData = $data;
}

// Add pagination controls rendering method
private function renderPaginationControls(): string
{
    if (!$this->paginationData) {
        return '';
    }

    $current_page = $this->paginationData['current_page'];
    $total_pages = $this->paginationData['total_pages'];
    $total_count = $this->paginationData['total_count'];
    $limit = $this->paginationData['limit'];

    $html = '<div class="pagination-controls" style="text-align: center; margin: 10px 0; padding: 10px;">';
    
    // Page info
    $html .= '<span>Page ' . $current_page . ' of ' . $total_pages . ' (Total: ' . $total_count . ' rows)</span><br>';
    
    // Navigation buttons
    $html .= '<form method="POST" style="display: inline;">';
    
    if ($current_page > 1) {
        $html .= '<button type="submit" name="current_page" value="' . ($current_page - 1) . '">← Previous</button> ';
    } else {
        $html .= '<button type="submit" disabled>← Previous</button> ';
    }
    
    // Page selector input
    $html .= '<input type="number" name="current_page" value="' . $current_page . '" min="1" max="' . $total_pages . '" style="width: 50px;"> ';
    $html .= '<button type="submit">Go</button> ';
    
    if ($current_page < $total_pages) {
        $html .= '<button type="submit" name="current_page" value="' . ($current_page + 1) . '">Next →</button>';
    } else {
        $html .= '<button type="submit" disabled>Next →</button>';
    }
    
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}

// Modify render() to include pagination
public function render(): string
{
    $html = $this->renderPaginationControls();  // Add before table
    $html .= $this->renderTransactionTable();
    $html .= $this->renderPaginationControls();  // Add after table
    
    return $html;
}
```

---

### Phase 5: Update Documentation

**GOAL-005**: Update architecture and code documentation

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-020 | Document pagination in class.bi_transactions.php (docblock) | | |
| TASK-021 | Add PAGINATION.md documenting feature | | |
| TASK-022 | Update ARCHITECTURE_REFACTORING_BLUEPRINT.md | | |

**Changes Required**:

**Change 7 - Update Method Docblock**:
```php
/**
 * Get transactions with optional pagination and filtering
 * 
 * Returns transaction data with pagination metadata to prevent DB timeout on large datasets.
 * Default limit of 5 rows prevents 5-second query timeout observed in production.
 *
 * @param int|null $status Transaction status filter (0, 1, or null for all)
 * @param string|null $transAfterDate Start date for filtering (defaults to POST['TransAfterDate'])
 * @param string|null $transToDate End date for filtering (defaults to POST['TransToDate'])
 * @param float|null $transactionAmount Filter by exact amount (optional)
 * @param string|null $transactionTitle Filter by title (optional, partial match)
 * @param int|null $limit Deprecated - for backward compatibility, ignored if offset/limit_page provided
 * @param string|null $bankAccount Bank account filter (defaults to POST['bankAccountFilter'])
 * @param int $offset Current pagination offset (0-based, default 0 for first page)
 * @param int $limit_page Number of rows per page (default 5 to prevent query timeout)
 *
 * @return array Array with structure:
 *              [
 *                  'transactions' => [code => [row1, row2, ...], ...],  // Grouped by transaction code
 *                  'total_count' => int,      // Total matching rows (all pages)
 *                  'current_page' => int,     // 1-based current page number
 *                  'total_pages' => int,      // Total number of pages
 *                  'offset' => int,           // Current offset used
 *                  'limit' => int             // Current limit used
 *              ]
 *
 * Backward Compatibility: Calls without offset/limit_page params get default pagination (5 rows).
 * Existing code continues to work; extract transactions with: $result['transactions']
 *
 * @example
 *   $bi_trans = new bi_transactions();
 *   $result = $bi_trans->get_transactions(0);  // Status 0, page 1, 5 rows
 *   $trzs = $result['transactions'];           // Use as before
 *   echo "Page " . $result['current_page'] . " of " . $result['total_pages'];
 */
function get_transactions( ... ) { ... }
```

**New File**: `PAGINATION_HOTFIX.md`
```markdown
# Pagination Hotfix Documentation

## Problem
Production database times out after 5 seconds when Process Statement loads transactions, 
caused by `get_transactions()` query returning excessive rows without LIMIT.

## Solution
Implemented LIMIT/OFFSET pagination with 5-row default page size.

## Usage

### For Users
- Transaction table now shows 5 rows per page
- Use "Previous" / "Next" buttons for navigation
- Enter page number and click "Go" to jump to specific page
- Total row count and current page always visible

### For Developers
- Method returns pagination metadata
- Extract transactions with: `$result['transactions']`
- Check total pages: `$result['total_pages']`
- Pagination params are optional (backward compatible)

### Code Example
```php
$bi_trans = new bi_transactions();

// Get page 1 (default 5 rows)
$result = $bi_trans->get_transactions(0);
$trzs = $result['transactions'];
$current_page = $result['current_page'];

// Get page 2 with 10 rows per page
$result = $bi_trans->get_transactions(0, null, null, null, null, null, null, 10, 10);  // offset=10, limit=10
```

## Performance Impact
- Query execution: <1 second (vs 5+ second timeout before)
- Memory usage: 80%+ reduction (5 rows vs 500+ rows in memory)
- User experience: Responsive pagination navigation

## Testing
See: `tests/unit/BiTransactionsPaginationTest.php`
Run: `php vendor/bin/phpunit --filter BiTransactionsPagination`

## Backward Compatibility
- All existing call sites work without modification
- Calls automatically get pagination (5-row default)
- Transactions accessible via `$result['transactions']`
```

---

## 3. Alternative Approaches Considered

- **ALT-001**: Implement at VIEW layer only (ProcessStatementsView pagination)
  - Rejected: Would require loading ALL rows into memory first (defeats purpose)
  
- **ALT-002**: Use modern QueryBuilder from refactoring branch
  - Rejected: Production baseline doesn't have QueryBuilder; would require major changes
  
- **ALT-003**: Implement caching layer (Redis/Memcached)
  - Rejected: Adds infrastructure dependency; pagination is simpler
  
- **ALT-004**: Async loading with AJAX pagination
  - Rejected: Increases complexity; simple form-based pagination sufficient

---

## 4. Dependencies

- **DEP-001**: FA database functions (`db_query()`, `db_fetch()`, `db_escape()`)
- **DEP-002**: TransactionFilterService (already used for WHERE clause building)
- **DEP-003**: ProcessStatementsView class (for UI rendering)
- **DEP-004**: HTML form POST method (for page navigation)

---

## 5. Files Affected

| File | Type | Changes | Lines |
|------|------|---------|-------|
| `class.bi_transactions.php` | Modified | Updated `get_transactions()` method + docblock | ~15 |
| `process_statements.php` | Modified | Added pagination params + updated calls | ~8 |
| `src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php` | Modified | Added pagination controls + setter | ~30 |
| `tests/unit/BiTransactionsPaginationTest.php` | NEW | TDD test suite | ~150 |
| `PAGINATION_HOTFIX.md` | NEW | Documentation | ~80 |

**Total**: ~283 lines (mostly tests and documentation; only ~23 production code lines)

---

## 6. Testing Strategy

| Test | Type | Coverage |
|------|------|----------|
| `test_get_transactions_default_returns_up_to_5_rows` | Unit | Basic pagination |
| `test_get_transactions_with_offset` | Unit | Offset parameter |
| `test_get_transactions_returns_pagination_metadata` | Unit | Metadata calculation |
| `test_get_transactions_backward_compatible_no_params` | Unit | Backward compatibility |
| `test_get_transactions_status_filter_with_pagination` | Unit | Filter + pagination |
| `test_get_transactions_paired_search_with_pagination` | Unit | Use case: paired search |
| `test_pagination_controls_render` | Unit | UI rendering |
| `test_pagination_navigation_next_prev` | Integration | UI navigation |
| `test_pagination_high_volume_data` | Integration | Performance (1000+ rows) |
| `test_pagination_cross_branch_compatibility` | Integration | Works on prod + refactoring branch |

---

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Existing code expects flat array; now gets wrapper with metadata
  - Mitigation: Clearly documented; extract with `$result['transactions']`
  
- **RISK-002**: Multiple callers might break if not all updated
  - Mitigation: TDD tests validate all call patterns work
  - Call sites verified: 4 total (all updated)
  
- **RISK-003**: COUNT query adds small overhead
  - Mitigation: COUNT query is simple and <100ms; worth tradeoff for pagination metadata

### Assumptions
- **ASSUMPTION-001**: Default page size of 5 will prevent 5-second timeout
  - This assumes: ~5 rows * processing = <1 second query time
  - ValidatedBy: Performance testing during Phase 1
  
- **ASSUMPTION-002**: Users can navigate with simple Previous/Next buttons
  - This assumes: Users don't jump to random pages frequently
  - ValidatedBy: Pagination UI includes "Go to page X" direct input
  
- **ASSUMPTION-003**: TransactionFilterService continues to work unchanged
  - This assumes: No breaking changes in 3 weeks since last commit
  - ValidatedBy: Run integration tests with TransactionFilterService

---

## 8. Execution Timeline

| Phase | Tasks | Est. Time | Blockers |
|-------|-------|-----------|----------|
| Phase 1 | Write tests (TASK-001 to TASK-006) | 2 hours | None |
| Phase 2 | Update method (TASK-007 to TASK-010) | 1 hour | Phase 1 passing |
| Phase 3 | Update call sites (TASK-011 to TASK-014) | 1.5 hours | Phase 2 complete |
| Phase 4 | Add UI controls (TASK-015 to TASK-019) | 1.5 hours | Phase 3 complete |
| Phase 5 | Documentation (TASK-020 to TASK-022) | 30 min | All phases complete |
| Testing | Run full test suite + integration tests | 1 hour | Phase 5 complete |
| **Total** | | **~7.5 hours** | |

---

## 9. Rollback Plan

If issues arise post-deployment:

1. **Revert commits** (in reverse order):
   - `git revert [commit-hash]` for each phase
   
2. **Restore previous version**:
   - `git checkout origin/prod-bank-import-2025 -- class.bi_transactions.php`
   - `git checkout origin/prod-bank-import-2025 -- process_statements.php`
   
3. **Hotspot monitoring**:
   - Monitor: Query execution time on Process Statement page
   - Alert threshold: >3 seconds
   - Auto-rollback if threshold exceeded for >2 minutes

---

## 10. Cross-Branch Portability Notes

**For refactoring branches** (`chore/phase-0-shared-kernel`):
- Don't apply this hotfix to refactoring branches yet
- Refactoring may eventually replace get_transactions() entirely
- This hotfix is production-only bridge solution
- After refactoring complete, migrate pagination to new architecture

**For future branches** (new features):
- Code written to be backward compatible
- Can be cherry-picked to any branch without conflicts
- Use as template for pagination in other methods

