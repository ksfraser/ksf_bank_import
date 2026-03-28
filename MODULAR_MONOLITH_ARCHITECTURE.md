# KSF Bank Import Module - Modular Monolith Architecture

**Status**: Proposed Architecture (Evolution of ADR-001)  
**Version**: 2.0  
**Date**: 2026-03-28  
**Decision**: ADR-002 - Modular Monolith with Independent Submodules  

---

## Executive Summary

This update evolves the restructuring proposal to enable each submodule to be independently developed, tested, and eventually deployed as a separate GitHub repository or Composer package.

**Key Change**: Instead of layered submodules, we adopt a **Shared Kernel Modular Monolith** pattern:
- Each submodule is self-contained with no direct cross-module dependencies
- All inter-module communication through shared kernel interfaces
- Namespace structure supports future extraction to separate repos
- Clear entry points per module allow independent testing and deployment

**Benefits**:
- ✅ Team independence: Different teams develop modules simultaneously
- ✅ Scalability: Can extract module to separate repo without code changes
- ✅ Testing isolation: Run module tests independently
- ✅ Future-proof: Enables microservices evolution if needed
- ✅ Deployment flexibility: Eventually version/release modules independently

---

## New Namespace & Folder Structure

### Level 1: Namespace Root
```
Ksfraser\FaBankImport\
├── Shared/                  ← Shared Kernel (language=DTOs, models, contracts)
├── Process\                 ← Process module (transaction processing)
├── Import\                  ← Import module (file upload → statement import)
├── Dedupe\                  ← Dedupe module (duplicate detection & review)
└── Admin\                   ← Admin module (configuration management)
```

