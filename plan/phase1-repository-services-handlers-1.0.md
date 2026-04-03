---
goal: "Implement Repository Layer, Service Migration, and Handler Refactoring with Test-Driven Development"
version: 1.0
date_created: 2026-04-02
last_updated: 2026-04-02
owner: "KSF Bank Import Team"
status: "Planned"
tags: ["architecture", "refactoring", "repositories", "services", "handlers", "TDD", "SOLID", "phase-1"]
---

# Phase 1: Repository Layer & Service Migration

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

**Objective**: Build comprehensive Repository Layer implementations using TDD, refactor Services to use Dependency Injection, and migrate Handlers to SOLID principles.

**Success Criteria**: 
- All 6 Repository implementations complete with 100% test coverage
- All Services injected via DI container with SRP compliance
- Zero regression in existing test suite (maintain 2,019 passing tests)
- Phase 1 deliverables have ≥95% test line coverage

---

## 1. Requirements & Constraints

### High-Level Requirements
- **REQ-001**: All Repository interfaces must have concrete implementations by Phase 1 completion
- **REQ-002**: Services must be refactored to accept Repository dependencies via constructor injection
- **REQ-003**: Legacy handlers must be non-breaking (backward compatibility maintained during migration)
- **REQ-004**: Test coverage must not decrease; baseline = 2,019 tests passing
- **REQ-005**: Each Repository implementation must support CRUD, bulk operations, and domain-specific queries

### Architectural Requirements
- **ARC-001**: All entities retrieved from repositories must be immutable (use factory methods)
- **ARC-002**: Repository methods must throw domain-specific exceptions (EntityNotFoundException, DuplicateEntityException, etc.)
- **ARC-003**: Services must follow Single Responsibility Principle (one reason to change)
- **ARC-004**: Handlers must use repository layer exclusively (no direct `db_query` calls in new code)
- **ARC-005**: DI container must be primary dependency resolver (ServiceContainer::resolve)

### TDD Requirements
- **TDD-001**: Unit tests MUST be written before implementation code (Red → Green → Refactor)
- **TDD-002**: Integration tests required for Repository ↔ Database interactions
- **TDD-003**: Service tests must mock Repository dependencies
- **TDD-004**: Handler tests must mock Service dependencies
- **TDD-005**: 100% method-level test coverage for all Repository methods

### SOLID Requirements
- **SRP-001**: Each Service has exactly one reason to change
- **OCP-001**: Services designed for extension without modification (use interfaces)
- **LSP-001**: Repository implementations honor interface contracts exactly
- **ISP-002**: Services depend on focused interfaces, not monolithic ones
- **DIP-001**: All dependencies injected via interfaces, not concrete classes

### Constraints
- **CON-001**: Cannot modify existing database schema (work with `bi_transactions`, `bi_statements` as-is)
- **CON-002**: Must maintain backward compatibility with legacy code during transition period
- **CON-003**: Legacy `class.bi_*.php` files must continue working (no forced refactoring)
- **CON-004**: No external libraries outside composer.json allowed
- **CON-005**: Phase 1 must complete within 135 hours (3-4 weeks)

### Dependencies
- **DEP-001**: Phase 0 Shared Kernel (Entities, Interfaces, Bootstrap, ServiceContainer)
- **DEP-002**: PHPUnit 9.6.34+ (already available)
- **DEP-003**: FrontAccounting database functions (`db_query`, `db_fetch`, `db_escape`)
- **DEP-004**: Existing `bi_transactions`, `bi_statements` tables

---

## 2. Architecture Overview

### Layered Architecture for Phase 1
```
┌─────────────────────────────────────┐
│       Controllers/Handlers           │ (ProcessStatementsHandler, etc.)
├─────────────────────────────────────┤
│      Services (DI Injected)          │ (TransactionFilterService, etc.)
├─────────────────────────────────────┤
│    Repository Layer (Interface)      │ (Contracts defined in Phase 0)
├─────────────────────────────────────┤
│ Repository Implementations (NEW)     │ (SQL ↔ Entity mapping)
├─────────────────────────────────────┤
│       Legacy Models (Deprecated)     │ (class.bi_*.php - parallel path)
└─────────────────────────────────────┘
         ↓
    FrontAccounting DB
```

### Dependency Flow (TDD Tests Mirror This)
```
Unit Tests (Mocked Repos) → Services
  ↓
Integration Tests (Real DB) → Repository Layer
  ↓
Acceptance Tests (Handler Flow) → Full Stack
```

