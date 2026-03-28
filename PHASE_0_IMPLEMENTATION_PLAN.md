---
goal: "Extract and consolidate Shared Kernel foundation layer for modular monolith architecture"
version: "1.0"
date_created: "2026-03-28"
status: "Planned"
owner: "Architecture Team - Phase Lead: TBD"
tags: ["architecture", "refactoring", "foundation", "phase-0", "modular-monolith"]
---

# Phase 0: Shared Kernel Foundation Extraction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

Extract and consolidate the Shared Kernel foundation layer that will serve as the stable contract foundation for all four independent modules (Process, Import, Dedupe, Admin).

**Duration**: 1 week (5 working days)  
**Risk Level**: LOW  
**Resource Requirement**: 1 Senior Developer + 1 Code Reviewer  
**Dependency**: None (can start immediately after approval)

---

## 1. Requirements & Constraints

### Functional Requirements

- **REQ-001**: Create `src/Ksfraser/FaBankImport/Shared/` folder structure with all subdirectories
- **REQ-002**: Consolidate all DTOs from multiple locations into `Shared/DTOs/`
- **REQ-003**: Consolidate all domain entities from `class.*.php` legacy files into `Shared/Entities/`
- **REQ-004**: Create interface contracts in `Shared/Contracts/` defining module boundaries
- **REQ-005**: Create `Shared/Container/ServiceContainer.php` for dependency injection
- **REQ-006**: Create centralized config in `Shared/Config/BankImportConfig.php`
- **REQ-007**: Create exception hierarchy in `Shared/Exceptions/`
- **REQ-008**: Write unit tests for all Shared kernel classes (≥80% coverage)
- **REQ-009**: Document contract stability guarantees in `Shared/README_SHARED_KERNEL.md`

### Technical Constraints

- **CON-001**: Backward compatibility must be maintained (old code still works during transition)
- **CON-002**: DO NOT delete old files/locations yet (keep for 1-2 releases)
- **CON-003**: All Shared kernel classes must have ZERO dependencies on other modules
- **CON-004**: Namespace must strictly follow `Ksfraser\FaBankImport\Shared\*` convention
- **CON-005**: No circular dependencies allowed (enforced via static analyzer)
- **CON-006**: All DTOs must be immutable or have explicit mutation patterns

### Security Requirements

- **SEC-001**: No hard-coded secrets in Shared kernel files
- **SEC-002**: Database credentials must come from centralized Config only
- **SEC-003**: All inputs validated before use in Shared classes

### Guidelines

- **GUD-001**: Follow PHP-FIG PSR-4 autoloading (namespace → folder structure)
- **GUD-002**: Use dependency injection, never static service locators
- **GUD-003**: Document all public interfaces with PHPDoc comments
- **GUD-004**: Write tests as you write code, not after

---

## 2. Current State Analysis

### Current DTO Locations (To Consolidate)
```
root/                           → src/Ksfraser/FaBankImport/Shared/DTOs/
├── DTOs.php
├── src/Model/
│   ├── TransactionDTO.php
│   ├── StatementDTO.php
│   └── [other DTOs]
└── src/Ksfraser/FaBankImport/
    └── DTOs/
        ├── DuplicateCheckResult.php
        └── [other DTOs]
```

### Current Entity Locations (To Consolidate)
```
class files:                    → src/Ksfraser/FaBankImport/Shared/Entities/
├── class.bi_transaction.php
├── class.bi_lineitem.php
├── class.bi_statement.php
├── class.bi_bank_accounts.php
├── class.bi_counterparty_model.php
├── class.bi_partners_data.php
└── class.bi_transactionTitle_model.php
```

### Current Exception Locations (To Consolidate)
- Scattered across various location files
- No unified exception hierarchy
- Will create in `Shared/Exceptions/`

---

## 3. Implementation Steps

### Phase 0.1: Create Shared Kernel Directory Structure (Day 1 AM)

**GOAL-0.1**: Create folder structure and baseline files for Shared kernel

#### Task 0.1.1: Create Directory Structure
- **Work**: Create folder hierarchy under `src/Ksfraser/FaBankImport/Shared/`
- **Directories to create**:
  ```
  src/Ksfraser/FaBankImport/Shared/
  ├── Contracts/           (interfaces)
  ├── DTOs/                (data transfer objects)
  ├── Entities/            (domain models)
  ├── ValueObjects/        (immutable value types)
  ├── Repositories/        (repository interfaces)
  ├── Exceptions/          (exception hierarchy)
  ├── Config/              (configuration)
  ├── Container/           (dependency injection)
  ├── Traits/              (shared traits)
  └── .gitkeep             (placeholder for git)
  ```
