# FaBankImport Module Contracts & Boundaries

**Purpose**: Define the interfaces and data contracts that separate independent modules  
**Status**: Proposed Architecture  
**Date**: 2026-03-28  

---

## Contract Overview

Each module communicates ONLY through:
1. **Shared kernel interfaces** (`Shared\Contracts\`)
2. **Shared DTOs** (`Shared\DTOs\`)
3. **Shared entities** (`Shared\Entities\`)
4. **Dependency injection** (no direct instantiation)

This document defines what each module PROVIDES and what it DEPENDS ON.

---

## Module: Shared Kernel

### Responsibility
Provide stable, immutable data structures and interface contracts used by all modules.

### What It Contains
- **Contracts**: Interfaces that modules implement
- **DTOs**: Data transfer objects (read-only, serializable)
- **Entities**: Domain models (Business objects)
- **Value Objects**: Immutable value types
- **Exceptions**: Exception hierarchy
- **Container**: Dependency injection service container

### NO External Dependencies
- No dependencies on Process, Import, Dedupe, or Admin modules
- No dependencies except PHP standard library and FA framework

### Stability Guarantee
- Breaking changes require major version bump
- All modules updated before breaking change ships
- Deprecation warnings for 1 major version before removal

---

## Module: Process (Transaction Processing)

### Responsibility
Route transactions through business logic and update internal state (GL posting, partner records, etc.)

### What It Provides

#### ProcessStatementsController
```php
namespace Ksfraser\FaBankImport\Process\Controllers;

class ProcessStatementsController {
    public function dispatch(array $postData): void;
}
```

#### ActionDispatcher (via interface)
```php
namespace Ksfraser\FaBankImport\Process\Dispatcher;

interface ActionDispatcher {
    public function dispatch(array $postData): void;
}
```

### What It Depends On

#### From Shared
- `Shared\DTOs\TransactionDTO` - Input transactions
- `Shared\DTOs\ProcessResultDTO` - Output results
- `Shared\Contracts\TransactionProcessorInterface` - Implemented by this module
- `Shared\Repositories\TransactionRepositoryInterface` - Data access to transactions
- `Shared\Exceptions\ProcessingException` - For error handling

### Entry Points

#### HTTP (FA Module Convention)
```php
// process_statements.php (stub ~40 LOC)
POST /process_statements.php
  action=process_transaction    → ProcessTransactionAction
  action=unset_transaction      → UnsetTransactionAction
  action=toggle_transaction     → ToggleTransactionAction
  ...
```

### Internal Structure

```
Process/
├── Actions/
│   ├── ProcessTransactionAction      (~200 LOC)
│   ├── UnsetTransactionAction        (~50 LOC)
│   └── ToggleTransactionAction       (~50 LOC)
│
├── Dispatcher/
│   ├── ActionInterface
│   ├── ActionRegistry
│   └── ActionDispatcher
│
├── Controllers/
│   └── ProcessStatementsController
│
├── Services/
│   ├── ProcessOrchestrator
│   └── TransactionProcessor         (partners/types)
│
└── Processors/
    ├── SupplierProcessor
    ├── CustomerProcessor
    └── BankTransferProcessor
```

### Output Data

```php
class ProcessResultDTO {
    public string $transactionId;
    public string $status;              // "processed" | "error" | "pending"
    public ?string $errorMessage;
    public ?string $glRef;              // GL posting reference
    public array $metadata;             // timestamp, duration, etc.
}
```

### NOT Responsible For
- ❌ Transaction routing/matching to duplicate checks
- ❌ Import workflow management
- ❌ Parser configuration
- ❌ Duplicate detection (depends on Dedupe via interface)

---

## Module: Import (Import Pipeline)

### Responsibility
Orchestrate file upload → parse → validate → transform → stage statements for review

### What It Provides

#### ImportPipelineController
```php
namespace Ksfraser\FaBankImport\Import\Controllers;

class ImportPipelineController {
    public function handle(array $files, array $postData): void;
}
```

#### ImportOrchestrator
```php
namespace Ksfraser\FaBankImport\Import\Services;

class ImportOrchestrator {
    public function executeImport(ImportRequestDTO $request): ImportResultDTO;
}
```

### What It Depends On

#### From Shared
- `Shared\DTOs\StatementDTO` - Import file becomes statements
- `Shared\DTOs\ImportResultDTO` - Result of import
- `Shared\Contracts\DuplicateDetectionInterface` - Call Dedupe module during pipeline
- `Shared\Repositories\StatementRepositoryInterface` - Data access
- `Shared\Entities\BankAccount` - Which account to import into
- `Shared\Exceptions\ValidationException`, `ImportException` - Error handling

#### From Other Modules
- **Dedupe Module** (via `DuplicateDetectionInterface`)
  - Calls during "duplicate detection stage" of pipeline
  - Passes transactions for duplicate checking
  - Receives `DuplicateCheckResultDTO`

### Entry Points

#### HTTP (FA Module Convention)
```php
// import_statements.php (stub ~40 LOC)
POST /import_statements.php
  file=*.csv
  bank_account_id=123
  parser_type=csv
  
Workflow:
  1. FileUploadHandler     - Receive uploaded file
  2. ParserSelectionHandler - Choose parser (CSV/XLS/OFX)
  3. ValidationHandler     - Validate format
  4. TransformHandler      - Transform to BiStatement
  5. DuplicateDetectionHandler - Check for duplicates (calls Dedupe)
  6. ReviewStagerHandler   - Stage for manual review
```

### Internal Structure

```
Import/
├── Controllers/
│   └── ImportPipelineController
│
├── Handlers/                  (Pipeline stages)
│   ├── FileUploadHandler
│   ├── ParserSelectionHandler
│   ├── ValidationHandler
│   ├── TransformHandler
│   ├── DuplicateDetectionHandler    (calls Dedupe)
│   └── ReviewStagerHandler
│
├── Orchestrators/
│   └── ImportPipelineOrchestrator     (coordinates handlers)
│
├── Parsers/
│   ├── BankStatementParserInterface
│   ├── CSVBankStatementParser
│   ├── XLSBankStatementParser
│   └── ParserFactory
│
└── Services/
    ├── ImportService
    ├── ValidationService
    └── ProgressTracker
```

### Cross-Module Dependency: Dedupe

```php
// Import/Handlers/DuplicateDetectionHandler.php
class DuplicateDetectionHandler implements ImportHandlerInterface {
    public function __construct(
        private DuplicateDetectionInterface $duplicateDetector
    ) {}
    
    public function handle(ImportStateDTO $state): ImportStateDTO {
        // For each imported statement's transactions
        foreach ($state->transactions as $transaction) {
            $result = $this->duplicateDetector->detectDuplicates($transaction);
            
            if ($result->hasDuplicates()) {
                $state->flagForReview($transaction, $result);
            }
        }
        return $state;
    }
}

// Dedupe module implements the interface
// Registered in container by DedupeModuleBootstrap
```

### Output Data

```php
class ImportResultDTO {
    public string $importId;
    public string $status;                      // "pending_review" | "staged" | "error"
    public int $recordsProcessed;
    public int $recordsFlagged;                 // Potential duplicates
    public int $recordsErrored;
    public array $errors;                       // Validation errors
    public ?string $reviewQueueLink;
}
```

### NOT Responsible For
- ❌ Processing transactions (that's Process module)
- ❌ Detecting duplicates (that's Dedupe module)
- ❌ Admin configuration (that's Admin module)

---

## Module: Dedupe (Duplicate Detection)

### Responsibility
Detect potential duplicate transactions, provide audit trail, manage whitelist of acceptable pairs

### What It Provides

#### DuplicateReviewController
```php
namespace Ksfraser\FaBankImport\Dedupe\Controllers;

class DuplicateReviewController {
    public function reviewQueue(): void;
    public function auditPair(int $pairId): void;
}
```

#### DuplicateDetectionService (implements Shared interface)
```php
namespace Ksfraser\FaBankImport\Dedupe\Services;

use Ksfraser\FaBankImport\Shared\Contracts\DuplicateDetectionInterface;

class DuplicateDetectionService implements DuplicateDetectionInterface {
    public function detectDuplicates(TransactionDTO $tx): DuplicateCheckResultDTO;
    public function auditMatch(DuplicatePairDTO $pair, string $decision): AuditResultDTO;
}
```

### What It Depends On

#### From Shared ONLY
- `Shared\DTOs\TransactionDTO` - Input for detection
- `Shared\DTOs\DuplicateCheckResultDTO` - Output result
- `Shared\DTOs\DuplicatePairDTO` - A potential duplicate pair
- `Shared\Entities\BiTransaction` - Historical transaction to compare against
- `Shared\Repositories\TransactionRepositoryInterface` - Fetch historical transactions
- `Shared\Exceptions\DuplicateDetectionException` - Error handling

### Entry Points

#### HTTP (FA Module Convention)
```php
// transfer_match_review.php (stub ~40 LOC)
GET /transfer_match_review.php     → DuplicateReviewController::reviewQueue()
POST /transfer_match_review.php
  action=audit_pair
  pair_id=123
  decision=whitelist|reject
    → DuplicateReviewController::auditPair()
```

### Internal Structure

```
Dedupe/
├── Controllers/
│   └── DuplicateReviewController
│
├── Services/
│   ├── DirectCodeMatcher              (exact match algorithms)
│   ├── FuzzyMatcher                   (fuzzy match algorithms)
│   ├── DuplicateDetectionService      (orchestrator, implements interface)
│   ├── DuplicateRulesProvider         (rules engine)
│   └── TransferMatchAuditService      (audit trail)
│
├── Repositories/
│   └── DedupeRepository               (pairs, whitelist table)
│
└── Views/
    ├── DuplicateReviewView
    ├── DuplicatePairRowView
    └── AuditResultsView
```

### Matching Algorithms

#### DirectCodeMatcher
```php
// Exact match: same code, same amount from same bank
public function findDirectMatches(TransactionDTO $tx): array;
```

#### FuzzyMatcher
```php
// Similar: similar description, close amount, close date
public function findFuzzyMatches(TransactionDTO $tx): array;
```

#### DuplicateRulesProvider
```php
// Business rules: which matches are actually duplicates?
public function getRulesForPartner(Partner $partner): array;
```

### Output Data

```php
class DuplicateCheckResultDTO {
    public TransactionDTO $transaction;
    public array $matches;              // Potential duplicate BiTransactions
    public array $matchReasons;         // Why they matched (code/amount/date)
    public string $confidence;          // "high" | "medium" | "low"
    public bool $requiresManualReview;
}

class AuditResultDTO {
    public DuplicatePairDTO $pair;
    public string $decision;            // "whitelisted" | "rejected"
    public string $timestamp;
    public string $auditedBy;
}
```

### IMPORTANT: NO Dependencies on Other Modules
- ❌ Does NOT import from Process module
- ❌ Does NOT import from Import module
- ❌ Does NOT import from Admin module
- ✅ Only imports from Shared kernel

This is deliberate: Dedupe is the most independent. Other modules can depend on Dedupe, but not vice versa.

### NOT Responsible For
- ❌ Making final decision (that's human/Process)
- ❌ Creating transactions (that's Import)
- ❌ Updating GL (that's Process)

---

## Module: Admin (Configuration Management)

### Responsibility
Provide admin interfaces to configure parsers, duplicate rules, transfer rules, and system settings

### What It Provides

#### ParserAdminController
```php
namespace Ksfraser\FaBankImport\Admin\Controllers;

class ParserAdminController {
    public function configList(): void;         // List all parsers
    public function configEdit(int $id): void;  // Edit parser settings
    public function configSave(int $id): void;  // Save settings
}
```

#### RulesAdminController
```php
class RulesAdminController {
    public function rulesList(): void;
    public function ruleEdit(int $id): void;
    public function ruleSave(int $id): void;
}
```

### What It Depends On

#### From Shared
- `Shared\Config\BankImportConfig` - System configuration
- `Shared\Repositories\TransactionRepositoryInterface` - Query transactions
- `Shared\Entities\Partner` - Partner-specific config

#### From Other Modules (via interfaces, not directly)
- Can reference all modules to update their configuration
- But does so through configuration-focused interfaces, not domain logic

### Entry Points

#### HTTP (FA Module Convention)
```php
// admin_parsers.php (stub ~30 LOC)
GET /admin_parsers.php?action=list        → ParserAdminController::configList()
GET /admin_parsers.php?action=edit&id=1   → ParserAdminController::configEdit(1)
POST /admin_parsers.php                   → ParserAdminController::configSave()

// admin_transfer_rules.php (stub ~30 LOC)
GET /admin_transfer_rules.php             → RulesAdminController::rulesList()
POST /admin_transfer_rules.php            → RulesAdminController::ruleSave()
```

### Internal Structure

```
Admin/
├── Controllers/
│   ├── ParserAdminController
│   ├── RulesAdminController
│   └── ConfigurationController
│
├── Services/
│   ├── ParserRegistry
│   ├── TransferRulesManager
│   └── ConfigurationService
│
└── Views/
    ├── ParserConfigView
    ├── RulesConfigView
    └── SystemSettingsView
```

### NOT Responsible For
- ❌ Running parsers (that's Import)
- ❌ Processing transactions (that's Process)
- ❌ Detecting duplicates (that's Dedupe)

---

## Data Flow: Cross-Module Communication

### Scenario 1: User Uploads Bank Statement

```
Entry: import_statements.php
│
└─→ Import\Controllers\ImportPipelineController::handle()
    │
    ├─ (1) FileUploadHandler         - Validate file
    ├─ (2) ParserSelectionHandler    - Determine parser type
    ├─ (3) ValidationHandler         - Validate format
    ├─ (4) TransformHandler          - Create StatementDTO
    ├─ (5) DuplicateDetectionHandler
    │      │
    │      └─→ DuplicateDetectionInterface (injected)
    │         │
    │         └─→ Dedupe\Services\DuplicateDetectionService::detectDuplicates()
    │            (calls Dedupe, which depends only on Shared)
    │
    ├─ (6) ReviewStagerHandler       - Stage statements for review
    │
    └─ Output: ImportResultDTO → User sees review queue
```

### Scenario 2: User Reviews Transaction & Decides to Process

```
Entry: process_statements.php
│
└─→ Process\Controllers\ProcessStatementsController::dispatch()
    │
    ├─ (1) ActionDispatcher routes action to appropriate Action
    │
    ├─ (2) ProcessTransactionAction::execute()
    │      │
    │      ├─ Call ProcessOrchestrator with TransactionDTO
    │      │
    │      ├─ Route to correct Processor (Supplier/Customer/etc)
    │      │
    │      └─ Processor updates GL, updates Partner data
    │
    └─ Output: ProcessResultDTO → User sees success/error
```

### Scenario 3: User Manages Duplicate Whitelis

```
Entry: transfer_match_review.php
│
└─→ Dedupe\Controllers\DuplicateReviewController::auditPair()
    │
    ├─ (1) Load DuplicatePairDTO from database
    │
    ├─ (2) Call DuplicateDetectionService::auditMatch()
    │
    ├─ (3) Record whitelist/rejection in Dedupe\Repository
    │
    └─ Output: AuditResultDTO → User sees audit trail
```

### Data Model: No Coupling

```
         Shared Kernel (Contracts)
                  ▲
         ┌────────┼────────┬────────┐
         │        │        │        │
      Process  Import    Dedupe    Admin
         │        │        │        │
      (Uses)    (Uses)   (Uses)   (Uses)
         │        │        │        │
    Shared DTOs, Entities, Repositories
         ↑        ↑        ↑        ↑
    (Each module depends only on Shared + interfaces)
    
    ✅ No module depends on another module's concrete classes
    ✅ All inter-module communication via interfaces
    ✅ Easy to test each module in isolation
```

---

## Dependency Injection: How It Works

### Container Bootstrap (Root)

```php
// src/bootstrap.php
$container = new ServiceContainer();

// Shared kernel (always)
Shared\ModuleBootstrap::bootstrap($container);

// All modules
Process\ModuleBootstrap::bootstrap($container);
Import\ModuleBootstrap::bootstrap($container);
Dedupe\ModuleBootstrap::bootstrap($container);
Admin\ModuleBootstrap::bootstrap($container);
```

### Module Bootstrap: Dedupe Example

```php
// Dedupe/ModuleBootstrap.php
namespace Ksfraser\FaBankImport\Dedupe;

use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;
use Ksfraser\FaBankImport\Shared\Contracts\DuplicateDetectionInterface;

class ModuleBootstrap {
    public static function bootstrap(ServiceContainer $container): void {
        // Register Dedupe implementation of the interface
        $container->register(
            DuplicateDetectionInterface::class,
            fn($c) => new Services\DuplicateDetectionService(
                $c->get(Services\DirectCodeMatcher::class),
                $c->get(Services\FuzzyMatcher::class),
                $c->get(Services\DuplicateRulesProvider::class),
                $c->get(Repositories\DedupeRepository::class),
            )
        );
        
        // Register Dedupe-specific services
        $container->register(
            Services\DirectCodeMatcher::class,
            fn() => new Services\DirectCodeMatcher()
        );
        
        $container->register(
            Services\FuzzyMatcher::class,
            fn() => new Services\FuzzyMatcher()
        );
        
        $container->register(
            Controllers\DuplicateReviewController::class,
            fn($c) => new Controllers\DuplicateReviewController(
                $c->get(DuplicateDetectionInterface::class),
                $c->get(Repositories\DedupeRepository::class),
            )
        );
    }
}
```

### Module Bootstrap: Import Example

```php
// Import/ModuleBootstrap.php
namespace Ksfraser\FaBankImport\Import;

class ModuleBootstrap {
    public static function bootstrap(ServiceContainer $container): void {
        // Import depends on Dedupe's DuplicateDetectionInterface
        // which is already registered by Dedupe\ModuleBootstrap
        $container->register(
            Controllers\ImportPipelineController::class,
            fn($c) => new Controllers\ImportPipelineController(
                $c->get(Services\ImportOrchestrator::class),
            )
        );
        
        $container->register(
            Services\ImportOrchestrator::class,
            fn($c) => new Services\ImportOrchestrator(
                $c->get(Handlers\DuplicateDetectionHandler::class),
                // ... other handlers
            )
        );
        
        $container->register(
            Handlers\DuplicateDetectionHandler::class,
            fn($c) => new Handlers\DuplicateDetectionHandler(
                $c->get(DuplicateDetectionInterface::class)  // ← Injected from Dedupe
            )
        );
    }
}
```

**KEY POINT**: Import doesn't directly import `Dedupe\Services\DuplicateDetectionService`. It only knows about the interface (`DuplicateDetectionInterface`). The container wires the implementation.

---

## Rules for Module Development

### DO ✅

- ✅ Import from `Shared\*` anywhere
- ✅ Import from your own module (`Process\`, `Import\`, etc.)
- ✅ Depend on interfaces from `Shared\Contracts\`
- ✅ Inject dependencies via constructor (not `$_SESSION` or globals)
- ✅ Use DTOs to pass data between modules
- ✅ Return DTOs from Module Controllers
- ✅ Write tests in `tests/unit/ModuleName/`

### DON'T ❌

- ❌ Import concrete classes from other modules
  ```php
  // WRONG - direct coupling
  use Dedupe\Services\DuplicateDetectionService;
  
  $detector = new DuplicateDetectionService();
  ```

- ❌ Pass module-specific objects to other modules
  ```php
  // WRONG - tight coupling via data structure
  return new Dedupe\DTOs\DuplicateCheckResultDTO(...);
  ```

- ❌ Share module-specific Views
  ```php
  // WRONG - view code shouldn't cross modules
  echo new Dedupe\Views\DuplicateResultView(...);
  ```

- ❌ Create circular dependencies
  ```php
  // WRONG - Process depends on Dedupe, Dedupe depends on Process
  ```

---

## Validation Checklist for New Features

Before adding a new feature to a module:

- [ ] Does it fit within module responsibility?
- [ ] Does it use only Shared interfaces/DTOs for cross-module data?
- [ ] Can module tests run independently?
- [ ] Is dependency injection used, not static calls?
- [ ] Did you write unit tests?
- [ ] Did integration tests pass?
- [ ] Does it compile without errors from other modules?

---

## Scaling from Monolith to Packages

When ready to extract module to separate Composer package:

1. **Create Package Directory**
   ```
   git remote add faba-process git@github.com:org/faba-process.git
   git subtree split --prefix=src/Ksfraser/FaBankImport/Process -b faba-process-branch
   git push faba-process faba-process-branch:main
   ```

2. **Create composer.json**
   ```json
   {
       "name": "faba/process",
       "require": {
           "faba/shared-kernel": "^1.0"
       }
   }
   ```

3. **Update Namespace** (optional)
   ```php
   // From:
   namespace Ksfraser\FaBankImport\Process;
   
   // To (when separate package):
   namespace FaProcess;
   ```

4. **Root Project Updated**
   ```json
   {
       "require": {
           "faba/process": "^1.0",
           "faba/import": "^2.0",
           "faba/dedupe": "^1.5",
           "faba/admin": "^1.0"
       }
   }
   ```

5. **No Code Changes Needed** ✅
   - Module code works as-is
   - Interfaces unchanged
   - DTOs unchanged
   - Tests unchanged

---

## References

- ADR-002: Modular Monolith Architecture Decision
- MODULAR_MONOLITH_ARCHITECTURE.md: Full architecture docs
- Shared Kernel README: `src/Ksfraser/FaBankImport/Shared/README_SHARED_KERNEL.md`