---

## 3. Implementation Phases

### PHASE 1.1: Transaction Repository & Tests (TDD First)

**GOAL-1A**: Write comprehensive test suite for TransactionRepository  
**GOAL-1B**: Implement TransactionRepository with CRUD and query operations  
**GOAL-1C**: Validate 100% test coverage and zero regressions  

#### Dependency: Phase 0 entities (BiTransaction), existing tests baseline

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.1.1** | Create `tests/Unit/Repository/TransactionRepositoryTest.php` with test stubs for all interface methods | Write 45+ test cases (Red phase) covering: `save()`, `findById()`, `findByCode()`, `findByStatement()`, `findByStatus()`, `update()`, `bulkInsert()`, `bulkUpdate()`, `delete()`, all throwing correct exceptions | Tests execute, all fail (Red) | ⬜ Not Started |
| **TASK-1.1.2** | Create `tests/Integration/Repository/TransactionRepositoryIntegrationTest.php` for real DB testing | 20+ integration tests covering actual database transactions, rollbacks, unique constraint violations | Tests execute, all fail (Red) | ⬜ Not Started |
| **TASK-1.1.3** | Implement `src/Ksfraser/FaBankImport/Shared/Repositories/TransactionRepository.php` - Constructor with DB connection | Accept `\PDO $connection` or FrontAccounting DB functions wrapper, implement `__construct`, property initialization | Unit tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.4** | Implement `save()` method (insert or update logic) | `public function save(BiTransaction $transaction): BiTransaction` returning saved entity with ID, delegate to `hand_insert_sql()` equivalent or direct INSERT | Unit tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.5** | Implement `findById()` method with EntityNotFoundException | `public function findById(int $id): BiTransaction` throwing `EntityNotFoundException` if not found, return immutable entity via `BiTransaction::fromDatabase()` | Unit + integration tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.6** | Implement query methods: `findByCode()`, `findByStatement()`, `findByStatus()` | Domain-specific queries with optional filters, return array of BiTransaction entities | Unit + integration tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.7** | Implement `update()` method with concurrency handling | Update entity handling partial changes, validation, return updated entity; throw `InvalidTransactionException` if invariants violated | Unit tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.8** | Implement bulk operations: `bulkInsert()`, `bulkUpdate()`, `bulkDelete()` | Handle arrays of entities, transactions, rollback on partial failure, return summary | Unit + integration tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.9** | Implement exception handling for all methods | All methods throw domain exceptions correctly (EntityNotFoundException, DuplicateEntityException, RepositoryException) | Exception tests pass (Green) | ⬜ Not Started |
| **TASK-1.1.10** | Refactor for SRP: Extract SQL building to QueryBuilder trait | Move complex SQL construction to `TransactionQueryBuilder` trait to keep Repository focused on persistence logic | Green phase maintained, refactoring doesn't break tests | ⬜ Not Started |
| **TASK-1.1.11** | Run full test suite (Unit + Integration) with coverage report | Execute `phpunit tests/Unit/Repository/TransactionRepositoryTest.php` + integration suite, collect coverage | Coverage ≥95%, all green, integration tests pass with real DB | ⬜ Not Started |

**Additional Validation for Phase 1.1**:
```
✓ All 45+ Unit Tests Pass
✓ All 20+ Integration Tests Pass
✓ Code Coverage ≥95%
✓ Zero regressions in existing 2,019 tests
✓ All exception paths tested
✓ Parallel query performance acceptable (<100ms for bulk operations)
```

---

### PHASE 1.2: Statement Repository & Tests (TDD First)

**GOAL-2A**: Write comprehensive test suite for StatementRepository  
**GOAL-2B**: Implement StatementRepository (similar pattern to Transaction)  
**GOAL-2C**: Validate integration with BankAccountMapping entity  