### Level 2: Folder Structure (Physical)
```
src/Ksfraser/FaBankImport/
│
├── Shared/                           ← SHARED KERNEL (stable, used by all)
│   ├── Contracts/                    (interfaces, defines module boundaries)
│   │   ├── DuplicateDetectionInterface.php
│   │   ├── TransactionProcessorInterface.php
│   │   ├── ImportHandlerInterface.php
│   │   └── ModuleBootstrapInterface.php
│   │
│   ├── DTOs/                         (data transfer objects)
│   │   ├── TransactionDTO.php
│   │   ├── StatementDTO.php
│   │   ├── ImportResultDTO.php
│   │   ├── DuplicateCheckResultDTO.php
│   │   └── [other DTOs]
│   │
│   ├── Entities/                     (domain models - legacy class.*.php replacements)
│   │   ├── BiTransaction.php
│   │   ├── BiLineItem.php
│   │   ├── BiStatement.php
│   │   ├── BankAccount.php
│   │   └── Partner.php
│   │
│   ├── ValueObjects/                 (immutable value objects)
│   │   ├── Currency.php
│   │   ├── BankCode.php
│   │   ├── Amount.php
│   │   └── DateRange.php
│   │
│   ├── Repositories/                 (repository interfaces)
│   │   ├── TransactionRepositoryInterface.php
│   │   ├── StatementRepositoryInterface.php
│   │   ├── BankAccountRepositoryInterface.php
│   │   └── [other repository interfaces]
│   │
│   ├── Exceptions/                   (exception hierarchy)
│   │   ├── BankImportException.php
│   │   ├── ValidationException.php
│   │   ├── ConfigurationException.php
│   │   └── [specific exceptions]
│   │
│   ├── Config/                       (centralized configuration)
│   │   ├── BankImportConfig.php
│   │   └── FeatureFlags.php
│   │
│   ├── Traits/                       (shared validation, logging traits)
│   │   ├── ValidatingTrait.php
│   │   └── LoggingTrait.php
│   │
│   ├── Container/                    (dependency injection)
│   │   ├── ServiceContainer.php
│   │   └── ModuleRegistry.php
│   │
│   └── README_SHARED_KERNEL.md       (contract documentation)
│
├── Process/                          ← PROCESS MODULE (independent)
│   ├── Controllers/
│   │   └── ProcessStatementsController.php    (module entry point ~40 LOC)
│   │
│   ├── Actions/
│   │   ├── ProcessTransactionAction.php
│   │   ├── UnsetTransactionAction.php
│   │   ├── ToggleTransactionAction.php
│   │   └── [other actions]
│   │
│   ├── Dispatcher/
│   │   ├── ActionInterface.php
│   │   ├── ActionRegistry.php
│   │   ├── ActionDispatcher.php
│   │   └── ActionRegistrar.php
│   │
│   ├── Services/
│   │   ├── ProcessOrchestrator.php    (module orchestration)
│   │   ├── TransactionProcessor.php
│   │   ├── PartnerProcessor.php
│   │   └── ProcessingService.php
│   │
│   ├── Processors/                   (partner-type specific)
│   │   ├── SupplierProcessor.php
│   │   ├── CustomerProcessor.php
│   │   ├── BankTransferProcessor.php
│   │   └── QuickEntryProcessor.php
│   │
│   ├── Repositories/
│   │   └── ProcessRepository.php      (process-specific data access)
│   │
│   ├── Views/
│   │   ├── ProcessingQueueView.php
│   │   ├── TransactionDetailView.php
│   │   └── ProcessingResultView.php
│   │
│   ├── Exceptions/
│   │   └── ProcessingException.php    (process-specific exceptions)
│   │
│   ├── ModuleBootstrap.php            (dependency registration → Shared\Container)
│   ├── README_PROCESS.md              (module documentation)
│   └── composer.json.template         (for future extraction as package)
│
├── Import/                           ← IMPORT MODULE (independent)
│   ├── Controllers/
│   │   └── ImportPipelineController.php    (module entry point ~40 LOC)
│   │
│   ├── Handlers/
│   │   ├── FileUploadHandler.php
│   │   ├── ParserSelectionHandler.php
│   │   ├── ValidationHandler.php
│   │   ├── TransformHandler.php
│   │   ├── DuplicateDetectionHandler.php     (calls Dedupe via interface)
│   │   └── ReviewStagerHandler.php
│   │
│   ├── Orchestrators/
│   │   └── ImportPipeline Orchestrator.php   (orchestrates workflow)
│   │
│   ├── Parsers/
│   │   ├── BankStatementParserInterface.php  (defines parser contract)
│   │   ├── CSVStatementParser.php
│   │   ├── XLSStatementParser.php
│   │   ├── OFXStatementParser.php
│   │   └── ParserFactory.php
│   │
│   ├── Services/
│   │   ├── ImportService.php
│   │   ├── ValidationService.php
│   │   ├── ImportProgressTracker.php
│   │   └── ParserFactory.php
│   │
│   ├── Repositories/
│   │   └── ImportRepository.php
│   │
│   ├── Views/
│   │   ├── ImportPipelineView.php
│   │   ├── FileUploadForm.php
│   │   ├── ValidationResultsView.php
│   │   └── ReviewQueueView.php
│   │
│   ├── Exceptions/
│   │   ├── ImportException.php
│   │   ├── ParserException.php
│   │   └── ValidationException.php
│   │
│   ├── ModuleBootstrap.php
│   ├── README_IMPORT.md
│   └── composer.json.template
│
├── Dedupe/                           ← DEDUPE MODULE (independent, NO dependencies on others)
│   ├── Controllers/
│   │   └── DuplicateReviewController.php    (module entry point ~40 LOC)
│   │
│   ├── Services/
│   │   ├── DirectCodeMatcher.php       (exact transaction matching)
│   │   ├── FuzzyMatcher.php            (fuzzy transaction matching)
│   │   ├── DuplicateDetectionService.php    (orchestrator)
│   │   ├── DuplicateRulesProvider.php       (rules engine)
│   │   ├── TransferMatchAuditService.php    (audit trail)
│   │   └── DedupeService.php
│   │
│   ├── Repositories/
│   │   └── DedupeRepository.php
│   │
│   ├── DTOs/
│   │   ├── DuplicateCheckResultDTO.php
│   │   ├── DuplicatePairDTO.php
│   │   └── AuditResultDTO.php
│   │
│   ├── Handlers/
│   │   └── DuplicateReviewHandler.php
│   │
│   ├── Views/
│   │   ├── DuplicateReviewView.php
│   │   ├── DuplicatePairRowView.php
│   │   ├── AuditResultsView.php
│   │   └── TransferMatchView.php
│   │
│   ├── Exceptions/
│   │   └── DuplicateDetectionException.php
│   │
│   ├── ModuleBootstrap.php
│   ├── README_DEDUPE.md
│   └── composer.json.template
│
├── Admin/                            ← ADMIN MODULE (independent)
│   ├── Controllers/
│   │   ├── ParserAdminController.php         (module entry point)
│   │   ├── TransferRulesController.php       (module entry point)
│   │   └── ConfigurationController.php
│   │
│   ├── Services/
│   │   ├── ParserRegistry.php
│   │   ├── TransferRulesManager.php
│   │   └── ConfigurationService.php
│   │
│   ├── Repositories/
│   │   └── AdminRepository.php
│   │
│   ├── Views/
│   │   ├── ParserConfigView.php
│   │   ├── RulesConfigView.php
│   │   └── SystemSettingsView.php
│   │
│   ├── Exceptions/
│   │   └── AdminException.php
│   │
│   ├── ModuleBootstrap.php
│   ├── README_ADMIN.md
│   └── composer.json.template
│
└── README_STRUCTURE.md               (this documentation)

tests/
├── unit/
│   ├── Shared/                       (shared kernel tests - run always)
│   │   └── [DTOs, entities, value objects]
│   │
│   ├── Process/                      (run independently: phpunit --testsuite Process)
│   │   ├── Actions/
│   │   ├── Services/
│   │   └── Processors/
│   │
│   ├── Import/                       (run independently: phpunit --testsuite Import)
│   │   ├── Handlers/
│   │   ├── Services/
│   │   └── Parsers/
│   │
│   ├── Dedupe/                       (run independently: phpunit --testsuite Dedupe)
│   │   ├── Services/
│   │   └── Handlers/
│   │
│   └── Admin/                        (run independently: phpunit --testsuite Admin)
│       └── Services/
│
├── integration/
│   ├── ImportToProcessFlow/          (Import → Process workflow)
│   ├── ProcessToDupeFlow/            (Process → Dedupe duplicate detection)
│   ├── FullPipelineFlow/             (Upload → Import → Process → Dedupe)
│   └── AdminConfigurationFlow/       (Admin config updates affect modules)
│
└── fixtures/
    └── [shared test data]

docs/
├── ARCHITECTURAL_BLUEPRINT.md        (this file - architecture overview)
├── ADR-001-RESTRUCTURE-INTO-SUBMODULES.md      (baseline)
├── ADR-002-MODULAR-MONOLITH-INDEPENDENT-SUBMODULES.md    (this evolution)
├── CONTRACT_SPECIFICATIONS.md        (interface contracts between modules)
├── MODULE_DEVELOPMENT_GUIDE.md       (how to develop within a module)
├── MODULE_EXTRACTION_PROCESS.md      (from repo → separate package → separate repo)
├── DEPENDENCY_INJECTION_GUIDE.md     (DI best practices)
├── TESTING_STRATEGY.md               (per-module and integration testing)
├── DEPLOYMENT_GUIDE.md               (how to deploy individual modules)
└── CONTRIBUTING.md

config/
└── bank_import.config.php
```

