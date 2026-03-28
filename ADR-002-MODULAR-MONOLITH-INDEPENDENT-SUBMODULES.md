---
title: "ADR-002: Modular Monolith with Independent Submodule Architecture"
status: "Proposed"
date: "2026-03-28"
authors: "Architecture Review Team"
tags: ["architecture", "modular", "independent-deployment", "namespace", "shared-kernel"]
supersedes: "ADR-001-RESTRUCTURE-INTO-SUBMODULES"
superseded_by: ""
---

# ADR-002: Modular Monolith with Independent Submodule Architecture

## Status

**Proposed** — This decision builds upon ADR-001 and evolves it toward greater independence while maintaining shared kernel patterns.

## Context

### Current Challenge

The previous restructuring (ADR-001) proposed 4 submodules but assumed monolithic deployment:
- All code in single repository
- Shared entry points
- No clear contracts between modules
- Difficult for teams to work independently
- Required coordinated releases

### Business Drivers

- **Team Autonomy**: Enable different teams to develop submodules independently
- **Deployment Flexibility**: Eventually allow individual modules to be versioned/released separately
- **Reusability**: Modules should be consumable as separate packages (Composer)
- **Scalability**: Support growing teams without constant coordination
- **Testing Isolation**: Each module testable in isolation without entire system

### Technical Constraints

- Must remain backward compatible with FrontAccounting legacy system
- Namespace structure must be preserved for FA integration
- Shared kernel (DTOs, exceptions, config) must be coherent and stable
- PHP/Composer ecosystem compatibility
- Existing test infrastructure and CI/CD must work

## Decision

**Adopt a Shared Kernel Modular Monolith architecture** with the following characteristics:

### 1. **Namespace Structure**
```
Ksfraser\FaBankImport\
├── Shared/                    ← Shared kernel (DTOs, models, exceptions)
├── Process\                   ← Transaction processing module (independent)
├── Import\                    ← Import pipeline module (independent)
├── Dedupe\                    ← Duplicate detection module (independent)
└── Admin\                     ← Administration module (independent)
```

### 2. **Module Independence**

Each submodule MUST:

**IND-001**: Have its own folder structure with Controllers, Services, Views
- Allows each module to be extracted to separate Git repository/Composer package
- Example: `src/Ksfraser/FaBankImport/Process/` → can become `git@github.com:org/faba-process.git`

**IND-002**: Define strict interface contracts
- All cross-module dependencies go through shared kernel interfaces
- Modules don't directly instantiate other module classes
- Example: `Process` module doesn't reference `Import\*` classes directly

**IND-003**: Have its own entry point controller
- `ProcessStatementsController` for Process module
- `ImportPipelineController` for Import module
- Makes module logic explicit and testable in isolation

**IND-004**: Have its own test suite folder
- `tests/Process/` for Process tests
- Can run in isolation: `phpunit --testsuite Process`
- No dependencies on other module tests

**IND-005**: Have its own composer.json (future)
- Currently one root composer.json
- Eventually: Each module can have composer.json specifying its own dependencies
- Shared kernel always required: `require: "faba/shared-kernel"`

### 3. **Shared Kernel**

Shared kernel houses ONLY:

**KER-001**: Data structures (DTOs, Entities)
- `BiTransaction`, `BiLineItem`, `BiStatement`
- `TransactionDTO`, `StatementDTO`
- Shared among all modules

**KER-002**: Value Objects
- `BankAccount`, `Partner`, `Currency`
- Immutable, used across modules

**KER-003**: Interfaces & Contracts
- `TransactionProcessorInterface` (Process module implements)
- `ImportHandlerInterface` (Import module implements)
- `DuplicateDetectionInterface` (Dedupe module implements)

**KER-004**: Exceptions
- `BankImportException`, `ValidationException`
- Shared exception hierarchy

**KER-005**: Configuration
- `BankImportConfig` with centralized settings
- Environment-based configuration

**KER-006**: Repository interfaces
- `TransactionRepositoryInterface`
- `StatementRepositoryInterface`
- Concrete implementations might be in modules or shared

### 4. **Dependency Injection & Module Coupling**

**DEP-001**: All inter-module communication through interfaces
```php
// ❌ WRONG - direct coupling
class ImportPipelineHandler {
    public function detectDuplicates($transaction) {
        $detector = new Dedupe\Services\DuplicateDetectionService();
    }
}

// ✅ CORRECT - interface-based loosely coupled
class ImportPipelineHandler {
    private $duplicateDetector;
    
    public function __construct(DuplicateDetectionInterface $detector) {
        $this->duplicateDetector = $detector;
    }
}
```