#### Dependency: BiStatement entity, BankAccountMapping entity, TransactionRepository (from Phase 1.1)

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.2.1** | Create `tests/Unit/Repository/StatementRepositoryTest.php` | Write 35+ test cases for Statement CRUD, query methods, relationship to BankAccountMapping | Tests fail (Red) | ⬜ Not Started |
| **TASK-1.2.2** | Create integration tests with BankAccountMapping relationship | 15+ tests for finding statements by mapping ID, cascade operations | Tests fail (Red) | ⬜ Not Started |
| **TASK-1.2.3** | Implement `src/Ksfraser/FaBankImport/Shared/Repositories/StatementRepository.php` | Full CRUD, `save()`, `findById()`, `findByBankAccountMapping()`, bulk operations | Unit tests pass (Green) | ⬜ Not Started |
| **TASK-1.2.4** | Implement Statement relationship methods | Methods to retrieve associated transactions and bank account mapping | Integration tests pass (Green) | ⬜ Not Started |
| **TASK-1.2.5** | Test suite coverage and regression validation | ≥95% code coverage, all existing tests still pass | Full validation pass | ⬜ Not Started |

---

### PHASE 1.3: BankAccountMapping Repository Implementation (Extends from Phase 0.5)

**GOAL-3A**: Complete partial BankAccountMappingRepository skeleton with full implementation  
**GOAL-3B**: Test comprehensive mapping lifecycle (create → store → retrieve)  
**GOAL-3C**: Validate cascade operations and audit trail  

#### Dependency: BankAccountMapping entity, Transaction & Statement implementations

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.3.1** | Create `tests/Unit/Repository/BankAccountMappingRepositoryTest.php` | 40+ tests covering: create, find, update, delete, findByOFXIdentifiers, cascade behavior | Tests fail (Red) | ⬜ Not Started |
| **TASK-1.3.2** | Refactor `src/Ksfraser/FaBankImport/Shared/Repositories/BankAccountMappingRepository.php` - Remove skeleton | Replace empty methods with real implementations using TDD | Unit tests pass (Green) | ⬜ Not Started |
| **TASK-1.3.3** | Implement core CRUD: `save()`, `findById()`, `update()`, `delete()` | Full immutable entity lifecycle, proper exception handling | Unit tests pass | ⬜ Not Started |
| **TASK-1.3.4** | Implement OFX lookup methods: `findByOFXIdentifiers()`, `getAllMappings()` | Query by bankid/acctid/intu_bid combination | Unit tests pass | ⬜ Not Started |
| **TASK-1.3.5** | Implement audit trail: `getMappingHistory()`, `logMappingChange()` | Track mapping changes with timestamps and user context | Unit tests pass | ⬜ Not Started |
| **TASK-1.3.6** | Implement cascade operations: `cascadeToStatements()`, `cascadeToTransactions()` | Update related records when mapping changes (idempotent) | Integration tests pass | ⬜ Not Started |
| **TASK-1.3.7** | Validation and coverage | ≥95% coverage, all existing tests pass | Full validation | ⬜ Not Started |

---

### PHASE 1.4: Remaining Repository Implementations (Bulk)

**GOAL-4**: Implement LineItem, BankPartner, and TransferMatch repositories following established pattern

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.4.1** | LineItemRepository: Tests + Implementation | Full CRUD for BiLineItem entity, TDD approach | ≥95% coverage | ⬜ Not Started |
| **TASK-1.4.2** | BankPartnerRepository: Tests + Implementation | Full CRUD for BankPartner entity, partner matching queries | ≥95% coverage | ⬜ Not Started |
| **TASK-1.4.3** | TransferMatchRepository: Tests + Implementation | Full CRUD for TransferMatch entity, reverse matching queries | ≥95% coverage | ⬜ Not Started |
| **TASK-1.4.4** | Cross-repository integration tests | Validate relationships between all repositories | All green | ⬜ Not Started |

**Parallel Effort**: Tasks 1.4.1-1.4.3 can execute in parallel (no interdependencies)

---

### PHASE 1.5: Service Layer Refactoring with DI (TDD)

**GOAL-5A**: Refactor existing Services to inject Repository dependencies  
**GOAL-5B**: Create new Service implementations following SRP  
**GOAL-5C**: Test service layer with mocked repositories  

#### Dependency: All Repository implementations from Phase 1.1-1.4