---

## Module Independence Model

### What Makes Each Module Independent?

#### 1. **Encapsulation**
Each module contains all code needed for its business domain:
```
Dedupe contains:
✅ All matching algorithms
✅ All review views
✅ All duplicate DTOs
✅ All exceptions specific to duplicates
✅ Configuration for duplicate rules
```

#### 2. **Clear Boundaries**
No module directly instantiates another module's classes:
```php
// ❌ WRONG - tight coupling
class DuplicateDetectionHandler extends Import\AbstractHandler {
    public function detectDuplicates() {
        $detector = new Dedupe\Services\DuplicateDetectionService();
    }
}

// ✅ CORRECT - loose coupling via interface
class DuplicateDetectionHandler extends Import\AbstractHandler {
    private DuplicateDetectionInterface $detector;
    
    public function __construct(DuplicateDetectionInterface $detector) {
        $this->detector = $detector;
    }
}
```

#### 3. **Interface-Based Dependencies**
All cross-module dependencies defined in Shared\Contracts:
```php
// Shared/Contracts/DuplicateDetectionInterface.php
namespace Ksfraser\FaBankImport\Shared\Contracts;

interface DuplicateDetectionInterface {
    public function detectDuplicates(TransactionDTO $transaction): DuplicateCheckResultDTO;
    public function auditMatch(DuplicatePairDTO $pair): AuditResultDTO;
}

// Dedupe/Services/DuplicateDetectionService.php
namespace Ksfraser\FaBankImport\Dedupe\Services;

use Ksfraser\FaBankImport\Shared\Contracts\DuplicateDetectionInterface;

class DuplicateDetectionService implements DuplicateDetectionInterface {
    // Implementation
}

// Import/Handlers/DuplicateDetectionHandler.php
namespace Ksfraser\FaBankImport\Import\Handlers;

use Ksfraser\FaBankImport\Shared\Contracts\DuplicateDetectionInterface;

class DuplicateDetectionHandler {
    public function __construct(private DuplicateDetectionInterface $detector) {}
    
    public function handle() {
        $result = $this->detector->detectDuplicates($transaction);
    }
}
```

