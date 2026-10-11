# Architecture Migration Guide

## Overview

This document describes the migration from the legacy bi_transactions_model architecture to a SOLID-compliant architecture using TransactionQueryBuilder and TransactionRepository.

**Document Version:** 20251104.1  
**Author:** Kevin Fraser / GitHub Copilot  
**Date:** November 4, 2025

## Current Architecture (Legacy)

### Problems

1. **Violation of Single Responsibility Principle (SRP)**
   - bi_transactions_model mixes: entity data, SQL generation, query execution, and business logic
   - Changes to SQL require modifying the model class
   - Hard to test without database

2. **Hardcoded Table Prefixes**
   - Uses hardcoded `'0_'` prefix in some places
   - Inconsistent use of `TB_PREF` constant
   - Breaks multi-company installations

3. **SQL Injection Risks**
   - Inline SQL construction with string concatenation
   - Inconsistent use of `db_escape()`
   - Difficult to audit for security

4. **Code Duplication**
   - Similar SQL patterns repeated across methods
   - Same filter logic duplicated
   - Maintenance nightmare

5. **Testing Challenges**
   - Cannot unit test without database
   - Integration tests slow and fragile
   - Hard to mock dependencies

### Legacy Code Example

```php
// OLD WAY - Violates SRP, hardcoded prefix, security risks
function get_transactions($status = null, $transAfterDate = null, ...) {
    $trzs = array();
    $sql = " SELECT t.*, s.account our_account, s.currency from " . TB_PREF . "bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id";
    
    // Inline SQL building - mixed concerns
    if ($transAfterDate) {
        $sql .= " WHERE t.valueTimestamp >= '" . db_escape($transAfterDate) . "'";
    }
    
    // Direct execution - cannot test without DB
    $res = db_query($sql, 'unable to get transactions data');
    
    while($myrow = db_fetch($result)) {
        $trz_code = $myrow['transactionCode'];
        if(!isset($trzs[$trz_code])) {
            $trzs[$trz_code] = array();
        }
        $trzs[$trz_code][] = $myrow;
    }
    return $trzs;
}
```

## New Architecture (SOLID)

### Components

#### 1. TransactionQueryBuilder
- **Responsibility:** Build SQL queries only (SRP)
- **Location:** `src/Ksfraser/FaBankImport/database/TransactionQueryBuilder.php`
- **Tests:** `tests/unit/TransactionQueryBuilderTest.php` (20 tests, 121 assertions - ALL PASSING ✅)
- **Features:**
  - Parameterized queries (prevents SQL injection)
  - Automatic TB_PREF handling
  - Returns `['sql' => string, 'params' => array]`
  - No database dependency (pure logic)

#### 2. TransactionRepository
- **Responsibility:** Execute queries and return data (Repository Pattern)
- **Location:** `src/Ksfraser/FaBankImport/repositories/TransactionRepository.php`
- **Tests:** `tests/integration/TransactionRepositoryTest.php` (17 tests)
- **Features:**
  - Dependency Injection of QueryBuilder
  - Converts parameters to FA format
  - Returns arrays or row counts
  - Handles db_query() execution

#### 3. Refactored Model
- **Responsibility:** Business logic only
- **Example:** `class.bi_transactions_refactored_example.php`
- **Features:**
  - Constructor injection of dependencies
  - Delegates SQL to QueryBuilder
  - Delegates execution to Repository
  - Focuses on business logic (grouping, validation)

### Benefits

| Principle | Old Architecture | New Architecture |
|-----------|-----------------|------------------|
| **SRP** | ❌ Mixed concerns | ✅ Clear separation |
| **OCP** | ❌ Modify to extend | ✅ Extend without modification |
| **DIP** | ❌ Depends on concrete DB | ✅ Depends on abstractions |
| **DRY** | ❌ Duplicate SQL | ✅ Centralized queries |
| **Testability** | ❌ Requires database | ✅ Unit tests without DB |
| **Security** | ⚠️ Inline SQL | ✅ Parameterized queries |
| **TB_PREF** | ⚠️ Inconsistent | ✅ Automatic handling |

### New Code Example

```php
// NEW WAY - Follows SOLID, secure, testable
class bi_transactions_model_refactored {
    private $queryBuilder;
    private $repository;
    
    // Dependency Injection
    public function __construct(
        ?TransactionQueryBuilder $queryBuilder = null,
        ?TransactionRepository $repository = null
    ) {
        $this->queryBuilder = $queryBuilder ?? new TransactionQueryBuilder();
        $this->repository = $repository ?? new TransactionRepository($this->queryBuilder);
    }
    
    // Business logic only - delegates to Repository
    public function get_transactions($status = null, $transAfterDate = null, ...) {
        // Build filters
        $filters = [];
        if ($status !== null) $filters['status'] = $status;
        if ($transAfterDate !== null) $filters['dateFrom'] = $transAfterDate;
        
        // Repository handles SQL generation and execution
        $rows = $this->repository->findByFilters($filters);
        
        // Model handles business logic (grouping)
        $trzs = [];
        foreach ($rows as $row) {
            $trz_code = $row['transactionCode'];
            $trzs[$trz_code][] = $row;
        }
        return $trzs;
    }
}
```