#### Services to Refactor/Create:
1. **TransactionFilterService** - Filter transactions by date, status, account
2. **TransactionMatchingService** - Match bank transactions to FA transactions
3. **StatementImportService** - Orchestrate statement import workflow
4. **BankAccountMappingService** - Manage OFX ↔ FA account mappings
5. **ContactService** - Handle partner/contact updates for transactions
6. **DeduplicationService** - Detect duplicate transactions
7. **ReconciledTransactionService** - Calculate reconciliation status

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.5.1** | Create `tests/Unit/Service/TransactionFilterServiceTest.php` | Mock TransactionRepository, test filter logic with various parameters | Tests fail (Red) | ⬜ Not Started |
| **TASK-1.5.2** | Refactor TransactionFilterService to accept `TransactionRepositoryInterface $repository` | Constructor injection, delegate queries to repository | Unit tests pass (Green) | ⬜ Not Started |
| **TASK-1.5.3** | Repeat 1.5.1-1.5.2 for TransactionMatchingService | Inject Transaction + Statement repositories, test matching logic | Tests pass (Green) | ⬜ Not Started |
| **TASK-1.5.4** | Create StatementImportService (orchestrator) | Coordinates Transaction, Statement, BankAccountMapping repos; implements import workflow | Integration tests pass | ⬜ Not Started |
| **TASK-1.5.5** | Create BankAccountMappingService (new service) | Manages mapping lifecycle, cache invalidation, cascade triggers | Unit + integration tests pass | ⬜ Not Started |
| **TASK-1.5.6** | Repeat for ContactService, DeduplicationService, ReconciledTransactionService | All follow TDD pattern, DI injection, SRP compliance | All tests pass | ⬜ Not Started |
| **TASK-1.5.7** | Register all services in ServiceContainer via configuration | Create `src/Ksfraser/FaBankImport/Shared/Config/RepositoryConfiguration.php` | Container resolves services correctly | ⬜ Not Started |
| **TASK-1.5.8** | Service integration test: Full workflow with real repos + test DB | End-to-end: import statement → save transactions → match transfers | All scenarios green | ⬜ Not Started |

---

### PHASE 1.6: Handler Refactoring to Use Services (Non-Breaking)

**GOAL-6A**: Refactor legacy handlers to use new Service layer  
**GOAL-6B**: Maintain backward compatibility with existing handler API  
**GOAL-6C**: Inject ServiceContainer into handlers  

#### Dependency: All Service implementations from Phase 1.5

#### Handlers to Refactor:
1. ProcessStatementsHandler
2. DeduplicationHandler  
3. BankImportHandler
4. AdminHandler (and variants)
5. ReferenceNumberHandler
6. TransactionMatchHandler

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.6.1** | Analyze ProcessStatementsHandler dependencies | Document current DB calls, service calls, decision logic | Understanding complete | ⬜ Not Started |
| **TASK-1.6.2** | Create ProcessStatementsHandler test with service mocks | Tests verify handler delegates to StatementImportService | Tests fail (Red) | ⬜ Not Started |
| **TASK-1.6.3** | Refactor ProcessStatementsHandler: Accept ServiceContainer in constructor | `public function __construct(ServiceContainer $container)`, resolve services on demand | Tests pass (Green) | ⬜ Not Started |
| **TASK-1.6.4** | Replace direct DB calls with service method calls | `$importService->importStatement()` instead of `db_query()` | Unit tests pass, integration tests pass | ⬜ Not Started |
| **TASK-1.6.5** | Validate handler maintains existing public API (backward compatibility) | Existing code calling handler continues working | Backward compat tests pass | ⬜ Not Started |
| **TASK-1.6.6** | Repeat for remaining 5 handlers (parallel effort) | Same pattern: test → inject → refactor → validate | All tests pass, zero regressions | ⬜ Not Started |
| **TASK-1.6.7** | Full integration test: Handler → Service → Repository → DB | Execute handler trigger, verify data persistence | Integration tests pass | ⬜ Not Started |
| **TASK-1.6.8** | Regression testing: All existing tests still pass | Run full PHPUnit suite (2,019 baseline) | 2,019+ tests pass | ⬜ Not Started |

---

### PHASE 1.7: Test Suite Consolidation & Coverage Analysis

**GOAL-7**: Comprehensive test coverage validation, documentation, and CI/CD readiness