#### 4. **Dependency Injection**
Services registered in ModuleBootstrap, injected via container:
```php
// Process/ModuleBootstrap.php
namespace Ksfraser\FaBankImport\Process;

class ModuleBootstrap implements ModuleBootstrapInterface {
    public static function bootstrap(ServiceContainer $container) {
        $container->register(
            ActionRegistry::class,
            fn() => new ActionRegistry()
        );
        
        $container->register(
            ProcessOrchestrator::class,
            fn($c) => new ProcessOrchestrator(
                $c->get(ActionDispatcher::class),
                $c->get(TransactionRepositoryInterface::class)
            )
        );
    }
}

// Root bootstrap
$container = new ServiceContainer();
Process\ModuleBootstrap::bootstrap($container);
Import\ModuleBootstrap::bootstrap($container);
Dedupe\ModuleBootstrap::bootstrap($container);
Admin\ModuleBootstrap::bootstrap($container);
```

### Module Dependency Graph (Allowed)

```
Shared Kernel (Foundation - No dependencies except PHP stdlib)
    ↑
    ├──── Process Module       (depends only on Shared)
    ├──── Import Module        (depends on Shared, Dedupe interface)
    ├──── Dedupe Module        (depends only on Shared)
    └──── Admin Module         (depends on Shared, references other modules via interfaces)

FORBIDDEN:
    ❌ Process → Import (direct import of Import classes)
    ❌ Import → Process (direct import of Process classes)
    ❌ Dedupe → Import (direct import of Import classes)
    ❌ Circular dependencies (A → B → A)

ALLOWED:
    ✅ Import → Dedupe (via DuplicateDetectionInterface)
    ✅ Process → Shared (direct, always allowed)
    ✅ All modules → Shared (direct, always allowed)
    ✅ Admin → Any Module (via interfaces, for configuration)
```

---

## Module Entry Points

Each module has a thin CLI-like entry point that delegates to controller:

### `process_statements.php` (Root entry point - ~40 LOC)
```php
<?php
use Ksfraser\FaBankImport\Process\Controllers\ProcessStatementsController;
use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;

// Bootstrap all modules
$container = new ServiceContainer();
require_once 'src/bootstrap.php';  // Calls all ModuleBootstrap::bootstrap()

// Delegate to Process module
$controller = $container->get(ProcessStatementsController::class);
$controller->dispatch($_POST);
```

### `import_statements.php` (Root entry point - ~40 LOC)
```php
<?php
use Ksfraser\FaBankImport\Import\Controllers\ImportPipelineController;
use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;

$container = new ServiceContainer();
require_once 'src/bootstrap.php';

$controller = $container->get(ImportPipelineController::class);
$controller->handle($_FILES, $_POST);
```

---

## Scaling Path: From Module → Package → Repository

### Stage 1: Modular Monolith (Today)
```
Single Repository
└── ksf_bank_import/
    ├── src/Ksfraser/FaBankImport/
    │   ├── Shared/
    │   ├── Process/
    │   ├── Import/
    │   ├── Dedupe/
    │   └── Admin/
    └── tests/
```

### Stage 2: Composer Packages (Year 2)
Extract each module to separate Composer package:
```
Separate Packages on Packagist
├── faba/shared-kernel          (Shared/ as package)
├── faba/process                 (Process/ as package)
├── faba/import                  (Import/ as package)
├── faba/dedupe                  (Dedupe/ as package)
└── faba/admin                   (Admin/ as package)

Root composer.json requires:
{
    "require": {
        "faba/shared-kernel": "^1.0",
        "faba/process": "^1.0",
        "faba/import": "^2.0",
        "faba/dedupe": "^1.5",
        "faba/admin": "^1.0"
    }
}
```

### Stage 3: Independent GitHub Repos (Year 3+)
Each package in its own GitHub repository:
```
GitHub Organization: github.com/faba
├── github.com/faba/shared-kernel    (source for Packagist package)
├── github.com/faba/process          (source for Packagist package)
├── github.com/faba/import           (source for Packagist package)
├── github.com/faba/dedupe           (source for Packagist package)
├── github.com/faba/admin            (source for Packagist package)
└── github.com/faba/core             (orchestrates packages)
```

