---
goal: "Systematically implement BankAccountMapping infrastructure across 5 phases using Test-Driven Development"
version: 1.0
date_created: 2026-03-30
status: 'In progress'
tags: ['feature', 'architecture', 'migration', 'tdd', 'legacy-model-integration']
---

# BankAccountMapping Implementation Plan: 5 Phases (TDD)

![Status: In progress](https://img.shields.io/badge/status-In%20progress-yellow)

**Objective**: Systematically implement centralized BankAccountMapping infrastructure, migrate legacy models, integrate import pipeline, consolidate services, and validate with comprehensive tests.

**Methodology**: Test-Driven Development (TDD) - Write tests first, implement to pass tests, refactor for quality.

---

## 1. Requirements & Constraints

### Functional Requirements
- **REQ-001**: Create centralized BankAccountMapping repository as single source of truth for OFX identifiers
- **REQ-002**: Add cross-reference methods to legacy models (bi_statements_model, bi_transactions_model)
- **REQ-003**: Integrate BankAccountMapping extraction into import pipeline (import_statements.php, process_statements.php)
- **REQ-004**: Migrate services to use repository instead of scattered static lookups
- **REQ-005**: Maintain 100% backward compatibility with existing code

### Architectural Constraints
- **CON-001**: Three-class pattern required: Entity + Repository + Factory
- **CON-002**: No breaking changes to existing API surfaces
- **CON-003**: Database schema already clean (bi_bank_accounts table)
- **CON-004**: Must preserve existing method signatures in legacy models

### Quality Constraints
- **QUA-001**: >90% code coverage for new classes
- **QUA-002**: All repository CRUD operations must be unit tested
- **QUA-003**: Integration tests required for cross-reference methods
- **QUA-004**: Regression tests for import pipeline modifications
- **QUA-005**: Zero fatal errors in test suite (exit code 0)

### Guideline Requirements
- **GUD-001**: Follow ISTQB test design techniques (equivalence partitioning, boundary analysis)
- **GUD-002**: Each phase must have independent test suite
- **GUD-003**: Write tests before implementation (TDD discipline)
- **GUD-004**: Use PHP unit testing framework (PHPUnit already configured)

---

## 2. Phase Architecture Overview

```
Phase 2 (Foundation)
  ↓ Tests written for cross-reference methods
  ↓ Legacy model methods implemented
  ↓ All phase 2 tests pass
  ↓ ✅ GATE: Backward compatibility verified
  │
  ├→ Phase 3 (Import Pipeline)
  │    ↓ Tests written for extraction/storage
  │    ↓ import_statements.php modified with factory calls
  │    ↓ process_statements.php modified with repository calls
  │    ↓ All phase 3 tests pass
  │    ↓ ✅ GATE: Import flow verified with real data
  │
  ├→ Phase 4 (Service Migration) [Can run after Phase 2]
  │    ↓ Tests written for service refactoring
  │    ↓ TransferMatchService updated
  │    ↓ BankImportModuleSchemaService updated
  │    ↓ ContactService updated
  │    ↓ All phase 4 tests pass
  │    ↓ ✅ GATE: Services use repository exclusively
  │
  └→ Phase 5 (Comprehensive Testing) [Runs at end]
       ↓ End-to-end tests verifying data flow integrity
       ↓ Performance benchmarks for repository operations
       ↓ Regression tests for all modified files
       ↓ ✅ GATE: Complete test suite passes, coverage >90%
```

---

## 3. Implementation Phases

### PHASE 2: Legacy Model Cross-References (Foundation)
**Status**: Not Started  
**Duration**: ~2 hours  
**Blocker**: None (all infrastructure exists)

#### Phase 2 Goals
- GOAL-P2-001: Add cross-reference methods to `bi_statements_model`
- GOAL-P2-002: Add cross-reference methods to `bi_transactions_model`
- GOAL-P2-003: Achieve 100% backward compatibility
- GOAL-P2-004: All tests pass with exit code 0

#### Phase 2 Test Suite (Write First)
**File**: `tests/Unit/BankAccountMapping/LegacyModelCrossReferencesTest.php`

Test cases to implement:
- `test_bi_statements_getBankAccountMapping_returns_valid_mapping()`
- `test_bi_statements_getFABankAccountId_returns_mapped_account()`
- `test_bi_statements_extractBankAccountMapping_normalization()`
- `test_bi_statements_storeBankAccountMapping_creates_entry()`
- `test_bi_transactions_getMappingFromCounterparty_returns_mapping()`
- `test_bi_transactions_getFABankAccountFromMapping_returns_id()`
- `test_bi_transactions_extractMappingFromStatement_handles_null()`
- `test_bi_transactions_updateMappingAfterImport_idempotent()`
- `test_legacy_methods_dont_break_existing_functionality()`
- `test_backward_compatibility_existing_calls_still_work()`

#### Phase 2 Implementation Tasks

**TASK-P2-001**: Add methods to `bi_statements_model`
```php
// Methods to add:
public function getBankAccountMapping(): ?BankAccountMapping
public function getFABankAccountId(): ?int
public function extractBankAccountMapping(): ?BankAccountMapping
public function storeBankAccountMapping(BankAccountMapping $mapping, int $faAccountId): bool
public function relinkBankAccountMapping(int $newFAAccountId): bool
public static function findByBankAccountMappingId(int $mappingId): ?array
```

**TASK-P2-002**: Add methods to `bi_transactions_model`
```php
// Methods to add:
public function getBankAccountMappingFromCounterparty(): ?BankAccountMapping
public function getFABankAccountFromMapping(): ?int
public function extractMappingFromStatement(array $statement): ?BankAccountMapping
public function updateMappingAfterImport(BankAccountMapping $mapping): bool
public function getMatchingMappingsForReconciliation(): array
public static function findByBankAccountMappingId(int $mappingId): array
public static function countByBankAccountMappingId(int $mappingId): int
public function validateMappingConsistency(): bool
public function syncMappingToTransaction(): void
public function getMappingAuditTrail(): array
```

**TASK-P2-003**: Verify all tests pass
- Command: `php .\vendor\bin\phpunit tests/Unit/BankAccountMapping/LegacyModelCrossReferencesTest.php`
- Expected result: Exit code 0, all assertions pass

#### Phase 2 Quality Gates
- ✅ All 10 test cases pass
- ✅ No fatal errors or exceptions
- ✅ Code coverage >90% for both model classes
- ✅ Backward compatibility verified (existing code still works)
- ✅ No performance degradation in legacy methods

---

### PHASE 3: Import Pipeline Integration
**Status**: Not Started  
**Duration**: ~2.5 hours  
**Dependencies**: Phase 2 complete  
**Blocker**: None (after Phase 2)

#### Phase 3 Goals
- GOAL-P3-001: Extract BankAccountMappings during import process
- GOAL-P3-002: Store mappings in repository automatically
- GOAL-P3-003: Maintain audit trail of mapping creation
- GOAL-P3-004: All import tests pass with no data loss

#### Phase 3 Test Suite (Write First)
**File**: `tests/Integration/BankAccountMapping/ImportPipelineTest.php`

Test cases to implement:
- `test_import_statements_extracts_mapping_from_ofx_data()`
- `test_import_statements_stores_mapping_in_repository()`
- `test_process_statements_cascades_mapping_to_transactions()`
- `test_import_handles_duplicate_mappings_gracefully()`
- `test_import_normalizes_ofx_identifiers_correctly()`
- `test_import_handles_null_identifiers_safely()`
- `test_import_maintains_audit_trail()`
- `test_import_performance_acceptable_for_large_batches()`
- `test_import_rollback_on_mapping_storage_failure()`
- `test_import_idempotency_repeated_imports_safe()`

#### Phase 3 Implementation Tasks

**TASK-P3-001**: Modify `import_statements.php` (lines 302-320)
```php
// Add after statement data is extracted:
$mapping = BankAccountMappingFactory::createFromStatement($statement);
$mappingId = BankAccountMappingRepository::upsert($mapping, $faAccountId);
$statement['bank_account_mapping_id'] = $mappingId;
// Log audit trail
AuditLog::record('import.mapping.created', [
    'mapping_id' => $mappingId,
    'statement_id' => $statement['id'],
    'ofx_identifiers' => [
        'bankid' => $mapping->bankid,
        'acctid' => $mapping->acctid,
        'intu_bid' => $mapping->intu_bid
    ]
]);
```

**TASK-P3-002**: Modify `process_statements.php` (transaction cascade)
```php
// Add when processing transactions:
foreach ($transactions as $transaction) {
    // Cascade mapping from statement
    $transaction->setMappingFromStatement($statement);
    $transaction->storeBankAccountMapping($mapping, $faAccountId);
    // Continue with normal processing
}
```

**TASK-P3-003**: Add mapping storage operations
- Upsert logic in repository (idempotent)
- Conflict resolution (if mapping already exists)
- Audit trail creation
- Rollback on failure

**TASK-P3-004**: Verify all integration tests pass
- Command: `php .\vendor\bin\phpunit tests/Integration/BankAccountMapping/ImportPipelineTest.php`
- Expected result: Exit code 0, all assertions pass

#### Phase 3 Quality Gates
- ✅ All 10 integration tests pass
- ✅ Import performance acceptable (<2s per 1000 statements)
- ✅ Audit trail complete for all mappings
- ✅ No data loss in import process
- ✅ Idempotency verified (re-import safe)

---

### PHASE 4: Service Migration
**Status**: Not Started  
**Duration**: ~1.5 hours  
**Dependencies**: Phase 2 complete  
**Blocker**: None (can run in parallel with Phase 3)

#### Phase 4 Goals
- GOAL-P4-001: Migrate TransferMatchService to use repository
- GOAL-P4-002: Migrate BankImportModuleSchemaService to use repository
- GOAL-P4-003: Migrate ContactService to use repository
- GOAL-P4-004: Eliminate scattered static lookups

#### Phase 4 Test Suite (Write First)
**File**: `tests/Unit/BankAccountMapping/ServiceMigrationTest.php`

Test cases to implement:
- `test_transfer_match_service_uses_repository()`
- `test_transfer_match_service_finds_accounts_correctly()`
- `test_bank_import_schema_service_uses_repository()`
- `test_schema_service_handles_missing_mappings_gracefully()`
- `test_contact_service_links_with_mapping_repository()`
- `test_contact_service_updates_mapping_on_account_change()`
- `test_all_services_drop_static_lookup_calls()`
- `test_services_backward_compatible_with_existing_code()`
- `test_service_migration_maintains_performance()`
- `test_service_error_handling_consistent()`

#### Phase 4 Implementation Tasks

**TASK-P4-001**: Refactor `TransferMatchService.php`
Find all occurrences of:
```php
// OLD: Static lookups
$accountId = bi_bank_accounts::get_fa_account_from_ofx($bankid, $acctid);
```
Replace with:
```php
// NEW: Repository injection
$mapping = BankAccountMappingRepository::findByOFXIdentifiers($bankid, $acctid);
$accountId = $mapping ? $mapping->bank_account_id : null;
```

**TASK-P4-002**: Refactor `BankImportModuleSchemaService.php`
Update all database queries for OFX identifiers to use:
```php
BankAccountMappingRepository::findByOFXIdentifiers()
BankAccountMappingRepository::findByFABankAccountId()
```

**TASK-P4-003**: Refactor `ContactService.php`
Update all account linking operations to:
```php
// Store mapping when linking account
BankAccountMappingRepository::upsert($mapping, $faAccountId);
```

**TASK-P4-004**: Verify all service tests pass
- Command: `php .\vendor\bin\phpunit tests/Unit/BankAccountMapping/ServiceMigrationTest.php`
- Expected result: Exit code 0, all assertions pass

#### Phase 4 Quality Gates
- ✅ All 10 service migration tests pass
- ✅ No static lookup calls remain in services
- ✅ Repository exclusively used for OFX identifier lookups
- ✅ Backward compatibility maintained
- ✅ Error handling consistent across services

---

### PHASE 5: Comprehensive Testing & Validation
**Status**: Not Started  
**Duration**: ~2 hours  
**Dependencies**: Phases 2, 3, 4 complete  
**Blocker**: All previous phases complete

#### Phase 5 Goals
- GOAL-P5-001: End-to-end data flow verification
- GOAL-P5-002: Performance benchmarking for repository
- GOAL-P5-003: Regression testing for all modified files
- GOAL-P5-004: Complete test suite with >90% coverage

#### Phase 5 Test Suite (Write First)
**File**: `tests/E2E/BankAccountMapping/DataFlowTest.php`

Test cases to implement:
- `test_complete_data_flow_import_to_match()`
- `test_mapping_consistency_across_cascade()`
- `test_performance_repository_operations_baseline()`
- `test_performance_legacy_methods_not_degraded()`
- `test_regression_import_statements_unchanged_functionality()`
- `test_regression_process_statements_unchanged_functionality()`
- `test_regression_transfer_matching_still_works()`
- `test_regression_contact_service_still_works()`
- `test_edge_case_null_identifiers_handled()`
- `test_edge_case_duplicate_mappings_resolved()`

#### Phase 5 Implementation Tasks

**TASK-P5-001**: Create E2E data flow test
```php
// Import OFX statement → Extract mapping → Store in repository 
// → Cascade to transaction → Match transfer → Verify integrity
```

**TASK-P5-002**: Performance benchmarking
```php
// Baseline metrics for:
// - Repository CRUD operations (target: <50ms)
// - Factory extraction (target: <100ms)
// - Legacy model methods (target: <75ms)
// - Import cascade (target: <500ms per transaction)
```

**TASK-P5-003**: Run full regression test suite
- All existing unit tests
- All existing integration tests
- All new tests from phases 2-5

**TASK-P5-004**: Code coverage analysis
- Target: >90% for new classes
- Target: >80% for modified legacy classes
- Generate coverage report: `php .\vendor\bin\phpunit --coverage-html coverage/`

#### Phase 5 Quality Gates
- ✅ All 10 E2E tests pass
- ✅ Code coverage >90% for new infrastructure
- ✅ Performance benchmarks meet targets
- ✅ All regression tests pass
- ✅ Full test suite exit code 0
- ✅ No errors or warnings in phpunit output

---

## 4. Execution Sequence

```
SEQUENTIAL STEPS:
================

1. Write Phase 2 tests
2. Implement Phase 2
3. Verify Phase 2 tests pass → ✅ GATE 1

4. Write Phase 3 tests (can start before Phase 2 complete)
5. Implement Phase 3
6. Verify Phase 3 tests pass → ✅ GATE 2

7. Write Phase 4 tests (can run in parallel with Phase 3)
8. Implement Phase 4
9. Verify Phase 4 tests pass → ✅ GATE 3

10. Write Phase 5 tests
11. Implement Phase 5
12. Verify Phase 5 tests pass → ✅ GATE 4 (FINAL)

13. Full test suite: php .\vendor\bin\phpunit
14. Coverage report: php .\vendor\bin\phpunit --coverage-html coverage/
15. Deployment verification complete
```

---

## 5. TDD Workflow for Each Phase

### For Each Task:
1. **Write Tests First**
   - Create test file with all test cases
   - Run tests (they will fail - RED state)
   - Document expected behavior

2. **Implement to Pass Tests**
   - Write implementation code
   - Run tests until they pass (GREEN state)
   - All assertions must pass

3. **Refactor for Quality**
   - Improve code clarity
   - Extract helper methods
   - Run tests again (must still be GREEN)
   - No behavior changes, only improvements

4. **Verify Quality Gates**
   - Code coverage check
   - Performance check
   - Backward compatibility check
   - All tests pass

---

## 6. Files to Create/Modify

### New Test Files
- `tests/Unit/BankAccountMapping/LegacyModelCrossReferencesTest.php` (Phase 2)
- `tests/Integration/BankAccountMapping/ImportPipelineTest.php` (Phase 3)
- `tests/Unit/BankAccountMapping/ServiceMigrationTest.php` (Phase 4)
- `tests/E2E/BankAccountMapping/DataFlowTest.php` (Phase 5)

### Files to Modify (Phase 2)
- `app/Legacy/Models/bi_statements_model.php`
- `app/Legacy/Models/bi_transactions_model.php`

### Files to Modify (Phase 3)
- `import_statements.php` (lines 302-320)
- `process_statements.php` (transaction processing section)

### Files to Modify (Phase 4)
- `app/Services/TransferMatchService.php`
- `app/Services/BankImportModuleSchemaService.php`
- `app/Services/ContactService.php`

### No Changes Required
- `app/Shared/Entities/BankAccountMapping.php` ✅ Already complete
- `app/Shared/Repositories/BankAccountMappingRepository.php` ✅ Already complete
- `app/Shared/Factories/BankAccountMappingFactory.php` ✅ Already complete
- `class.bi_lineitem.php` ✅ Already de-duplicated

---

## 7. Testing Command Reference

```powershell
# Phase 2 only
php .\vendor\bin\phpunit tests/Unit/BankAccountMapping/LegacyModelCrossReferencesTest.php

# Phase 3 only
php .\vendor\bin\phpunit tests/Integration/BankAccountMapping/ImportPipelineTest.php

# Phase 4 only
php .\vendor\bin\phpunit tests/Unit/BankAccountMapping/ServiceMigrationTest.php

# Phase 5 only
php .\vendor\bin\phpunit tests/E2E/BankAccountMapping/DataFlowTest.php

# All new tests for this implementation
php .\vendor\bin\phpunit tests/Unit/BankAccountMapping/ tests/Integration/BankAccountMapping/ tests/E2E/BankAccountMapping/

# Full suite (all tests in project)
php .\vendor\bin\phpunit --configuration phpunit.xml --colors=never --no-coverage

# Coverage report
php .\vendor\bin\phpunit --coverage-html coverage/
```

---

## 8. Success Criteria

**Phase 2 Complete**: ✅
- [ ] LegacyModelCrossReferencesTest.php exists with 10+ tests
- [ ] All tests in Phase 2 file pass
- [ ] Exit code: 0
- [ ] Code coverage >90%
- [ ] bi_statements_model has 6 new methods
- [ ] bi_transactions_model has 10 new methods

**Phase 3 Complete**: ✅
- [ ] ImportPipelineTest.php exists with 10+ tests
- [ ] All tests in Phase 3 file pass
- [ ] Exit code: 0
- [ ] import_statements.php modified with factory calls
- [ ] process_statements.php modified with cascade
- [ ] Audit trail for mappings created

**Phase 4 Complete**: ✅
- [ ] ServiceMigrationTest.php exists with 10+ tests
- [ ] All tests in Phase 4 file pass
- [ ] Exit code: 0
- [ ] TransferMatchService refactored
- [ ] BankImportModuleSchemaService refactored
- [ ] ContactService refactored

**Phase 5 Complete**: ✅
- [ ] DataFlowTest.php exists with 10+ tests
- [ ] All tests in Phase 5 file pass
- [ ] Exit code: 0
- [ ] Code coverage >90% for all new code
- [ ] Performance benchmarks met
- [ ] All regression tests pass
- [ ] Full test suite: `php .\vendor\bin\phpunit` exit code 0

---

## 9. Progress Tracking

**Current Status**: Not Started

| Phase | Tests | Implementation | Tests Pass | Quality Gates | Status |
|-------|-------|-----------------|------------|---------------|---------|
| Phase 2 | ⬜ Pending | ⬜ Pending | ⬜ Pending | ⬜ Pending | Not Started |
| Phase 3 | ⬜ Pending | ⬜ Pending | ⬜ Pending | ⬜ Pending | Blocked by P2 |
| Phase 4 | ⬜ Pending | ⬜ Pending | ⬜ Pending | ⬜ Pending | Blocked by P2 |
| Phase 5 | ⬜ Pending | ⬜ Pending | ⬜ Pending | ⬜ Pending | Blocked by P2-P4 |

---

**Next Action**: Begin Phase 2 - Write test suite first (TDD)