| Task ID | Task Description | Implementation | Testing | Status |
|---------|------------------|-----------------|---------|--------|
| **TASK-1.7.1** | Generate code coverage report for Phase 1 deliverables | `phpunit --log-junit=phase1-coverage.xml --log-coverage=phase1-coverage.html` | Coverage ≥95% for all new code | ⬜ Not Started |
| **TASK-1.7.2** | Identify uncovered code paths and edge cases | Inspect coverage report, create targeted tests for gaps | Coverage reaches 95%+ | ⬜ Not Started |
| **TASK-1.7.3** | Document test organization and patterns | `docs/TESTING_STRATEGY.md` with TDD workflow, test structure, assertions | Documentation complete | ⬜ Not Started |
| **TASK-1.7.4** | Create CI/CD pipeline configuration for test execution | GitHub Actions workflow or equivalent (structured output for parsing) | Pipeline executes tests with XML output | ⬜ Not Started |
| **TASK-1.7.5** | Performance baseline testing | Benchmark repository query performance, service execution time, handler latency | Baseline metrics documented | ⬜ Not Started |
| **TASK-1.7.6** | Final regression testing and validation | Execute all 2,019+ tests, confirm zero new failures | Final pass ✅ | ⬜ Not Started |

---

### PHASE 1.8: Documentation & Knowledge Handoff

**GOAL-8**: Create comprehensive documentation for Phase 1 deliverables

| Task ID | Task Description | Implementation | Status |
|---------|------------------|-----------------|---------|
| **TASK-1.8.1** | Repository Implementation Guide | Document pattern, best practices, examples for extending repositories | ⬜ Not Started |
| **TASK-1.8.2** | Service Layer Architecture | Document SRP principles applied, DI patterns, service responsibilities | ⬜ Not Started |
| **TASK-1.8.3** | Handler Refactoring Patterns | Document before/after comparisons, migration strategy for remaining handlers | ⬜ Not Started |
| **TASK-1.8.4** | TDD Workflow & Test Patterns | Document Red-Green-Refactor cycle, test structure, assertions | ⬜ Not Started |
| **TASK-1.8.5** | Migration Guide: Legacy → New Architecture | Step-by-step guide for developers migrating code | ⬜ Not Started |

---

## 4. Test-Driven Development (TDD) Workflow

### TDD Cycle for Each Task
```
┌─────────────────────────────────────┐
│  1. RED: Write failing tests         │
│     - Define expected behavior       │
│     - Assert on interface contracts  │
│     - Run: phpunit (all fail)        │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│  2. GREEN: Implement minimal code    │
│     - Make tests pass quickly        │
│     - Focus on functionality         │
│     - Run: phpunit (all pass)        │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│  3. REFACTOR: Improve design         │
│     - Apply SOLID principles         │
│     - Reduce duplication             │
│     - Extract helper methods         │
│     - Run: phpunit (still pass)      │
└─────────────────────────────────────┘
```

### Test File Naming Convention
```
tests/Unit/Repository/TransactionRepositoryTest.php    ← Unit tests (mocked dependencies)
tests/Integration/Repository/TransactionRepositoryIntegrationTest.php  ← Integration (real DB)
tests/Unit/Service/TransactionFilterServiceTest.php    ← Service tests (mocked repos)
tests/Integration/Handler/ProcessStatementsHandlerTest.php  ← Handler tests (full stack)
```

### Test Assertion Patterns

#### Repository Tests
```php
// UNIT TEST: Method exists and returns expected type
$this->assertInstanceOf(BiTransaction::class, $result);

// INTEGRATION TEST: Data persists to database
$saved = $repository->save($entity);
$retrieved = $repository->findById($saved->getId());
$this->assertEquals($saved->getId(), $retrieved->getId());

// EXCEPTION TESTING: EntityNotFoundException thrown
$this->expectException(EntityNotFoundException::class);
$repository->findById(99999);
```

#### Service Tests
```php
// Mock repository and verify service delegates correctly
$mockRepo = $this->createMock(TransactionRepositoryInterface::class);
$mockRepo->method('findByStatus')
    ->willReturn([new BiTransaction(...)]);
$service = new TransactionFilterService($mockRepo);
$results = $service->filterByStatus('pending');
$this->assertCount(1, $results);
```

#### Handler Tests
```php
// Mock services and verify handler orchestrates correctly
$mockService = $this->createMock(StatementImportService::class);
$mockService->expects($this->once())
    ->method('importStatement')
    ->willReturn(new BiStatement(...));
$handler = new ProcessStatementsHandler($mockService);
$result = $handler->execute($statementData);
$this->assertTrue($result['success']);
```

---

## 5. Quality Gates & Validation

### Code Coverage Requirements
```
Minimum Coverage per Component:
├── Repositories:      95%+
├── Services:          90%+
├── Handlers:          85%+
├── Overall Phase 1:   95%+
```

