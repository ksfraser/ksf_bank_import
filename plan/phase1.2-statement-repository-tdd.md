# Phase 1.2: StatementRepository - TDD Implementation

## Overview
Implement the StatementRepository following the same TDD discipline as Phase 1.1 (TransactionRepository). This repository manages `BiStatement` entities - imported bank statements.

## Phase 1.2 Phases

### Phase 1.2a: RED - Test Suite Creation (45+ tests)

**File**: `tests/Unit/Repository/StatementRepositoryTest.php`

**Test Groups**:

1. **Constructor & Initialization (2 tests)**
   - RepositoryImplementsInterface
   - RepositoryAcceptsPdoConnection

2. **Save Operation (5 tests)**
   - SaveNewStatementReturnsId
   - SaveStatementPersistsToDatabase
   - SaveReturnsIdNotEntity
   - SaveThrowsExceptionForMissingBankId
   - SaveThrowsExceptionForMissingAcctId

3. **Find by ID (3 tests)**
   - FindByIdReturnsStatementEntity
   - FindByIdThrowsEntityNotFoundException
   - FindByIdReturnsCorrectStatementData

4. **Query Methods - By Bank (3 tests)**
   - FindByBankIdReturnsArrayOfStatements
   - FindByBankIdReturnsEmptyArrayForNoMatches
   - FindByBankIdReturnsOnlyMatchingStatements

5. **Query Methods - By Account (3 tests)**
   - FindByAcctIdReturnsStatements
   - FindByAcctIdReturnsEmptyArrayForNoMatches
   - FindByAcctIdPaginates

6. **Query Methods - By Date Range (3 tests)**
   - FindByDateRangeReturnsStatements
   - FindByDateRangeReturnsEmptyArrayForOutOfRange
   - FindByDateRangeSupportsLimit

7. **Update Operation (3 tests)**
   - UpdateStatementUpdatesDatabaseRecord
   - UpdateReturnsId
   - UpdateThrowsExceptionIfNotFound

8. **Delete Operation (2 tests)**
   - DeleteRemovesStatementFromRepository
   - DeleteThrowsExceptionIfNotFound

9. **Bulk Operations (3 tests)**
   - BulkInsertReturnsArrayOfIds
   - BulkInsertEmptyArrayReturnsEmpty
   - BulkInsertRollsBackOnError

10. **Query Filtered Operations (4 tests)**
    - FindByStatusReturnsFilteredStatements
    - FindByPartnerTypeReturnsStatements
    - FindUnprocessedReturnsStatements
    - FindProcessedReturnsStatements

11. **Count Operations (2 tests)**
    - CountReturnsIntegerCount
    - CountByStatusReturnsCount

12. **Exception Handling (3 tests)**
    - FindByIdThrowsRepositoryExceptionOnDatabaseError
    - SaveThrowsRepositoryExceptionOnDatabaseError
    - UpdateThrowsRepositoryExceptionOnDatabaseError

13. **Performance Metrics (2 tests)**
    - FindByDateRangeCompletesBelowThreshold
    - BulkInsertCompletesBelowThreshold

14. **Complex Queries (3 tests)**
    - FindByFilter CombinesMultipleCriteria
    - FindWithPaginationOffsetsCorrectly
    - FindSortsResultsCorrectly

15. **Integration Tests (2 tests)**
    - RoundTripCreateReadVerify
    - UpdatePreservesImmutabilityViaNewEntity

16. **Data Integrity (2 tests)**
    - StatementFieldsCorrectlyMapped
    - BankAccountMappingPreervesIntegrity

**Total**: 45+ tests

### Phase 1.2b: GREEN - Implement StatementRepository

**File**: `app/Shared/Repositories/StatementRepository.php`

**Methods to implement** (all from StatementRepositoryInterface):

1. `findById(int $id): BiStatement`
2. `findByBankId(string $bankId, ?int $limit = null, ?int $offset = null): array`
3. `findByAcctId(string $acctId, ?int $limit = null, ?int $offset = null): array`
4. `findByDateRange(string $startDate, string $endDate, ?int $limit = null, ?int $offset = null): array`
5. `save(BiStatement $statement): int`
6. `update(BiStatement $statement): int`
7. `delete(int $id): bool`
8. `count(): int`
9. `bulkInsert(array $statements): array`
10. `bulkUpdate(array $statements): int`
11. `bulkDelete(array $ids): int`
12. `findByStatus(string $status, ?int $limit = null, ?int $offset = null): array`
13. `findByPartnerType(string $partnerType, ?int $limit = null, ?int $offset = null): array`
14. `findUnprocessed(?int $limit = null, ?int $offset = null): array`
15. `findProcessed(?int $limit = null, ?int $offset = null): array`
16. `countByStatus(string $status): int`

**Implementation Details**:
- Use PDO prepared statements for all queries
- Entity hydration via `BiStatement::fromDatabase()`
- Transaction support for bulk operations
- Exception handling with domain-specific types
- Private helpers: `entityFromRow()`, `entitiesToArray()`, `buildParams()`

### Phase 1.2c: REFACTOR - Apply SOLID Principles

- Extract complex query building to helper methods
- Ensure immutability of returned entities
- Optimize query performance (indexes, query plans)
- Apply Repository pattern correctly
- Code coverage ≥95%

## BiStatement Interface Check

**Interface File**: `app/Shared/Repositories/StatementRepositoryInterface.php`

- Verify all 16 methods are defined with correct signatures
- Check return types (BiStatement, array, int, bool)
- Verify exception declarations

## Database Setup

**Table**: `bi_statements`

**Mock Setup** (for tests):
- Mock PDO with `prepare()` returning mock PDOStatement
- Mock statement with `execute()`, `fetch()`, `fetchAll()` methods
- Use correct database field names: `id`, `bankid`, `acctid`, etc.
- Mock `lastInsertId()` for insert operations

## Token-Efficient Testing Strategy

Per AGENTS.md:
1. Run all tests ONCE with `--log-junit=test-results-statement.xml`
2. Parse results to JSON using PHP script
3. Share summary (counts, pass/fail) in chat
4. Only re-run specific failing tests if needed
5. **Do NOT** run tests multiple times

## Success Criteria

- ✅ All 45+ tests pass (GREEN phase)
- ✅ Zero regressions (2,019+ Phase 0 tests still passing)
- ✅ Coverage ≥95% for StatementRepository
- ✅ SOLID principles applied (REFACTOR phase)
- ✅ Database operations <100ms (performance metrics)
- ✅ Immutable entities returned from all methods
- ✅ Comprehensive exception handling

## Estimated Effort

- RED phase (test creation): 30 minutes
- GREEN phase (implementation): 45 minutes  
- REFACTOR phase (SOLID): 30 minutes
- **Total**: ~2 hours

## Next Steps After 1.2

1. Phase 1.3: LineItemRepository (same TDD pattern)
2. Phase 1.4: PartnerRepository (same TDD pattern)
3. Phase 1.5: Transfer match repositories (can parallelize)
4. Phase 1.6: Service layer implementations
5. Phase 1.7: Event handling/AOP
6. Phase 1.8: Integration testing

---

**Created**: 2026-04-03
**Status**: Ready for RED phase execution
**TDD Discipline**: Embedded throughout