- **Command**: `mkdir -p src/Ksfraser/FaBankImport/Shared/{Contracts,DTOs,Entities,ValueObjects,Repositories,Exceptions,Config,Container,Traits}`
- **Verification**: `ls -la src/Ksfraser/FaBankImport/Shared/` should show all folders

#### Task 0.1.2: Create README for Shared Kernel
- **File**: `src/Ksfraser/FaBankImport/Shared/README_SHARED_KERNEL.md`
- **Content**: Document purpose, stability guarantees, no external module deps
- **Status marker**: "Phase 0 In Progress"
- **Verification**: File exists and is readable

---

### Phase 0.2: Consolidate DTOs (Day 1-2)

**GOAL-0.2**: Move all DTOs to `Shared/DTOs/` with updated namespaces

#### Task 0.2.1: Audit Current DTOs
- **Work**: Identify all DTO files across codebase
- **Search command**: `find . -name "*DTO.php" -o -name "DTOs.php"`
- **Expected locations**:
  - `root/DTOs.php`
  - `src/Model/*.php` (TransactionDTO.php, etc.)
  - `src/Ksfraser/FaBankImport/DTOs/*.php`
  - `src/Ksfraser/FaBankImport/Dedupe/DTOs/*.php`
- **Deliverable**: Spreadsheet/list of all DTOs with current location
- **Verification**: Grep count matches actual DTO files: `grep -r "class.*DTO" --include="*.php" | wc -l`

#### Task 0.2.2: Create Canonical DTOs in Shared
- **Process**: For each DTO from audit:
  1. Copy to `src/Ksfraser/FaBankImport/Shared/DTOs/ClassName.php`
  2. Update namespace to `Ksfraser\FaBankImport\Shared\DTOs`
  3. Add PHPDoc with stability marker: `@stable` or `@experimental`
  4. Create corresponding unit test: `tests/unit/Shared/DTOs/ClassNameTest.php`

- **Example DTOs to consolidate** (priority order):
  1. TransactionDTO
  2. StatementDTO
  3. ImportResultDTO
  4. DuplicateCheckResultDTO
  5. ProcessResultDTO
  6. [others as identified in audit]

- **File template**:
  ```php
  <?php
  namespace Ksfraser\FaBankImport\Shared\DTOs;

  /**
   * TransactionDTO - Data Transfer Object for bank transactions
   * 
   * @package Ksfraser\FaBankImport\Shared\DTOs
   * @stable - Part of public Shared Kernel API
   */
  class TransactionDTO
  {
      // immutable properties
      // getter methods only
      // private __set to prevent mutation
  }
  ```

- **Verification**:
  - All DTOs copyable without errors
  - Namespace correct: `grep "namespace Ksfraser.*Shared.*DTOs" src/Ksfraser/FaBankImport/Shared/DTOs/*.php`
  - No syntax errors: `php -l src/Ksfraser/FaBankImport/Shared/DTOs/*.php`

#### Task 0.2.3: Update All Imports
- **Process**: Find and update all imports referencing old DTO locations
- **Search for**:
  - `use Ksfraser\FaBankImport\Model\*DTO` → `Ksfraser\FaBankImport\Shared\DTOs`
  - `use Model\*DTO` → `Ksfraser\FaBankImport\Shared\DTOs`
  - `class_exists('src/Model/TransactionDTO')` → update paths

- **Locations to check**:
  - All files in `src/Ksfraser/FaBankImport/Process/`
  - All files in `src/Ksfraser/FaBankImport/Import/`
  - All files in `src/Ksfraser/FaBankImport/Dedupe/`
  - All files in tests/

- **Command**: `grep -r "Model.*DTO\|use Model" src/ tests/ --include="*.php" | cut -d: -f1 | sort -u`

- **Verification**:
  - No import errors: `grep -r "use.*DTO" src/ tests/ --include="*.php" should show Shared\DTOs`
  - Zero errors: `php -l` on all updated files

---

### Phase 0.3: Consolidate Entities (Day 2-3)

**GOAL-0.3**: Convert legacy `class.*.php` files to modern Entity classes in `Shared/Entities/`