### Test Execution Validation
```bash
# Phase 1 test command (structured output)
php vendor/bin/phpunit \
  --configuration phpunit.xml \
  --log-junit=phase1-results.xml \
  --log-coverage=phase1-coverage.html \
  tests/Unit/Repository \
  tests/Unit/Service \
  tests/Integration/Repository \
  tests/Integration/Service

# Expected result:
# ✅ 150+ tests passing
# ✅ 0 failures
# ✅ 0 errors
# ✅ Code coverage ≥95%
```

### Regression Testing
```bash
# Run full existing test suite
php vendor/bin/phpunit phpunit.xml --no-coverage

# Expected result:
# ✅ 2,019+ tests passing (including baseline)
# ✅ 0 new failures
# ✅ Exit code: 0
```

### SRP Compliance Checklist
- [ ] Each Service class has single responsibility
- [ ] No Service knows about HTTP/CLI details
- [ ] Repositories only handle persistence
- [ ] Handlers only orchestrate workflow
- [ ] No circular dependencies (ServiceContainer validates)
- [ ] Exception hierarchy is domain-specific

### SOLID Principles Applied
- **S**: Each class has one reason to change (Services, Handlers)
- **O**: Services/Handlers extended via interfaces, not modified
- **L**: Repository implementations honor interface contracts
- **I**: Services depend on focused Repository interfaces
- **D**: Dependencies injected via interfaces, resolved by ServiceContainer

---

## 6. Implementation Sequence & Dependencies

### Critical Path (Sequential)
```
1.1 TransactionRepository (Foundation)
  ↓
1.2 StatementRepository (Depends on 1.1)
  ↓
1.3 BankAccountMappingRepository (Depends on 1.1, 1.2)
  ↓
1.4 Other Repositories (Can parallelize)
  ↓
1.5 Services (Depends on 1.1-1.4)
  ↓
1.6 Handlers (Depends on 1.5)
  ↓
1.7 Documentation & Validation (Depends on 1.1-1.6)
```

### Parallelizable Tasks
```
1.4.1 LineItemRepository          ≈ Can run parallel with others
1.4.2 BankPartnerRepository       ≈ Can run parallel with others
1.4.3 TransferMatchRepository     ≈ Can run parallel with others

1.5.3-1.5.6 Service refactorings  ≈ Can run parallel (independent services)

1.6.2-1.6.4 Handler refactorings  ≈ Can run parallel (independent handlers)
```

### Time Estimates per Phase
```
1.1 TransactionRepository:           20 hours
1.2 StatementRepository:             15 hours
1.3 BankAccountMappingRepository:    12 hours
1.4 Other Repositories:              18 hours (parallel)
1.5 Service Refactoring:             30 hours (parallel)
1.6 Handler Refactoring:             20 hours (parallel)
1.7 Test Consolidation:              12 hours
1.8 Documentation:                   8 hours
─────────────────────────────────
TOTAL PHASE 1:                      ~135 hours
```

---

## 7. Alternatives Considered

- **ALT-001**: **Skip Repository Pattern, Use DTOs Directly**
  - ❌ Rejected: Violates SOLID (no abstraction), tightly couples to DB implementation, makes testing harder
  
- **ALT-002**: **Use an ORM (Doctrine, Eloquent)**
  - ❌ Rejected: Adds external dependency, overkill for current simple queries, migration cost too high
  
- **ALT-003**: **Keep Legacy Models, No Service Layer**
  - ❌ Rejected: Perpetuates mixed concerns, prevents Phase 2-5 features, architectural debt increases
  
- **ALT-004**: **Implement All Repositories in Parallel Without Dependencies**
  - ⚠️ Considered but rejected: Less optimal knowledge sharing, harder to catch cross-repo patterns early

---

## 8. Dependencies

### External Dependencies (Already Available)
- **DEP-001**: PHPUnit 9.6.34+ (test framework)
- **DEP-002**: FrontAccounting database functions (`db_query`, `db_fetch`, `db_escape`)
- **DEP-003**: Phase 0 Shared Kernel (Entities, Bootstrap, ServiceContainer)
- **DEP-004**: PHP 7.4+ (strict types, typed properties)

### Internal Dependencies (Build Order)
- **DEP-005**: TransactionRepository → Base for other repos
- **DEP-006**: StatementRepository → Depends on TransactionRepository
- **DEP-007**: All Repositories → Required for Service implementations
- **DEP-008**: Services → Required for Handler refactoring
- **DEP-009**: Existing test suite (2,019 tests) → Must remain passing

