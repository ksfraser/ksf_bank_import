# PSR-4 Migration Plan - Legacy Classes

**Status**: Planning Phase  
**Current Metrics**: 1495/1495 approved tests passing ✓  
**Goal**: Migrate legacy non-PSR-4 classes to PSR-4 namespace structure  

---

## Phase Overview

### Current State
- 20+ legacy classes using `class.ClassName.php` naming convention
- Root-level files not compatible with composer PSR-4 autoloader
- Circular require_once dependencies making testing difficult
- Tight coupling to FrontAccounting bootstrap requirements

### Migration Path
1. **Phase 1**: Identify all legacy classes and dependencies
2. **Phase 2**: Create PSR-4 equivalents in `src/Ksfraser/FaBankImport/`
3. **Phase 3**: Update class references one at a time with tests
4. **Phase 4**: Remove legacy files after verification
5. **Phase 5**: Update composer.json classmap as needed

---

## Phase 1: Legacy Class Inventory

### Root-Level Classes (Must Migrate)
```
class.bi_lineitem.php
  - Extends: generic_fa_interface_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiLineItem
  - Dependencies: ksf_modules_common/class.generic_fa_interface.php
  - Status: PRIORITY (used in many views)
  - Tests: None currently active (deprecated)

class.bi_transaction.php
  - Extends: bi_transactions_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiTransaction
  - Dependencies: class.bi_transactions.php
  - Status: PRIORITY (deprecated tests - core model)
  - Tests: DEPRECATED (BiTransactionsModelTest.php)

class.bi_transactions.php
  - Extends: generic_fa_interface_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiTransactions
  - Dependencies: ksf_modules_common classes
  - Status: PRIORITY (core staging table)
  - Tests: DEPRECATED (11 tests)

class.bi_statements.php
  - Extends: generic_fa_interface_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiStatements
  - Dependencies: ksf_modules_common
  - Status: MEDIUM

class.bi_partners_data.php
  - Extends: generic_fa_interface_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiPartnersData
  - Dependencies: ksf_modules_common
  - Status: MEDIUM

class.bi_counterparty_model.php
  - Extends: generic_fa_interface_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiCounterparty
  - Dependencies: ksf_modules_common
  - Status: MEDIUM

class.bi_transactionTitle_model.php
  - Extends: generic_fa_interface_model
  - Namespace Target: Ksfraser\FaBankImport\Models\BiTransactionTitle
  - Dependencies: ksf_modules_common
  - Status: MEDIUM

class.ViewBiLineItems.php
  - No extends, general view class
  - Namespace Target: Ksfraser\FaBankImport\Views\BiLineItemsView
  - Dependencies: Multiple Views/* and Models
  - Status: HIGH (used in display)
  - Tests: Unit tests reference this

class.transactions_table.php
  - No extends, view composition class
  - Namespace Target: Ksfraser\FaBankImport\Views\TransactionsTable
  - Dependencies: Multiple Views/* and Models
  - Status: HIGH (core transaction display)
  - Tests: Tests reference this

class.bank_import_controller.php
  - No extends, main controller
  - Namespace Target: Ksfraser\FaBankImport\Controller\BankImportController
  - Status: CRITICAL
  - Already has PSR-4 copy in src/Ksfraser/FaBankImport/controllers/

class.bi_transactionTitle_model.php
  - Namespace Target: Ksfraser\FaBankImport\Models\BiTransactionTitle
  - Status: MEDIUM
```

### Src-Level Classes (Hybrid State)
```
src/Ksfraser/FaBankImport/class.*.php (8 files)
  - Already in src/ but use legacy naming convention
  - Should be renamed to PSR-4 format: Controllers/, Models/, Services/, etc.
  - Less urgent (already partially organized)
```

---

## Phase 2: Dependency Analysis

### FA Bootstrap Coupling
**Problem**: Classes require FrontAccounting constants/classes unavailable in PHPUnit