## Migration Strategy

### Phase 1: Preparation (✅ COMPLETE)

**Completed:**
- ✅ Created TransactionQueryBuilder with TB_PREF support
- ✅ Created TransactionRepository with dependency injection
- ✅ Written 20 unit tests for QueryBuilder (ALL PASSING)
- ✅ Written 17 integration tests for Repository
- ✅ Created refactored example demonstrating patterns
- ✅ Updated documentation with UML diagrams

**Status:** Ready for implementation in production code

### Phase 2: Gradual Migration (RECOMMENDED APPROACH)

**Timeline:** 2-4 weeks  
**Risk Level:** LOW  
**Approach:** Incremental refactoring with backward compatibility

#### Step 1: Add Dependencies to Existing Model (Week 1)

```php
// In class.bi_transactions.php
class bi_transactions_model extends generic_fa_interface_model {
    /** @var TransactionQueryBuilder|null */
    private $queryBuilder;
    
    /** @var TransactionRepository|null */
    private $repository;
    
    public function __construct() {
        parent::__construct();
        
        // Optional dependencies - only used if available
        if (class_exists('\\Ksfraser\\FaBankImport\\Database\\TransactionQueryBuilder')) {
            $this->queryBuilder = new \Ksfraser\FaBankImport\Database\TransactionQueryBuilder();
            $this->repository = new \Ksfraser\FaBankImport\Database\TransactionRepository($this->queryBuilder);
        }
    }
}
```

#### Step 2: Refactor One Method at a Time (Week 2-3)

**Priority Order:**
1. `get_transactions()` - Most used, high impact
2. `get_transaction()` - Simple, good first refactor
3. `get_normal_pairing()` - Already has TB_PREF issues
4. `update_transactions()` - Security improvement
5. `reset_transactions()` - Void operations
6. `db_prevoid()` - FA integration

**Pattern for Each Method:**
```php
public function get_transaction($tid = null, $bSetInternal = false) {
    // NEW: Use repository if available
    if ($this->repository !== null) {
        if ($tid === null) {
            if (isset($this->id)) {
                $tid = $this->id;
            } else {
                throw new Exception("No ID set to search for");
            }
        }
        
        $res = $this->repository->findById($tid);
        
        if ($bSetInternal && $res !== null) {
            $this->arr2obj($res);
        }
        
        return $res;
    }
    
    // OLD: Fallback to legacy code (for safety)
    $sql = "SELECT t.*, s.account our_account FROM ".TB_PREF."bi_transactions t
            LEFT JOIN ".TB_PREF."bi_statements as s ON t.smt_id = s.id
            WHERE t.id=".db_escape($tid);
    $result = db_query($sql, "could not get transaction with id $tid");
    $res = db_fetch($result);
    
    if ($bSetInternal) {
        $this->arr2obj($res);
    }
    
    return $res;
}
```

#### Step 3: Testing & Validation (Week 3-4)

**For Each Refactored Method:**
1. Run existing integration tests
2. Compare results: old vs new implementation
3. Monitor production logs for errors
4. Gradually increase confidence

**Validation Script:**
```php
// Add to test suite
public function testRefactoredMethodMatchesLegacy() {
    $model = new bi_transactions_model();
    
    // Get results from refactored method
    $newResult = $model->get_transaction(123);
    
    // Get results from legacy method (if still available)
    $legacyResult = $model->get_transaction_legacy(123);
    
    // Assert they match
    $this->assertEquals($legacyResult, $newResult);
}
```

#### Step 4: Remove Legacy Code (Week 4)

Once all methods refactored and tested:
1. Remove fallback logic
2. Make QueryBuilder/Repository required
3. Update documentation
4. Add @since tags with migration date

### Phase 3: Alternative Approaches

#### Option A: Feature Flag Migration

```php
// Config option to enable new architecture
define('USE_NEW_TRANSACTION_ARCHITECTURE', true);

public function get_transactions(...) {
    if (USE_NEW_TRANSACTION_ARCHITECTURE && $this->repository !== null) {
        // New implementation
        return $this->repository->findByFilters($filters);
    }
    
    // Old implementation
    return $this->get_transactions_legacy(...);
}
```