#### Task 0.3.1: Audit Legacy Entity Classes
- **Work**: Analyze each `class.*.php` file to understand structure and usage
- **Files to audit**:
  - `class.bi_transaction.php`
  - `class.bi_lineitem.php`
  - `class.bi_statement.php`
  - `class.bi_bank_accounts.php`
  - `class.bi_counterparty_model.php`
  - `class.bi_partners_data.php`
  - `class.bi_transactionTitle_model.php`

- **Audit checklist** (per file):
  - [ ] Identify public methods and properties
  - [ ] Identify database schema it maps to
  - [ ] Identify where it's currently used
  - [ ] Determine if it should be Entity, ValueObject, or Repository
  - [ ] Note any FA framework dependencies

- **Deliverable**: Analysis document: `ENTITY_MIGRATION_ANALYSIS.md`

- **Verification**: All files analyzed and documented

#### Task 0.3.2: Create Canonical Entity Classes
- **Process**: For each legacy class:
  1. Create modern OOP version in `Shared/Entities/`
  2. Use immutable properties where possible
  3. Define clear responsibility (Entity vs ValueObject)
  4. Add proper PHPDoc

- **Example Entity Structure**:
  ```php
  <?php
  namespace Ksfraser\FaBankImport\Shared\Entities;

  /**
   * BiTransaction - Domain entity for bank transactions
   * 
   * Invariants:
   * - amount must be > 0
   * - date must be valid
   * - cannot be modified after creation
   * 
   * @stable
   */
  class BiTransaction
  {
      private int $id;
      private string $code;
      private float $amount;
      private \DateTime $transactionDate;
      
      // immutable: getters only, no setters
      // factory methods for creation
  }
  ```

- **Priority order**:
  1. BiTransaction (used everywhere)
  2. BiStatement (import output)
  3. BiLineItem (transaction detail)
  4. BankAccount (import target)
  5. Partner (matching/processing)

- **Verification**:
  - No syntax errors: `php -l src/Ksfraser/FaBankImport/Shared/Entities/*.php`
  - All entities in one namespace: `grep "^namespace" src/Ksfraser/FaBankImport/Shared/Entities/*.php`

#### Task 0.3.3: Create Repository Interfaces
- **Work**: Define repository interfaces for each entity
- **File**: `src/Ksfraser/FaBankImport/Shared/Repositories/`
- **Interfaces to create**:
  - `TransactionRepositoryInterface`
  - `StatementRepositoryInterface`
  - `BankAccountRepositoryInterface`
  - `PartnerRepositoryInterface`

- **Example Interface**:
  ```php
  <?php
  namespace Ksfraser\FaBankImport\Shared\Repositories;

  use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

  interface TransactionRepositoryInterface
  {
      public function findById(int $id): ?BiTransaction;
      public function save(BiTransaction $transaction): void;
      public function findByCode(string $code): ?BiTransaction;
  }
  ```

- **Verification**: All interfaces compile: `php -l src/Ksfraser/FaBankImport/Shared/Repositories/*Interface.php`

---

### Phase 0.4: Create Interface Contracts (Day 3-4)

**GOAL-0.4**: Define module boundary contracts in `Shared/Contracts/`

#### Task 0.4.1: Create Module Interfaces
- **File**: `src/Ksfraser/FaBankImport/Shared/Contracts/`
- **Interfaces to create** (priority order):

1. **ModuleBootstrapInterface**
   ```php
   interface ModuleBootstrapInterface
   {
       public static function bootstrap(ServiceContainer $container): void;
   }
   ```

2. **DuplicateDetectionInterface** (Import calls Dedupe via this)
   ```php
   interface DuplicateDetectionInterface
   {
       public function detectDuplicates(TransactionDTO $tx): DuplicateCheckResultDTO;
       public function auditMatch(DuplicatePairDTO $pair, string $decision): AuditResultDTO;
   }
   ```

3. **TransactionProcessorInterface**
   ```php
   interface TransactionProcessorInterface
   {
       public function processTransaction(TransactionDTO $tx): ProcessResultDTO;
   }
   ```

4. **ImportHandlerInterface**
   ```php
   interface ImportHandlerInterface
   {
       public function handle(ImportStateDTO $state): ImportStateDTO;
   }
   ```

- **Deliverable**: All interface files in `Shared/Contracts/`
- **Verification**: `php -l src/Ksfraser/FaBankImport/Shared/Contracts/*Interface.php`