### NO CODE CHANGES NEEDED FOR EXTRACTION
Because of strict module boundaries, extracting module to separate repo requires:
- Move folder to new repo
- Adjust namespace (maybe: `FaProcess\` instead of `FaBankImport\Process\`)
- Update composer.json
- **NO changes to module code itself**

---

## Shared Kernel Contracts

The interfaces defined in `Shared\Contracts\` form the contracts between modules:

### DuplicateDetectionInterface
```php
namespace Ksfraser\FaBankImport\Shared\Contracts;

interface DuplicateDetectionInterface {
    /**
     * Detect if transaction might be a duplicate
     * 
     * @param TransactionDTO $transaction
     * @return DuplicateCheckResultDTO with matches or empty
     */
    public function detectDuplicates(TransactionDTO $transaction): DuplicateCheckResultDTO;
    
    /**
     * Audit a duplicate pair (whitelist or reject)
     */
    public function auditMatch(DuplicatePairDTO $pair, string $decision): AuditResultDTO;
}
```

### TransactionProcessorInterface
```php
namespace Ksfraser\FaBankImport\Shared\Contracts;

interface TransactionProcessorInterface {
    /**
     * Process a single transaction through business logic
     * 
     * @param TransactionDTO $transaction
     * @param ProcessContextDTO $context
     * @return ProcessResultDTO
     */
    public function processTransaction(
        TransactionDTO $transaction,
        ProcessContextDTO $context
    ): ProcessResultDTO;
}
```

### ImportHandlerInterface
```php
namespace Ksfraser\FaBankImport\Shared\Contracts;

interface ImportHandlerInterface {
    /**
     * Each import handler processes one stage of import pipeline
     */
    public function handle(ImportStateDTO $state): ImportStateDTO;
}
```

---

## Benefits of Modular Monolith Approach

### For Development Teams
| Benefit | Impact |
|---------|--------|
| **Team Independence** | Teams develop Process, Import, Dedupe in parallel |
| **Faster Iterations** | Changes stay within module, no coordination needed |
| **Clear Ownership** | One team owns one module, no ambiguity |
| **Isolated Testing** | Module tests pass independently |
| **Faster Feedback** | Run only relevant tests: `phpunit --testsuite Dedupe` |

### For Code Quality
| Benefit | Impact |
|---------|--------|
| **Enforced Boundaries** | Dependency injection prevents sneaking around interfaces |
| **Lower Coupling** | Average 3-5 external dependencies per module vs 30+ today |
| **Higher Cohesion** | Related code stays together in module |
| **Easier Refactoring** | Refactor one module, test in isolation |
| **Clear Responsibilities** | Each module has clear business domain |

### For Operations
| Benefit | Impact |
|---------|--------|
| **Selective Deployment** | Fix bug in Dedupe, deploy only that module eventually |
| **Version Independence** | Import v2.0 compatible with Process v1.5 |
| **Scalability** | Teams grow by adding team per module, not monolithic org |
| **Easier Monitoring** | Monitor metrics per module separately |
| **Incident Response** | Bug in Dedupe doesn't require Process redeploy |

### For Future
| Benefit | Impact |
|---------|--------|
| **Microservices** | If needed, extract module to HTTP service without code change |
| **Event-Driven** | Can add event bus later without code changes |
| **Distributed** | Can scale specific modules independently if needed |

---

## Dependency Injection Container

Central container in `Shared\Container\ServiceContainer`:

```php
namespace Ksfraser\FaBankImport\Shared\Container;

class ServiceContainer {
    private array $services = [];
    private array $singletons = [];
    
    public function register(string $abstract, callable $concrete): void {
        $this->services[$abstract] = $concrete;
    }
    
    public function singleton(string $abstract, callable $concrete): void {
        if (!isset($this->singletons[$abstract])) {
            $this->singletons[$abstract] = $concrete($this);
        }
        return;
    }
    
    public function get(string $abstract) {
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }
        return $this->services[$abstract]($this);
    }
}
```

Each module bootstraps its services:

```php
// Process/ModuleBootstrap.php
class ProcessModuleBootstrap {
    public static function bootstrap(ServiceContainer $container): void {
        // Register interfaces to implementations
        $container->register(
            DuplicateDetectionInterface::class,
            fn($c) => $c->get(Dedupe\Services\DuplicateDetectionService::class)
        );
        
        // Register module-specific services
        $container->register(
            ProcessOrchestrator::class,
            fn($c) => new ProcessOrchestrator(
                $c->get(ActionDispatcher::class),
                $c->get(TransactionRepositoryInterface::class)
            )
        );
    }
}
```

---

## Testing Strategy

### Unit Tests (Per Module)
```bash
# Run only Process module tests
phpunit --testsuite Process

# Run only Import module tests
phpunit --testsuite Import

# Run Dedupe module tests (fastest - no cross-module dependencies)
phpunit --testsuite Dedupe

# Run Shared kernel tests (always run, affects all)
phpunit --testsuite Shared
```

### Integration Tests (Cross-Module)
```bash
# Run workflows that cross module boundaries
phpunit --testsuite Integration

# Examples:
# - ImportPipeline → DuplicateDetection (Import calls Dedupe)
# - ImportPipeline → ProcessStatements → DuplicateDetection
# - AdminConfiguration → affects all modules
```

### Module Test Structure
```
tests/unit/Process/
├── Actions/
│   ├── ProcessTransactionActionTest.php
│   ├── UnsetTransactionActionTest.php
│   └── ToggleTransactionActionTest.php
│
├── Services/
│   ├── ProcessOrchestratorTest.php
│   └── TransactionProcessorTest.php
│
└── Processors/
    ├── SupplierProcessorTest.php
    └── CustomerProcessorTest.php