```php
// Typical requirements:
require_once( __DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php' );
require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' );
require_once( __DIR__ . '/Views/HTML_ROW_LABELDecorator.php' );

// Uses FA constants/classes:
defined('TB_PREF')     // Table prefix
defined('ST_JOURNAL')  // Statement types
// etc.
```

**Solution Strategy**:
1. Inject FA dependencies (TB_PREF, statement types) via constructor
2. Create interface contracts for FA integration points
3. Use dependency injection instead of require_once

### Circular Dependencies
**Example**: class.bi_transaction.php → class.bi_transactions.php → generic_fa_interface_model

**Solution**: 
- Create PSR-4 versions with explicit namespace imports
- Use composer autoloader instead of require_once chains

---

## Phase 3: Migration Strategy (Phased Approach)

### PHASE 3A: Core Models (Priority)
**Duration**: ~2 weeks  
**Classes**: bi_transactions, bi_transaction, bi_lineitem

**Steps**:
1. Create PSR-4 structure:
   ```
   src/Ksfraser/FaBankImport/Models/
   ├── BiTransactions.php
   ├── BiTransaction.php
   ├── BiLineItem.php
   ```

2. Update class structure:
   ```php
   <?php
   namespace Ksfraser\FaBankImport\Models;
   
   use Ksfraser\FaBankImport\Contracts\FaIntegrationInterface;
   
   class BiTransactions implements FaIntegrationInterface {
       private string $tablePrefix;
       
       public function __construct(string $tablePrefix = '0_') {
           $this->tablePrefix = $tablePrefix;
       }
       
       // All methods from legacy class, updated for DI
   }
   ```

3. Update composer.json to autoload new namespace:
   ```json
   {
       "psr-4": {
           "Ksfraser\\FaBankImport\\Models\\": "src/Ksfraser/FaBankImport/Models/"
       }
   }
   ```

4. Create adapter/facade for backward compatibility:
   ```php
   // In root for temporary backward compat:
   if (!class_exists('bi_transactions')) {
       class bi_transactions extends \Ksfraser\FaBankImport\Models\BiTransactions {}
   }
   ```

5. Update tests:
   - Activate DEPRECATED bi_transactions tests with new namespaced class
   - Run full test suite to verify migrations work
   - Commit changes with clear messages

6. Deprecate old legacy files after verification:
   ```php
   // In legacy file:
   trigger_error('bi_transactions.php is deprecated, use \\Ksfraser\\FaBankImport\\Models\\BiTransactions', E_USER_DEPRECATED);
   ```

### PHASE 3B: View Classes (High Priority)
**Duration**: ~2 weeks  
**Classes**: ViewBiLineItems, transactions_table, partner type views

**Steps**: Same as Phase 3A but with view-specific considerations

### PHASE 3C: Helper Models (Medium Priority)
**Duration**: ~1 week  
**Classes**: bi_statements, bi_partners_data, bi_counterparty_model, bi_transactionTitle_model

**Steps**: Same pattern as Phase 3A

### PHASE 3D: Src-Level Classes (Housekeeping)
**Duration**: ~1 week  
**Classes**: Rename class.* files to PSR-4 structure within src/

---

## Phase 4: Testing Strategy

### Unit Test Updates
1. **Restore deprecated tests** with new namespaced classes
2. **Mock FA dependencies** (TB_PREF, statement types, etc.)
3. **Add integration tests** for FA-dependent behavior
4. **Verify no regression** against 1495 baseline

### Test Sequence
```bash
# 1. Run approved suite (should stay at 1495/1495)
php run-approved-tests.php

# 2. Run migrated model tests
php vendor/bin/phpunit tests/unit/Models/

# 3. Run full unit suite
php vendor/bin/phpunit tests/unit

# 4. Run integration tests (optional, requires FA)
php vendor/bin/phpunit tests/integration
```

---

## Phase 5: Implementation Checklist