**Benefits:**
- Can toggle on/off quickly
- Easy rollback if issues found
- Test in production safely

**Timeline:** 1-2 weeks

#### Option B: Parallel Classes

Create `bi_transactions_model_v2` with new architecture:

```php
// New file: class.bi_transactions_v2.php
class bi_transactions_model_v2 extends generic_fa_interface_model {
    private $queryBuilder;
    private $repository;
    
    // Complete rewrite with new architecture
}

// Update calling code gradually
$model = new bi_transactions_model_v2(); // Instead of bi_transactions_model
```

**Benefits:**
- Complete clean slate
- No mixing old/new code
- Can test thoroughly before switch

**Timeline:** 2-3 weeks

**Drawbacks:**
- More code duplication during transition
- Need to update all calling code

## Backward Compatibility

### Maintaining Existing API

All public methods will maintain same signatures:

```php
// PUBLIC API - NO BREAKING CHANGES
public function get_transactions($status = null, $transAfterDate = null, ...) { }
public function get_transaction($tid = null, $bSetInternal = false) { }
public function get_normal_pairing($account = null) { }
```

**Internal implementation changes, but external contract stays the same.**

### Deprecation Timeline

| Date | Milestone |
|------|-----------|
| **Week 1** | Add new architecture components (QueryBuilder, Repository) |
| **Week 2** | Refactor 2-3 methods with fallback |
| **Week 3** | Refactor remaining methods |
| **Week 4** | Remove fallback code if tests pass |
| **Week 5-8** | Monitor production, fix edge cases |
| **Week 9** | Mark legacy methods as @deprecated |
| **Week 12** | Remove legacy code completely |

## Testing Strategy

### Unit Tests (QueryBuilder)
- ✅ **20 tests, 121 assertions - ALL PASSING**
- Location: `tests/unit/TransactionQueryBuilderTest.php`
- Coverage: All 9 public methods
- No database required

### Integration Tests (Repository)
- 17 tests written
- Location: `tests/integration/TransactionRepositoryTest.php`
- Requires: FA database connection
- Tests: CRUD operations, TB_PREF handling, filters

### Regression Tests (Model)
- Compare refactored vs legacy results
- Run on production data
- Ensure no behavior changes

### Performance Tests
- Benchmark query execution time
- Compare old vs new architecture
- Ensure no performance regression

## Rollback Plan

### If Issues Found

**Step 1: Immediate Rollback**
```php
// Set flag to false
define('USE_NEW_TRANSACTION_ARCHITECTURE', false);

// Or comment out new code
/*
if ($this->repository !== null) {
    return $this->repository->findByFilters($filters);
}
*/
```

**Step 2: Investigate**
- Check error logs
- Review test results
- Identify failing scenarios

**Step 3: Fix and Re-deploy**
- Fix QueryBuilder/Repository
- Add regression test
- Re-enable gradually

### Rollback Safety

- Keep legacy code until Week 4
- All changes can be reverted via git
- Feature flags allow quick disable
- Incremental approach limits blast radius

## Success Metrics

| Metric | Target | How to Measure |
|--------|--------|----------------|
| **Test Coverage** | 100% of new code | PHPUnit coverage report |
| **Performance** | No regression | Benchmark queries |
| **Bugs** | 0 new bugs | Production error logs |
| **Code Quality** | Lint errors = 0 | PHP linter |
| **Team Velocity** | No slowdown | Sprint metrics |
| **TB_PREF Support** | Works all companies | Multi-company tests |

## Resources

### Documentation
- `docs/TransactionQueryBuilder_UML.md` - Architecture diagrams
- `class.bi_transactions_refactored_example.php` - Code examples
- This file - Migration guide

### Code Locations
- QueryBuilder: `src/Ksfraser/FaBankImport/database/TransactionQueryBuilder.php`
- Repository: `src/Ksfraser/FaBankImport/repositories/TransactionRepository.php`
- Tests: `tests/unit/TransactionQueryBuilderTest.php`

### Support
- **Technical Questions:** See code comments and PHPDoc
- **Issues:** GitHub issue tracker
- **Design Decisions:** Review SOLID principles in code comments

## Conclusion

This migration moves from legacy monolithic architecture to SOLID-compliant, testable, maintainable code. The gradual approach ensures:

✅ **Low Risk** - Incremental changes with fallback  
✅ **High Quality** - 20 unit tests passing  
✅ **Maintainable** - Clear separation of concerns  
✅ **Secure** - Parameterized queries  
✅ **Flexible** - TB_PREF support for all companies  

**Next Steps:** Begin Phase 2, Step 1 - Add dependencies to existing model.