#### Task 0.4.2: Document Contract Specifications
- **Update**: `CONTRACT_SPECIFICATIONS.md` already created, verify completeness
- **Sections to verify**:
  - [ ] What each module provides
  - [ ] What each module depends on
  - [ ] Data flow scenarios
  - [ ] Dependency rules (DO's/DON'Ts)

---

### Phase 0.5: Create Dependency Injection Container (Day 4)

**GOAL-0.5**: Implement `ServiceContainer` for dependency injection (foundation for module bootstrap)

#### Task 0.5.1: Create ServiceContainer Class

- **File**: `src/Ksfraser/FaBankImport/Shared/Container/ServiceContainer.php`
- **Required methods**:
  ```php
  public function register(string $abstract, callable $concrete): void;
  public function singleton(string $abstract, callable $concrete): void;
  public function get(string $abstract): mixed;
  public function has(string $abstract): bool;
  ```

- **Features**:
  - [ ] Factory registrations (create new instance each time)
  - [ ] Singleton registrations (reuse same instance)
  - [ ] Lazy-loading (create on first access)
  - [ ] Container passed to closures for dependency resolution
  - [ ] Circular dependency detection

- **Example usage** (for documentation):
  ```php
  $container = new ServiceContainer();
  
  // Factory registration
  $container->register(
      TransactionRepositoryInterface::class,
      fn() => new TransactionRepository()
  );
  
  // Singleton registration
  $container->singleton(
      BankImportConfig::class,
      fn() => new BankImportConfig()
  );
  
  // Get dependency
  $repository = $container->get(TransactionRepositoryInterface::class);
  ```

- **Verification**:
  - No syntax errors: `php -l src/Ksfraser/FaBankImport/Shared/Container/ServiceContainer.php`
  - Test file: `tests/unit/Shared/Container/ServiceContainerTest.php` passes
  - Test coverage ≥85%

#### Task 0.5.2: Create ModuleRegistry

- **File**: `src/Ksfraser/FaBankImport/Shared/Container/ModuleRegistry.php`
- **Purpose**: Register and discover modules
- **Methods**:
  ```php
  public function register(string $moduleName, string $bootstrapClass): void;
  public function bootstrap(ServiceContainer $container): void;
  ```

- **Verification**: Tests pass for bootstrap of each module (mock implementations)

---

### Phase 0.6: Create Centralized Configuration (Day 4)

**GOAL-0.6**: Consolidate all configuration into `Shared/Config/`

#### Task 0.6.1: Create BankImportConfig

- **File**: `src/Ksfraser/FaBankImport/Shared/Config/BankImportConfig.php`
- **Configuration values to include**:
  ```php
  // Database
  DB_HOST, DB_USER, DB_PASS, DB_NAME
  
  // Import settings
  SUPPORTED_PARSERS, MAX_UPLOAD_SIZE, TEMP_PATH
  
  // Feature flags
  ENABLE_DEDUPE_CHECK, ENABLE_TRANSFER_MATCHING
  
  // Logging
  LOG_LEVEL, LOG_PATH
  
  // Processing
  BATCH_SIZE, TIMEOUT
  ```

- **Loading strategy**:
  - Environment variables (production priority)
  - `.env` file (development)
  - Defaults in code (fallback)

- **Verification**:
  - Config loads without errors: `php -r "require 'config/bank_import.config.php';"`
  - All required keys present
  - No hardcoded secrets

---

### Phase 0.7: Create Exception Hierarchy (Day 4)

**GOAL-0.7**: Define exception hierarchy in `Shared/Exceptions/`

#### Task 0.7.1: Create Exception Classes

- **File**: `src/Ksfraser/FaBankImport/Shared/Exceptions/`
- **Exception hierarchy**:
  ```
  BankImportException (base)
  ├── ValidationException
  ├── ConfigurationException
  ├── DatabaseException
  ├── ProcessingException
  ├── ImportException
  ├── DuplicateDetectionException
  └── [others as needed]
  ```

- **Base class example**:
  ```php
  class BankImportException extends \Exception
  {
      private array $context = [];
      
      public function __construct(string $message, array $context = [], int $code = 0) {
          $this->context = $context;
          parent::__construct($message, $code);
      }
  }
  ```

- **Verification**: `php -l src/Ksfraser/FaBankImport/Shared/Exceptions/*.php`

---

### Phase 0.8: Write Unit Tests (Day 5)

**GOAL-0.8**: Create unit test suite for Shared kernel (minimum 80% coverage)