```

Each test file can run independently:
```bash
phpunit tests/unit/Process/Services/ProcessOrchestratorTest.php
```

---

## Implementation Roadmap

### Phase 0: Shared Kernel Foundation (1 week)
1. Create `Shared/` folder structure
2. Move DTOs to `Shared/DTOs/`
3. Move Entities to `Shared/Entities/`
4. Create interfaces in `Shared/Contracts/`
5. Create `ServiceContainer` in `Shared/Container/`
6. Define module contracts (what each module provides/depends on)

### Phase 1: Process Module Extraction (2 weeks)
1. Create `Process/` folder structure
2. Move all action classes to `Process/Actions/`
3. Extract `ProcessStatementsController`
4. Create `ProcessModuleBootstrap`
5. Implement dependency injection
6. Test in isolation: `phpunit --testsuite Process`

### Phase 2: Import Module Extraction (3 weeks)
1. Create `Import/` folder structure
2. Move handlers, parsers, services to `Import/`
3. Extract `ImportPipelineController`
4. Create `ImportModuleBootstrap`
5. Wire Dedupe interface dependency
6. Test in isolation: `phpunit --testsuite Import`

### Phase 3: Dedupe Module Extraction (2 weeks)
1. Create `Dedupe/` folder structure
2. Move services to `Dedupe/`
3. Extract `DuplicateReviewController`
4. Create `DedupeModuleBootstrap`
5. Implement `DuplicateDetectionInterface`
6. Test in isolation: `phpunit --testsuite Dedupe`

### Phase 4: Admin Module Extraction (1 week)
1. Create `Admin/` folder structure
2. Consolidate admin controllers and services
3. Extract admin entry points
4. Create `AdminModuleBootstrap`

### Phase 5: Refactor Root Entry Points (1 week)
1. Update `process_statements.php` to delegate to controller (~40 LOC)
2. Update `import_statements.php` to delegate to controller (~40 LOC)
3. Create root `bootstrap.php` that calls all ModuleBootstrap::bootstrap()
4. Verify all integration tests pass

### Phase 6: Documentation & Migration (1 week)
1. Document module contracts
2. Create per-module README files
3. Document how to add new features to each module
4. Create module extraction guide

### Phase 7: Composer Packages (Future)
1. Create separate composer.json for each module
2. Publish to Packagist
3. Root project requires all packages

### Phase 8: GitHub Repositories (Future)
1. Create separate GitHub repo for each module
2. Update CI/CD to build independent repos
3. Version modules independently

---

##Success Criteria

- [ ] Each module has ≤100 external dependencies (down from 300+ today)
- [ ] Module tests run in <10 seconds independently
- [ ] Cross-module integration tests run in <20 seconds
- [ ] Developer can add new feature entirely within 1 module 85% of time
- [ ] No circular dependencies detected by static analyzer
- [ ] Each module ≥80% unit test coverage
- [ ] Can extract module to separate repo without code changes
- [ ] New developer understands module boundary in <1 hour

---

## Related Documentation

- [ADR-002: Modular Monolith Decision](ADR-002-MODULAR-MONOLITH-INDEPENDENT-SUBMODULES.md)
- [ADR-001: Initial Restructuring](ADR-001-RESTRUCTURE-INTO-SUBMODULES.md)
- [CONTRACT_SPECIFICATIONS.md](CONTRACT_SPECIFICATIONS.md) (interface contracts)
- [MODULE_DEVELOPMENT_GUIDE.md](MODULE_DEVELOPMENT_GUIDE.md)
- [MODULE_EXTRACTION_PROCESS.md](MODULE_EXTRACTION_PROCESS.md)