**DEP-002**: Registry pattern for module discovery
```php
// Central registry in shared kernel
$registry = new ModuleRegistry();
$registry->register('Process', ProcessModule::class);
$registry->register('Import', ImportModule::class);
$registry->register('Dedupe', DedupeModule::class);

// Modules register their services
ProcessModule::registerServices($container);
ImportModule::registerServices($container);
```

**DEP-003**: No circular dependencies
- `Process` can depend on `Shared` ✅
- `Import` can depend on `Shared` and `Dedupe` ✅
- `Dedupe` cannot depend on `Process` or `Import` ❌

### 5. **Module Composition**

Each module (`Process`, `Import`, `Dedupe`, `Admin`) contains:

```
Module/
├── Controllers/
│   └── ModuleController.php         (entry point for module)
│
├── Services/
│   ├── ModuleOrchestrator.php       (internal orchestration)
│   └── ModuleService.php             (business logic)
│
├── Actions/                          (if pattern applies)
│   └── ActionClassName.php
│
├── Handlers/                         (if pattern applies)
│   └── HandlerClassName.php
│
├── Repositories/
│   └── ModuleRepository.php          (data access specific to module)
│
├── Views/
│   └── ModuleView.php                (view rendering)
│
├── Traits/
│   └── ModuleValidationTrait.php     (module-specific validation)
│
├── Exceptions/
│   └── ModuleException.php           (module-specific exceptions)
│
└── README_MODULE.md                  (module documentation)
```

### 6. **Transition Path to Independent Repositories**

**PHV-001**: Year 1 - Shared repository with modular structure
- All code in single repo
- Clear namespace/folder boundaries
- Each module independently testable

**PHV-002**: Year 2 - Separate Composer packages
- Extract each module to separate Composer package
- Published to Packagist
- Root project requires packages: `require: "faba/process" "faba/import" "faba/dedupe"`

**PHV-003**: Year 3+ - Separate GitHub repositories
- Each module in own GitHub repo
- Versioned independently
- Can be developed/released independently
- Shared kernel versioned separately

## Consequences

### Positive

- **POS-001**: Teams can work independently without coordination hotspots
- **POS-002**: Each module testable in isolation with clear boundaries
- **POS-003**: Clear interface contracts prevent unintended coupling
- **POS-004**: Easy to extract module to separate repo/package later
- **POS-005**: Failed module can be redeployed independently (future)
- **POS-006**: Supports microservices evolution if needed later
- **POS-007**: Onboarding easier: new dev learns 1 module at a time
- **POS-008**: Code reviews become module-focused (smaller scope)
- **POS-009**: Can apply different testing strategies per module
- **POS-010**: Dependency injection forces explicit module boundaries

### Negative

- **NEG-001**: Shared kernel must be very stable (breaking changes require coordination)
- **NEG-002**: More complex dependency management than single module
- **NEG-003**: Requires discipline to maintain interface contracts
- **NEG-004**: Module interdependencies harder to trace (implicit through interfaces)
- **NEG-005**: Testing across modules requires integration test suite
- **NEG-006**: Increased boilerplate: each module needs bootstrap/config
- **NEG-007**: Transition period has duplicate code (old structure + new)
- **NEG-008**: Risk of "module god problem" if modules have too many responsibilities

## Alternatives Considered

### Alt-1: True Microservices

- **ALT-001**: **Description**: Each module is independent HTTP service with own database
- **ALT-002**: **Rejection Reason**: 
  - Adds network latency and complexity
  - FrontAccounting integration becomes difficult
  - Overkill for current scale
  - Data consistency much harder
  - Proposal is "too much, too soon"

### Alt-2: Monolithic with no clear boundaries

- **ALT-003**: **Description**: Keep current mixed structure, just improve documentation
- **ALT-004**: **Rejection Reason**:
  - Doesn't solve cognitive complexity problem
  - Can't support team independence
  - Coupling remains, preventing future scaling
  - Hard to extract to packages later

### Alt-3: Keep ADR-001 approach (strictly layered, single module)

- **ALT-005**: **Description**: Restructure into layers (entry → controllers → services → models) but no module boundaries
- **ALT-006**: **Rejection Reason**:
  - Doesn't support team independence
  - Can't extract modules to separate repos
  - Scaling to multiple teams will require re-architecture
  - ADR-002 is just-slightly-more-complex but much more future-proof

## Implementation Notes

### IMP-001: Namespace & Folder Alignment

Map namespace to folder structure precisely:
```
Namespace: Ksfraser\FaBankImport\Process\Controllers\ProcessStatementsController
File: src/Ksfraser/FaBankImport/Process/Controllers/ProcessStatementsController.php
```

### IMP-002: Shared Kernel Stability

Shared kernel versioning critical:
- Use semantic versioning strictly: `major.minor.patch`
- Document stable interfaces in shared kernel
- Break changes only with major version bump
- Notify all module teams of upcoming breaks

### IMP-003: Module Bootstrap