---

## 9. Files to Create/Modify

### Repository Implementation Files (12 files)
```
src/Ksfraser/FaBankImport/Shared/Repositories/
├── TransactionRepository.php                    ✨ CREATE
├── StatementRepository.php                      ✨ CREATE
├── BankAccountMappingRepository.php             📝 MODIFY (extend skeleton)
├── LineItemRepository.php                       ✨ CREATE
├── BankPartnerRepository.php                    ✨ CREATE
├── TransferMatchRepository.php                  ✨ CREATE
└── Traits/
    ├── TransactionQueryBuilder.php              ✨ CREATE (SRP extraction)
    ├── StatementQueryBuilder.php                ✨ CREATE
    └── CommonQueryPatterns.php                  ✨ CREATE
```

### Test Files (20+ files)
```
tests/Unit/Repository/
├── TransactionRepositoryTest.php                ✨ CREATE
├── StatementRepositoryTest.php                  ✨ CREATE
├── BankAccountMappingRepositoryTest.php         ✨ CREATE
├── LineItemRepositoryTest.php                   ✨ CREATE
├── BankPartnerRepositoryTest.php                ✨ CREATE
└── TransferMatchRepositoryTest.php              ✨ CREATE

tests/Integration/Repository/
├── TransactionRepositoryIntegrationTest.php     ✨ CREATE
├── StatementRepositoryIntegrationTest.php       ✨ CREATE
├── RepositoryCascadeTest.php                    ✨ CREATE
└── RepositoryConcurrencyTest.php                ✨ CREATE

tests/Unit/Service/
├── TransactionFilterServiceTest.php             ✨ CREATE
├── TransactionMatchingServiceTest.php           ✨ CREATE
├── StatementImportServiceTest.php               ✨ CREATE
└── [3 more service tests]                       ✨ CREATE

tests/Integration/Handler/
├── ProcessStatementsHandlerRefactoredTest.php   ✨ CREATE
├── BankImportHandlerRefactoredTest.php          ✨ CREATE
└── [4 more handler tests]                       ✨ CREATE
```

### Service Refactoring/Creation Files (12 files)
```
src/Ksfraser/FaBankImport/services/
├── TransactionFilterService.php                 📝 MODIFY (add DI)
├── TransactionMatchingService.php               📝 MODIFY (add DI)
├── StatementImportService.php                   ✨ CREATE (new orchestrator)
├── BankAccountMappingService.php                ✨ CREATE (new)
├── ContactService.php                           📝 MODIFY (add DI)
├── DeduplicationService.php                     ✨ CREATE or 📝 MODIFY
└── ReconciledTransactionService.php             ✨ CREATE (new)
```

### Handler Refactoring Files (12 files)
```
src/[Various]/Handlers/
├── ProcessStatementsHandler.php                 📝 MODIFY (add DI)
├── DeduplicationHandler.php                     📝 MODIFY (add DI)
├── BankImportHandler.php                        📝 MODIFY (add DI)
├── AdminHandler.php                             📝 MODIFY (add DI)
└── [More handlers]                              📝 MODIFY
```

### Configuration/Documentation Files (5 files)
```
src/Ksfraser/FaBankImport/Shared/Config/
├── RepositoryConfiguration.php                  ✨ CREATE (ServiceContainer wiring)
└── ServiceConfiguration.php                     ✨ CREATE

docs/
├── PHASE_1_TESTING_STRATEGY.md                  ✨ CREATE
├── REPOSITORY_IMPLEMENTATION_GUIDE.md           ✨ CREATE
├── SERVICE_MIGRATION_GUIDE.md                   ✨ CREATE
└── HANDLER_REFACTORING_PATTERNS.md              ✨ CREATE
```

---

## 10. Testing Strategy Summary

### Test Pyramid for Phase 1
```
           ⬆️
          / \
         /   \  Integration Tests (15 tests)
        /     \ Repository & Service integration
       /       \
      /─────────\
     /           \  Unit Tests (120+ tests)
    /    UNIT    \ Repository methods, Service logic
   /             \ (Mocked dependencies)
  /_______________\
        ⬆️
    Base Layer:
    - TDD: Red → Green → Refactor
    - Mocked repository dependencies
    - Fast execution (<5 sec total)
```