### Before Starting Migration
- [ ] All deprecated tests marked with @deprecated
- [ ] 1495/1495 approved tests passing
- [ ] Git branch created (feature/psr4-migration)
- [ ] Migration plan reviewed with team

### Per-Class Checklist
1. **Create PSR-4 file**
   - [ ] Create new file in src/Ksfraser/FaBankImport/Models/ or appropriate directory
   - [ ] Add namespace declaration
   - [ ] Convert require_once to use statements

2. **Inject Dependencies**
   - [ ] Replace FA constants with constructor injection
   - [ ] Replace require_once with use statements
   - [ ] Update method signatures to accept injected dependencies

3. **Create Interface/Contract**
   - [ ] Define what FA methods/properties are needed
   - [ ] Create interface contract for abstraction

4. **Create Adapter (Backward Compat)**
   - [ ] Create adapter class in legacy location or compatibility layer
   - [ ] Point adapter to new PSR-4 class

5. **Update Tests**
   - [ ] Activate relevant deprecated tests
   - [ ] Create mocks for FA dependencies
   - [ ] Run tests with new class

6. **Verify & Commit**
   - [ ] Run full approved suite (must stay 1495/1495)
   - [ ] Commit with conventional message: `refactor(models): migrate ClassName to PSR-4 namespace`
   - [ ] Update this plan with completion status

7. **Clean Up (Optional, Phase 4)**
   - [ ] Remove legacy file or mark as deprecated
   - [ ] Update remaining references to use new namespace

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| FA dependency breaks | HIGH | CRITICAL | Use dependency injection from start; mock in tests |
| Circular dependencies | HIGH | CRITICAL | Map dependencies before migration; break circular refs |
| Test failures in approved suite | MEDIUM | CRITICAL | Test each migration in isolation; 1495 baseline check after each |
| Performance regression | LOW | MEDIUM | Profile old vs new; optimize hot paths |
| Integration test failures | MEDIUM | MEDIUM | Create FA stub environment for integration tests |

---

## Expected Outcomes

### After Phase 1 (Classes Identified)
- ✓ Clear understanding of 20+ legacy classes
- ✓ Dependency map created
- ✓ Prioritization complete

### After Phase 2 (PSR-4 Models)
- ✓ Core bi_transactions model is PSR-4 compliant
- ✓ Tests restored and passing (1500+ tests)
- ✓ Backward compatibility layer in place

### After Phase 3 (All Views Migrated)
- ✓ All core classes have PSR-4 equivalents
- ✓ Full test suite passing (1550+ tests)
- ✓ No root-level require_once chains

### After Phase 4 (Integration Tests)
- ✓ Integration tests working with PSR-4 models
- ✓ FA bootstrap requirements clearly documented
- ✓ Path forward for production deployment

---

## Timeline Estimate

| Phase | Tasks | Duration | Priority |
|-------|-------|----------|----------|
| 1 | Inventory & Analysis | 1 day | CRITICAL |
| 2 | Core Models (bi_transactions family) | 2 weeks | CRITICAL |
| 3 | View Classes | 2 weeks | HIGH |
| 4 | Helper Models | 1 week | MEDIUM |
| 5 | Integration & Cleanup | 1 week | MEDIUM |
| **TOTAL** | | **~5 weeks** | |

**Fast Track** (Focus Core Only): 2-3 weeks for bi_transactions, bi_transaction, bi_lineitem

---

## Next Steps

1. **Review this plan** with team/stakeholders
2. **Identify quick wins** among legacy classes
3. **Start Phase 2** with bi_transactions migration
4. **Keep 1495 baseline tests passing** throughout
5. **Document decisions** in ADR (Architectural Decision Record)

---

## References

- **Current Test Baseline**: 1495/1495 approved tests passing
- **Approved Suite Components**: SupplierMatching, SupplierTransaction, StatementReconcile
- **Deprecated Tests (Awaiting PSR-4 Migration)**: 28 tests for legacy classes
- **User Memory**: See PHASE_0_FOUNDATION_PATTERN.md for PSR-4 entity examples