Each module needs bootstrap entry point:
```php
// Process/Controllers/ProcessStatementsController.php
namespace Ksfraser\FaBankImport\Process\Controllers;

use Ksfraser\FaBankImport\Shared\Contracts\ModuleBootstrap;

class ProcessStatementsController implements ModuleBootstrap {
    public static function bootstrap(ContainerInterface $container) {
        // Register module services
    }
}
```

### IMP-004: Interface Definition Priority

Before Phase 0 implementation:
1. Define all interfaces in shared kernel
2. Document what each module provides
3. Document what each module depends on
4. Validate no circular dependencies

### IMP-005: Entry Point Unification

Single root entry point (`process_statements.php`) routes to module controllers:
```php
// process_statements.php (stub - ~20 LOC)
$controller = new ProcessStatementsController($container);
$controller->dispatch($_POST);
```

### IMP-006: Testing Strategy Per Module

Each module has own test suite:
```bash
# Run Process module tests only
phpunit --testsuite Process

# Run Integration tests (cross-module)
phpunit --testsuite Integration

# Run all
phpunit
```

## Migration Strategy

### Phase 0: Prepare Shared Kernel (1 week)
- Define all interfaces
- Create interface contracts
- Move DTOs to shared kernel
- Document stability guarantees

### Phase 1: Process Module Independence (2 weeks)
- Extract to `src/Ksfraser/FaBankImport/Process/`
- Create ProcessModule bootstrap
- Dependency injection for all services
- All imports from Shared::
- Own test suite

### Phase 2: Import Module Independence (3 weeks)
- Extract to `src/Ksfraser/FaBankImport/Import/`
- CreateImportModule bootstrap
- Dependency on Shared and DuplicateDetectionInterface
- Own test suite

### Phase 3: Dedupe Module Independence (2 weeks)
- Extract to `src/Ksfraser/FaBankImport/Dedupe/`
- CreateDedupeModule bootstrap
- No dependencies on other modules
- Own test suite

### Phase 4: Admin Module Independence (1 week)
- Extract to `src/Ksfraser/FaBankImport/Admin/`
- CreateAdminModule bootstrap
- Can reference all modules through interfaces
- Own test suite

### Phase 5: Composer Packages (2 weeks)
- Create separate composer.json for each module
- Publish as Composer packages
- Root project requires all packages

### Phase 6: GitHub Repositories (ongoing)
- Create separate GitHub repos (optional)
- Each module developed independently
- Shared kernel in separate "core" repo

## Success Criteria

- **SUC-001**: Each module has <100 external dependencies (currently ~300 in monolith)
- **SUC-002**: Module tests run in <10 seconds independently
- **SUC-003**: Cross-module integration tests run in <20 seconds
- **SUC-004**: New module developer understands module boundary in <1 hour
- **SUC-005**: Add new feature can be done entirely within 1 module 80% of time
- **SUC-006**: No circular dependencies detected by static analyzer
- **SUC-007**: All interface contracts documented and stable
- **SUC-008**: Each module ≥80% test coverage
- **SUC-009**: Module extraction to separate package possible without changes

## Monitoring & Rollback

### Monitoring

- **MON-001**: Track module coupling metrics (external deps per module)
- **MON-002**: Monitor test suite runtime per module
- **MON-003**: Track feature delivery time per module
- **MON-004**: Measure onboarding time for new developers

### Rollback

If modular approach proves problematic:
1. Reverse to ADR-001 (layered, single module)
2. Keep extracted code but ignore module boundaries
3. Consolidate namespaces back to pre-module structure
4. Take ~1 week to revert

Risk of rollback: **LOW** — each phase is contained and reversible

## References

- **REF-001**: ADR-001 - Restructure Into Submodules (baseline architecture)
- **REF-002**: [Modular Monolith Pattern](https://www.nginx.com/blog/what-is-modular-monolith/)
- **REF-003**: [Shared Kernel Pattern - Domain-Driven Design](https://martinfowler.com/bliki/BoundedContext.html)
- **REF-004**: [PHP Namespace Best Practices](https://www.php-fig.org/psr/psr-4/)
- **REF-005**: ARCHITECTURAL_BLUEPRINT.md - Previous restructuring plan
- **REF-006**: DISPATCHER_REFACTORING_COMPLETE.md - Action dispatcher pattern

---

## Decision Timeline

| Date | Status | Notes |
|------|--------|-------|
| 2026-03-28 | **PROPOSED** | Initial design proposal |
| TBD | Review | Stakeholder review & feedback |
| TBD | Accepted | Team decision to proceed |
| TBD | Implementation | Phase 0-6 execution begins |

---

## Related Issues & PRs

- Will link to GitHub issues when created
- Will link to feature branch: `feat/modular-monolith-refactoring`
- Will link to implementation plan issue