### Test Coverage Targets
| Component | Unit | Integration | Total | Target |
|-----------|------|-------------|-------|--------|
| Repositories | 80%+ | 95%+ | 90%+ | 95% |
| Services | 85%+ | 80%+ | 85%+ | 90% |
| Handlers | 75%+ | 85%+ | 80%+ | 85% |
| **Overall** | **80%+** | **85%+** | **85%+** | **≥95%** |

### Continuous Validation
- After each task/phase: `phpunit --coverage-html=report/` → Verify baseline maintained
- Before merge: All 150+ new tests pass + 2,019 baseline tests pass
- Performance: No query should exceed 100ms; bulk operations <500ms

---

## 11. Success Criteria Checklist

### Phase 1 Complete When:
- [ ] All 6 Repository interfaces have working implementations (TASK-1.1 through 1.4)
- [ ] 100+ unit tests written for repositories (TDD: Red → Green → Refactor completion)
- [ ] 30+ integration tests verify database persistence
- [ ] All Services refactored to use dependency injection
- [ ] All Handlers refactored to use new Service layer
- [ ] Code coverage ≥95% for all new code
- [ ] All existing 2,019 tests pass (zero regressions)
- [ ] No SRP violations in Services or Handlers
- [ ] No SOLID principle violations identified
- [ ] Repository query performance benchmarked and acceptable
- [ ] ServiceContainer successfully resolves all dependencies
- [ ] Documentation complete (testing strategy, patterns, migration guide)

### Validation Pass Criteria
```bash
✅ phpunit unit tests: 120+ passing
✅ phpunit integration tests: 30+ passing  
✅ phpunit full suite: 2,019+ passing (baseline + new)
✅ Code coverage: ≥95% for Phase 1 code
✅ Exit code: 0 (no failures, no errors)
✅ Performance: All queries <100ms
✅ SOLID analysis: 0 violations
✅ Documentation: 4 guides complete
```

---

## 12. Risk Assessment & Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|-----------|
| **Performance 🚨** | Medium | High | Benchmark queries early (Task 1.3), optimize if needed before Phase 1.5 |
| **Regression in Tests** | Low | Critical | Run full test suite after each phase, use coverage reports |
| **Circular Dependencies** | Low | Medium | ServiceContainer validates during resolution; enable strict mode |
| **Database Schema Unfamiliarity** | Medium | Medium | Document schema assumptions early, test with actual tables |
| **Scope Creep** | High | Medium | Strict task boundaries, track time, escalate scope changes immediately |
| **Team Unfamiliar with TDD** | Medium | High | Pair programming, code reviews, create TDD howto guide (Task 1.8.4) |

---

## 13. Next Steps Post-Phase 1

Once Phase 1 completes successfully:

### Phase 2: Advanced Features & Optimization
- Transfer matching enhancement
- Duplicate detection algorithms
- Performance optimization (caching, query optimization)

### Phase 3: Admin Features
- Configuration management UI
- Audit trail visualization
- Batch operations

### Phase 4: Integration Features
- Multi-bank support
- Custom field mappings
- Data export/reporting

### Phase 5: Full Module Replacement
- Complete sunset of legacy models
- Unified API across all modules
- Full test automation

---

## Glossary & Key Definitions

| Term | Definition |
|------|-----------|
| **Repository** | Persistence abstraction; converts between Domain Entities and Database |
| **Entity** | Immutable domain model (BiTransaction, BiStatement, etc.) |
| **Service** | Business logic orchestrator; uses repositories to persist entities |
| **Handler** | HTTP/CLI entry point; coordinates services and returns responses |
| **DI (Dependency Injection)** | Constructor parameters receive dependencies; ServiceContainer resolves |
| **TDD** | Red → Green → Refactor cycle; tests written before implementation |
| **SRP** | Single Responsibility Principle; each class has one reason to change |
| **SOLID** | S+O+L+I+D principles for maintainable, extensible code |

---

## Document Metadata

| Field | Value |
|-------|-------|
| **Created** | 2026-04-02 |
| **Version** | 1.0 |
| **Status** | 🔵 Planned (Ready for Execution) |
| **Owner** | KSF Bank Import Team |
| **Review Cycle** | Post-Phase per task completion |
| **Communication** | Update this document after each phase; mark completed tasks with ✅ date |

---

**END PHASE 1 IMPLEMENTATION PLAN**