#### Task 0.8.1: Create Test Structure
- **Directory**: `tests/unit/Shared/`
- **Structure**:
  ```
  tests/unit/Shared/
  ├── DTOs/
  ├── Entities/
  ├── Container/
  ├── Config/
  ├── Exceptions/
  └── Repositories/
  ```

#### Task 0.8.2: Write DTO Tests
- **Scope**: Test each DTO for:
  - [ ] Instantiation with valid data
  - [ ] Immutability (can't change after creation)
  - [ ] Serialization/deserialization
  - [ ] Required field validation

#### Task 0.8.3: Write Entity Tests
- **Scope**: Test each Entity for:
  - [ ] Invariants (amount > 0, dates valid)
  - [ ] Factory methods work
  - [ ] Getters return correct values
  - [ ] Can't modify after creation

#### Task 0.8.4: Write Container Tests
- **Scope**: Test ServiceContainer:
  - [ ] Register and retrieve services
  - [ ] Singletons reuse same instance
  - [ ] Factories create new instances
  - [ ] Circular dependency detection works

#### Task 0.8.5: Run Full Test Suite
- **Command**: `vendor/bin/phpunit tests/unit/Shared/ --coverage-text`
- **Target**: ≥80% coverage
- **Requirement**: All tests pass (`--colors=never --no-coverage`)

- **Verification**:
  ```bash
  # Coverage report
  vendor/bin/phpunit tests/unit/Shared/ --coverage-html coverage/shared/
  
  # All tests pass
  vendor/bin/phpunit tests/unit/Shared/ --colors=never --no-coverage
  ```

---

### Phase 0.9: Update Root Entry Points (Day 5)

**GOAL-0.9**: Create root bootstrap that initializes Shared kernel

#### Task 0.9.1: Create src/bootstrap.php

- **File**: `src/bootstrap.php`
- **Purpose**: Initialize all modules
- **Content**:
  ```php
  <?php
  // shared/bootstrap.php
  use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;
  use Ksfraser\FaBankImport\Shared\Container\ModuleRegistry;
  
  // Create DI container
  $container = new ServiceContainer();
  
  // Bootstrap Shared kernel
  Ksfraser\FaBankImport\Shared\Config\BankImportConfig::initialize();
  
  // Register modules (after Shared is ready)
  $registry = new ModuleRegistry();
  // $registry->register('Process', Process\ModuleBootstrap::class);
  // $registry->register('Import', Import\ModuleBootstrap::class);
  // etc (commented out for Phase 0)
  
  return $container;
  ```

- **Verification**: File exists and is includable: `php -r "require 'src/bootstrap.php';"`

#### Task 0.9.2: Update Existing Entry Points (Compatibility Layer)

- **Files to update**: `process_statements.php`, `import_statements.php`, etc.
- **Action**: Add `include 'src/bootstrap.php'` at top (for Phase 1 transition)
- **Keep existing logic working** during transition
- **Marker**: Add comment: `// Phase 0: Shared kernel loaded, TODO: Route to module`

---

### Phase 0.10: Documentation & Validation (Day 5)

**GOAL-0.10**: Complete documentation for Phase 0

#### Task 0.10.1: Update Architecture Docs
- **File**: Update `MODULAR_MONOLITH_ARCHITECTURE.md`
- **Sections to update**:
  - [ ] Shared kernel extraction complete
  - [ ] All DTOs consolidated
  - [ ] All entities modernized
  - [ ] Contracts defined
  - [ ] Container created

#### Task 0.10.2: Create Phase 0 Completion Checklist
- **File**: `PHASE_0_COMPLETION_CHECKLIST.md`
- **Items**:
  - [ ] All DTOs in `Shared/DTOs/` with new namespace
  - [ ] All entities in `Shared/Entities/` with new namespace
  - [ ] All imports updated
  - [ ] ServiceContainer implemented and tested
  - [ ] All interfaces in `Shared/Contracts/`
  - [ ] Configuration centralized
  - [ ] Exception hierarchy created
  - [ ] Unit tests ≥80% coverage
  - [ ] No syntax errors in any file
  - [ ] Bootstrap script works
  - [ ] Backward compatibility maintained (old imports still work)
  - [ ] Documentation complete
  - [ ] Code reviewed and approved

#### Task 0.10.3: Commit Phase 0

- **Changes to commit**:
  - All new files in `src/Ksfraser/FaBankImport/Shared/`
  - All tests in `tests/unit/Shared/`
  - Updated documentation files
  - src/bootstrap.php

- **Commit message template**:
  ```
  feat(shared-kernel): Phase 0 - Shared kernel foundation extraction
  
  - Create Shared/DTOs/ with consolidated DTO classes
  - Create Shared/Entities/ with modernized entity classes
  - Create Shared/Contracts/ with module boundary interfaces
  - Implement ServiceContainer for dependency injection
  - Create ModuleRegistry for module bootstrap
  - Centralize configuration in Config/BankImportConfig
  - Define exception hierarchy in Exceptions/
  - Write unit tests: 80%+ coverage
  - Update all imports to use Shared namespace
  - Maintain backward compatibility with old locations (temporary)
  
  Milestone: Shared kernel ready for module extraction phases
  Related: ADR-002, MODULAR_MONOLITH_ARCHITECTURE.md
  ```

- **Verification**: `git status` should be clean, commit hash recorded

#### Task 0.10.4: Code Review
- **Process**:
  1. Push to feature branch: `chore/phase-0-shared-kernel`
  2. Create PR with description of changes
  3. Run full test suite: `phpunit --colors=never --no-coverage`
  4. Code review by 1+ team members
  5. Approval and merge to main branch

- **Review checklist**:
  - [ ] All tests pass
  - [ ] No PHP syntax errors
  - [ ] No hardcoded secrets in Shared
  - [ ] Namespace structure correct
  - [ ] Backward compatibility maintained
  - [ ] Documentation complete and accurate

---

## 4. Deliverables

| Deliverable | Location | Status | Owner |
|---|---|---|---|
| Shared kernel directory structure | `src/Ksfraser/FaBankImport/Shared/` | TBD | Phase Lead |
| Consolidated DTO classes | `Shared/DTOs/*.php` | TBD | Phase Lead |
| Modernized entity classes | `Shared/Entities/*.php` | TBD | Phase Lead |
| Module boundary interfaces | `Shared/Contracts/*Interface.php` | TBD | Phase Lead |
| ServiceContainer implementation | `Shared/Container/ServiceContainer.php` | TBD | Phase Lead |
| Centralized configuration | `Shared/Config/BankImportConfig.php` | TBD | Phase Lead |
| Exception hierarchy | `Shared/Exceptions/*.php` | TBD | Phase Lead |
| Unit test suite | `tests/unit/Shared/` | TBD | QA Engineer |
| Bootstrap script | `src/bootstrap.php` | TBD | Phase Lead |
| Phase 0 completion checklist | `PHASE_0_COMPLETION_CHECKLIST.md` | TBD | Phase Lead |
| Updated architecture docs | `MODULAR_MONOLITH_ARCHITECTURE.md` | TBD | Phase Lead |
| Code review approval | PR Review | TBD | Code Reviewer |
| Git commit with all changes | Repository | TBD | Phase Lead |

---

## 5. Success Criteria

Phase 0 is considered **COMPLETE** when:

- [ ] **All classes created**: Every DTO, Entity, Interface, and container class created and in correct location
- [ ] **No syntax errors**: `php -l` on all PHP files in Shared kernel passes
- [ ] **Tests passing**: `phpunit tests/unit/Shared/ --colors=never --no-coverage` shows 100% pass rate
- [ ] **Coverage adequate**: ≥80% unit test coverage for Shared kernel
- [ ] **Imports updated**: All imports point to new `Shared\*` namespaces
- [ ] **Backward compatibility**: Old code still works (through deprecated imports if needed)
- [ ] **Bootstrap works**: `src/bootstrap.php` includes without errors
- [ ] **Documentation complete**: `MODULAR_MONOLITH_ARCHITECTURE.md` updated with Phase 0 completion status
- [ ] **Code reviewed**: PR approved by senior developer and merged to main branch
- [ ] **Ready for Phase 1**: No blockers for starting Process module extraction

---

## 6. Risk Assessment

### Risks

| Risk | Probability | Impact | Mitigation |
|---|---|---|---|
| Breaking existing imports | Medium | High | Keep old files, update imports gradually, extensive search/replace testing |
| Missing DTOs during consolidation | Medium | Medium | Thorough audit before consolidation, use grep to verify coverage |
| Circular dependencies in code | Low | High | Enforce via static analyzer (phpstan), code review |
| Incomplete Entity conversion | Medium | High | Document all legacy class fields/methods in audit phase |
| ServiceContainer too simple | Low | Medium | Design can be expanded if needed, initially MVP acceptable |
| Test coverage insufficient | Low | Medium | Write tests as code is written, review coverage before completion |

### Contingency Plans

- **If imports break**: Revert commit and use "translate" approach (new files alongside old until Phase 2)
- **If DTOs missing**: Run `grep -r "class.*DTO"` to find stragglers, add to Phase 0 extension
- **If SER Container insufficient**: Upgrade to Pimple or Symfony Container in Phase 1
- **If tests too slow**: Parallelize with `phpunit -d process=8`

---

## 7. Timeline & Scheduling

### Day-by-Day Breakdown

| Day | Tasks | Estimated Hours | Status |
|---|---|---|---|
| **Day 1** | 0.1 (Setup), 0.2.1 (Audit) | 8 | Planning |
| **Day 2** | 0.2.2-0.2.3 (Consolidate DTOs) | 8 | Planning |
| **Day 3** | 0.3.1-0.3.3 (Consolidate Entities) | 8 | Planning |
| **Day 4** | 0.4 (Contracts), 0.5 (Container), 0.6 (Config), 0.7 (Exceptions) | 8 | Planning |
| **Day 5** | 0.8 (Tests), 0.9 (Bootstrap), 0.10 (Docs) | 8 | Planning |
| **Buffer** | Additional testing, fixes, reviews | 4-8 | Planning |

**Total Effort**: ~40-48 hours (1 senior developer, 1 week)

### Critical Path

1. Audit phase must complete before consolidation starts (Day 1)
2. DTO consolidation unblocks Entity consolidation (Day 2 → Day 3)
3. Entities unblock Repository interface creation (Day 3 → Day 4)
4. Container + Config ready before tests (Day 4 → Day 5)
5. All classes ready before full test suite (Day 5)

---

## 8. Dependencies & Prerequisites

### Must be Done Before Phase 0

- [ ] Architecture decision (ADR-002) approved by team
- [ ] Feature branch created: `chore/phase-0-shared-kernel`
- [ ] PHP environment configured with required version (≥7.4)
- [ ] PHPUnit and tools installed
- [ ] Code review access configured

### External Dependencies

- None (Phase 0 has zero external module dependencies by design)

### Blocks Next Phase

- Phase 1 (Process module) requires Phase 0 ✓ successful completion
- Phase 2 (Import module) requires Phase 0 ✓ successful completion
- Phase 3 (Dedupe module) requires Phase 0 ✓ successful completion
- Phase 4 (Admin module) requires Phase 0 ✓ successful completion

---

## 9. Monitoring & Validation

### Daily Check-ins

- [ ] Day 1-2 PM: Audit complete, DTOs identified
- [ ] Day 2 PM: DTO consolidation 50% done
- [ ] Day 3 PM: Entities consolidated
- [ ] Day 4 PM: Interfaces and Container complete
- [ ] Day 5 PM: Tests passing, ready for review

### Validation Commands

```bash
# Check for syntax errors
find src/Ksfraser/FaBankImport/Shared -name "*.php" -exec php -l {} \;

# Run Shared kernel tests
vendor/bin/phpunit tests/unit/Shared/ --colors=never --no-coverage

# Check coverage
vendor/bin/phpunit tests/unit/Shared/ --coverage-text | grep "Lines"

# Verify namespace correctness
grep -r "^namespace Ksfraser" src/Ksfraser/FaBankImport/Shared/ | wc -l

# Find any remaining old imports
grep -r "use Model\|use.*DTO[^T]" src/ tests/ --include="*.php" | grep -v "Shared"
```

### Commit Hygiene

- All commits on feature branch `chore/phase-0-shared-kernel`
- Each commit is atomic and compilable
- All tests passing before commit
- Commit messages follow conventional commits format

---

## 10. Rollback Plan

### If Phase 0 Fails

1. **Revert commits**: `git reset --hard <before-phase-0>`
2. **Keep old approach**: Continue with ADR-001 (layered not modular) if modular approach proves problematic
3. **Document learnings**: Create `PHASE_0_LESSONS_LEARNED.md` explaining what went wrong
4. **Team review**: Discuss alternative approaches in team meeting

### If Phase 0 Partially Complete

If some tasks complete but others don't:
1. Keep completed sections (no rollback needed if isolated)
2. Extend Phase 0 by 2-3 days for remaining tasks
3. Push completion date back 1-2 weeks if needed
4. Don't start Phase 1 until Phase 0 100% complete

### Zero-Risk Approach

If risk deemed too high:
1. Keep Phase 0 as documentation/reference
2. Start with minimal Phase 1 changes
3. Create new modules gradually without full Shared consolidation
4. Revisit Shared consolidation later (technical debt)

---

## 11. Next Phases (Preview)

After Phase 0 completion, proceed to:

- **Phase 1**: Process module extraction (2 weeks)
  - Depends on: Shared kernel ✓
  - Lead: TBD
  
- **Phase 2**: Import module extraction (3 weeks)
  - Depends on: Shared kernel, Phase 1 learnings
  - Lead: TBD

See `MODULAR_MONOLITH_ARCHITECTURE.md` for full Phase 1-6 details.

---

## 12. Approvals & Sign-Off

| Role | Name | Sign-Off Date | Notes |
|---|---|---|---|
| Architecture Lead | TBD | TBD | Approved ADR-002? |
| Development Lead | TBD | TBD | Resource allocation? |
| QA Lead | TBD | TBD | Test strategy approved? |
| Product Owner | TBD | TBD | Business impact accepted? |

---

## 13. Appendix: File Inventory

### Files to Create (New)
```
src/Ksfraser/FaBankImport/Shared/
├── Contracts/
│   ├── DuplicateDetectionInterface.php
│   ├── TransactionProcessorInterface.php
│   ├── ImportHandlerInterface.php
│   ├── ModuleBootstrapInterface.php
│   └── [other interfaces]
│
├── DTOs/
│   ├── TransactionDTO.php
│   ├── StatementDTO.php
│   ├── ImportResultDTO.php
│   ├── ProcessResultDTO.php
│   ├── DuplicateCheckResultDTO.php
│   └── [consolidated DTOs]
│
├── Entities/
│   ├── BiTransaction.php
│   ├── BiStatement.php
│   ├── BiLineItem.php
│   ├── BankAccount.php
│   ├── Partner.php
│   └── [modernized entities]
│
├── Repositories/
│   ├── TransactionRepositoryInterface.php
│   ├── StatementRepositoryInterface.php
│   ├── BankAccountRepositoryInterface.php
│   └── PartnerRepositoryInterface.php
│
├── Container/
│   ├── ServiceContainer.php
│   └── ModuleRegistry.php
│
├── Config/
│   └── BankImportConfig.php
│
├── Exceptions/
│   ├── BankImportException.php
│   ├── ValidationException.php
│   ├── ConfigurationException.php
│   └── [exception hierarchy]
│
├── ValueObjects/
│   ├── Currency.php
│   ├── Amount.php
│   └── [value objects]
│
├── Traits/
│   ├── ValidatingTrait.php
│   └── LoggingTrait.php
│
└── README_SHARED_KERNEL.md

tests/unit/Shared/
├── DTOs/
│   ├── TransactionDTOTest.php
│   ├── StatementDTOTest.php
│   └── [DTO tests]
│
├── Entities/
│   ├── BiTransactionTest.php
│   ├── BiStatementTest.php
│   └── [entity tests]
│
├── Container/
│   └── ServiceContainerTest.php
│
├── Exceptions/
│   └── ExceptionHierarchyTest.php
│
└── Config/
    └── BankImportConfigTest.php

src/bootstrap.php
PHASE_0_COMPLETION_CHECKLIST.md
```

### Files to Update (Import Changes Only)
```
All existing files in:
- src/Ksfraser/FaBankImport/Process/
- src/Ksfraser/FaBankImport/Import/
- src/Ksfraser/FaBankImport/Dedupe/
- src/Ksfraser/FaBankImport/Admin/
- tests/

Changes: Update `use` statements to point to Shared\* namespaces
```

### Files to Keep (Backward Compatibility)
```
[Kept temporarily, marked as deprecated]
- class.bi_transaction.php (deprecated, use Shared\Entities\BiTransaction)
- class.bi_statement.php (deprecated)
- src/Model/*.php (deprecated, use Shared\DTOs\*)
- [other legacy locations]

Timeline: Remove in Phase 1 after all imports updated
```

---

**Document Status**: READY FOR IMPLEMENTATION  
**Last Updated**: 2026-03-28  
**Version**: 1.0  

Proceed to Phase 0 implementation upon team approval of this plan and ADR-002.
