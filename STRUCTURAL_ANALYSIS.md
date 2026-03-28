# KSF Bank Import - Module Structure Analysis

**Date:** March 28, 2026  
**Status:** Comprehensive structural analysis  
**Scope:** Physical file organization, logical groupings, dependencies, and growth patterns

---

## 1. PHYSICAL STRUCTURE OVERVIEW

### 1.1 Root-Level Entry Points & Controllers

#### Primary Entry Points (Page Handlers)
| File | Purpose | Lines | Status |
|------|---------|-------|--------|
| `process_statements.php` | Main transaction processing UI controller | ~563 | Procedural + class delegation |
| `import_statements.php` | File upload → parsing → import staging workflow | ~1,700 | Procedural with service classes |
| `admin_parsers.php` | Parser enable/disable admin interface | ~50 | Simple config UI |

#### Legacy Root PHP Classes (Pre-namespace)
| File | Purpose | Status |
|------|---------|--------|
| `class.bi_lineitem.php` | **[EMPTY]** - Legacy placeholder marking namespace migration |
| `class.bi_statements.php` | Legacy staging table model (bi_statements DB table) | Active |
| `class.bi_transaction.php` | Legacy single transaction staging model | Active (migrated patterns) |
| `class.bi_transactions.php` | Legacy collection model (bi_transactions DB table) | Active |
| `class.bi_bank_accounts.php` | Legacy FA bank account wrapper | Active |
| `class.bi_contact.php` | Legacy contact data model | Active |
| `class.bi_counterparty_model.php` | Legacy counterparty/partner model | Active |
| `class.bi_partners_data.php` | Legacy partner keyword/matching model | Active |
| `class.bi_transactionTitle_model.php` | Legacy transaction title extraction | Active |
| `class.bi_transaction.php` | Legacy transaction processing | Active |
| `class.bi_transfer_matches.php` | Legacy transfer pair matching model | Active |
| `class.bank_import_controller.php` | **PRIMARY CONTROLLER** - Routes transaction processing actions | Active + delegation |
| `class.transactions_table.php` | Legacy UI table renderer (replaced by Views) | Legacy |

#### Supporting Root Files
| File | Purpose |
|------|---------|
| `Handlers.php` | **[NOW SPLIT]** - Comment-only file pointing to handlers/ directory |
| `DTOs.php` | Legacy DTO stub (now in src/Ksfraser/FaBankImport/DTO/) |
| `config.php` | Module configuration (FA path resolution, logging) |
| `config.example.php` | Config template |

---

### 1.2 Directory Organization

```
ksf_bank_import/
├── root/                          ← LEGACY: Pre-namespace PHP classes & procedural pages
│   ├── process_statements.php     ← Entry point: transaction processing
│   ├── import_statements.php      ← Entry point: file import flow
│   ├── admin_parsers.php          ← Entry point: parser config
│   ├── class.*.php                ← 15+ legacy model classes (pending migration)
│   ├── Handlers.php               ← Metadata only (split into handlers/)
│   ├── config.php
│   ├── DTOs.php
│   └── [other support files]
│
├── src/Ksfraser/                  ← MODERN: Namespaced architecture
│   └── FaBankImport/
│       ├── Controllers/           ← Action routing
│       │   ├── BankImportController.php      (Main orchestrator)
│       │   └── ProcessStatementsController.php (Process flow)
│       ├── handlers/              ← Transaction action handlers (post-refactor)
│       │   ├── AbstractTransactionHandler.php
│       │   ├── CustomerTransactionHandler.php
│       │   ├── SupplierTransactionHandler.php
│       │   ├── QuickEntryTransactionHandler.php
│       │   ├── BankTransferTransactionHandler.php
│       │   ├── ManualSettlementHandler.php
│       │   ├── DuplicateResolutionHandler.php
│       │   ├── MatchedTransactionHandler.php
│       │   ├── AccountResolutionHandler.php
│       │   ├── ImportHandler.php  ← File import orchestration
│       │   ├── ParseFilesHandler.php
│       │   ├── UploadFormHandler.php
│       │   └── [other handlers]
│       │
│       ├── Service/               ← Domain services
│       │   ├── BankTransferAmountCalculator.php
│       │   ├── BankTransferFactory.php
│       │   ├── BankTransferFactoryInterface.php
│       │   ├── ExchangeRateService.php
│       │   ├── PairedTransferProcessor.php
│       │   ├── TransactionFilterService.php
│       │   ├── TransactionUpdater.php
│       │   ├── TransferDirectionAnalyzer.php
│       │   ├── TransferMatchAuditService.php
│       │   ├── TransferMatchService.php
│       │   ├── FileUploadService.php
│       │   ├── FileStorageService.php
│       │   ├── ImportRunLogger.php
│       │   ├── BankImportPathResolver.php
│       │   ├── DuplicateDetector.php
│       │   ├── AccountMappingResolver.php
│       │   ├── StatementAccountMappingService.php
│       │   └── [other services]
│       │
│       ├── Model/                 ← Namespaced models
│       │   └── BiLineItemModel.php
│       │
│       ├── DTO/                   ← Transfer objects
│       │   ├── BankingStatement.php
│       │   ├── BankingTransaction.php
│       │   ├── BiTransactionDto.php
│       │   ├── AccountResolutionDTO.php
│       │   ├── DuplicateResolutionDTO.php
│       │   ├── MappingConfirmationDTO.php
│       │   ├── ImportSummaryDTO.php
│       │   ├── UploadFormDTO.php
│       │   └── [other DTOs]
│       │
│       ├── Domain/                ← Aggregates + value objects
│       │   ├── Exceptions/        ← Custom exceptions
│       │   └── ValueObjects/      ← Value object implementations
│       │
│       ├── Dispatcher/            ← Action dispatch pattern
│       │   ├── ActionDispatcher.php
│       │   ├── ActionInterface.php
│       │   └── ActionRegistry.php
│       │
│       ├── Contracts/             ← Interfaces
│       │   ├── CommandInterface.php
│       │   └── CommandDispatcherInterface.php
│       │
│       ├── Traits/                ← Shared behavior
│       │   ├── [trait files]
│       │
│       ├── Application/           ← Bootstrap + container
│       │   ├── Application.php    ← Main entry point class
│       │   └── Bootstrap.php
│       │
│       ├── Container/             ← Dependency injection
│       │   └── Container.php
│       │
│       ├── Schema/                ← Database schema management
│       │   ├── [schema classes]
│       │
│       ├── Repository/            ← Data access
│       │   ├── [repository classes]
│       │
│       ├── Import/                ← Import workflow
│       │   ├── Orchestrators/     ← High-level coordinators
│       │   │   ├── BankImportOrchestrator.php
│       │   │   └── ProcessStatementsOrchestrator.php
│       │   ├── Services/          ← Low-level utilities
│       │   ├── Transactions/      ← Transaction wrappers
│       │   ├── Results/           ← Result objects
│       │   └── Queries/           ← Query builders
│       │
│       ├── View/                  ← View logic (legacy)
│       ├── Views/                 ← View classes (modern)
│       │   ├── [view classes]
│       │
│       ├── HTML/                  ← HTML utility classes
│       │   ├── Ajax/
│       │   ├── [other HTML utilities]
│       │
│       ├── FaBankImport/          ← [DUPLICATE STRUCTURE?]
│       │   ├── [mirror of above]
│       │
│       ├── FrontAccounting/       ← FA wrapper classes
│       ├── FA/                    ← FA helpers
│       │   ├── Auth/
│       │   ├── [other FA wrappers]
│       │
│       ├── PartnerTypes/          ← Strategy pattern: partner type handling
│       │   ├── [partner type strategies]
│       │
│       └── [various other packages]
│
├── Services/                      ← Root Services directory (parallel to src/)
│   ├── BankTransferAmountCalculator.php
│   ├── BankTransferFactory.php
│   ├── ExchangeRateService.php
│   ├── [other services - REDUNDANT with src/Ksfraser/FaBankImport/Service/]
│
├── views/                         ← View templates + components
│   ├── admin/                     ← Admin pages
│   │   ├── anomaly_report.php
│   │   ├── dashboard.php
│   │   ├── performance_report.php
│   ├── DataProviders/             ← Data provider strategies
│   │   ├── CustomerDataProvider.php
│   │   ├── SupplierDataProvider.php
│   │   ├── QuickEntryDataProvider.php
│   │   ├── PartnerDataProviderInterface.php
│   ├── transactions/              ← Transaction-related views
│   ├── errors/                    ← Error display
│   ├── AddVendorButton.php
│   ├── AddCustomerButton.php
│   ├── ViewFactory.php            ← Factory for view creation
│   ├── PartnerTypeDisplayStrategy.php
│   ├── MatchingGLS.php
│   ├── BankTransferPartnerTypeView.php
│   ├── CustomerPartnerTypeView.php
│   ├── SupplierPartnerTypeView.php
│   ├── QuickEntryPartnerTypeView.php
│   ├── TransactionTypeLabel.php
│   └── [other view components]
│
├── Views/ → views/                ← Symlink or file alias
│
├── Parsers/                       ← Bank statement parsers
│   ├── ro_bcr_csv/
│   ├── ro_brd_mt940/
│   ├── ro_ing_csv/
│   └── ro_wmmc_csv/
│
├── includes/                      ← Legacy procedural includes
│   ├── banking.php                ← Bank-specific functions
│   ├── parsers.inc                ← Parser registry
│   ├── includes.inc               ← Common includes
│   ├── pdata.inc                  ← Partner data includes
│   ├── qfx_parser.php
│   ├── ro_*_parser.php            ← Parser implementations
│   ├── parser.php                 ← Generic parser wrapper
│   └── [other includes]
│
├── OperationTypes/                ← Transaction operation types
│   ├── OperationTypeInterface.php
│   └── OperationTypesRegistry.php
│
├── tests/                         ← Unit + integration tests
│   ├── unit/                      ← Unit tests
│   ├── integration/               ← Integration tests
│   ├── functional/                ← Functional tests
│   ├── acceptance/                ← Acceptance tests
│   ├── feature/                   ← Feature tests
│   ├── performance/               ← Performance tests
│   ├── Entity/                    ← Test entities
│   ├── Service/                   ← Service tests
│   ├── Strategy/                  ← Strategy tests
│   ├── ValueObject/               ← Value object tests
│   ├── Views/                     ← View tests
│   ├── HTML/                      ← HTML tests
│   ├── bootstrap.php              ← Test bootstrap
│   ├── [test files]
│
├── cron/                          ← Background jobs
│   └── monitor_performance.php
│
├── docs/                          ← Documentation
│   ├── INTEGRATION_GUIDE.md
│   ├── ARCHITECTURE.md
│   ├── [other docs]
│
├── modules/                       ← [External dependency?]
├── ksf_modules_common/            ← Shared code with other modules
├── vendor/                        ← Composer packages
├── [numerous .md documentation files] ← Session notes, refactoring records
```

---

## 2. LOGICAL GROUPINGS: What Should Be Together

### 2.1 Current Groupings (by responsibility)

#### **GROUP A: Transaction Entry Points (User-facing)**
```
ROOT:
├── process_statements.php         → Main transaction processor view
├── import_statements.php          → File import view
└── admin_parsers.php              → Admin configuration view

CONTROLLERS:
├── src/Controllers/BankImportController.php
└── src/Controllers/ProcessStatementsController.php

HANDLERS (Action processing):
├── src/handlers/CustomerTransactionHandler.php
├── src/handlers/SupplierTransactionHandler.php
├── src/handlers/QuickEntryTransactionHandler.php
├── src/handlers/BankTransferTransactionHandler.php
├── src/handlers/ManualSettlementHandler.php
├── src/handlers/DuplicateResolutionHandler.php
├── src/handlers/MatchedTransactionHandler.php
└── [other handlers]

VIEWS (UI Components):
├── views/ViewFactory.php          ← Creates view objects by type
├── views/PartnerTypeDisplayStrategy.php
├── views/BankTransferPartnerTypeView.php
├── views/CustomerPartnerTypeView.php
├── views/SupplierPartnerTypeView.php
├── views/QuickEntryPartnerTypeView.php
└── [other view classes]
```
**STATUS:** Cohesive - UI flow is well-organized by transaction type

#### **GROUP B: File Import Pipeline**
```
ROOT:
├── import_statements.php          ← Entry point

PARSERS:
├── includes/parsers.inc           ← Parser registry
├── includes/qfx_parser.php        ← QFX format
├── includes/ro_*_parser.php       ← Romania bank formats
└── Parsers/                       ← Parser subdirectories

HANDLERS:
├── src/handlers/ImportHandler.php
├── src/handlers/ParseFilesHandler.php
└── src/handlers/UploadFormHandler.php

SERVICES:
├── Service/FileUploadService.php
├── Service/FileStorageService.php
├── Service/ImportRunLogger.php
└── Service/BankImportPathResolver.php

ORCHESTRATORS:
├── src/Import/Orchestrators/BankImportOrchestrator.php
└── src/Import/Orchestrators/ProcessStatementsOrchestrator.php

DTOs:
├── DTO/UploadFormDTO.php
├── DTO/BankingStatement.php
├── DTO/BankingTransaction.php
└── DTO/ParseFilesDTO.php
```
**STATUS:** Good organization - pipeline flow is clear

#### **GROUP C: Database Models & Persistence**
```
LEGACY MODELS (Root):
├── class.bi_statements.php        ← Statement staging table
├── class.bi_transaction.php       ← Single transaction
├── class.bi_transactions.php      ← Transactions collection
├── class.bi_bank_accounts.php     ← Bank accounts
├── class.bi_contact.php           ← Contacts
├── class.bi_counterparty_model.php ← Partners/counterparties
├── class.bi_partners_data.php     ← Partner matching keywords
└── [other model classes]

MODERN MODELS:
├── src/Model/BiLineItemModel.php  ← New namespaced model
├── src/Repository/               ← Data access layer
└── src/Db/                       ← Database wrappers

SCHEMA:
├── src/Schema/                    ← Database schema management
```
**STATUS:** Fragmented - Models split between legacy (root) and modern (src/), creating confusion

#### **GROUP D: Business Logic Services**
```
SERVICE DIRECTORY A (Root Services/):
├── BankTransferAmountCalculator.php
├── BankTransferFactory.php
├── ExchangeRateService.php
├── PairedTransferProcessor.php
├── TransactionFilterService.php
├── TransactionUpdater.php
├── TransferDirectionAnalyzer.php
├── TransferMatchAuditService.php
└── TransferMatchService.php

SERVICE DIRECTORY B (src/Service/):
├── BankTransferAmountCalculator.php   [DUPLICATE?]
├── BankTransferFactory.php            [DUPLICATE?]
├── ExchangeRateService.php            [DUPLICATE?]
├── FileUploadService.php
├── DuplicateDetector.php
├── AccountMappingResolver.php
├── StatementAccountMappingService.php
└── [other services]
```
**STATUS:** Problematic - Services in TWO locations (root vs. src/), causing redundancy/confusion

#### **GROUP E: Data Transfer Objects**
```
ROOT:
├── DTOs.php                       ← Legacy definition/stub

src/Ksfraser/FaBankImport/:
├── DTO/AbstractArrayDto.php
├── DTO/BankingStatement.php
├── DTO/BankingTransaction.php
├── DTO/BiTransactionDto.php
├── DTO/AccountResolutionDTO.php
├── DTO/DuplicateResolutionDTO.php
├── DTO/ImportSummaryDTO.php
├── DTO/MappingConfirmationDTO.php
├── DTO/UploadFormDTO.php
└── [other DTOs]
```
**STATUS:** Modern structure is good, legacy stub should be removed

#### **GROUP F: Testing**
```
tests/
├── unit/                 ← Unit tests
├── integration/          ← Integration tests
├── functional/           ← Functional tests
├── acceptance/           ← Acceptance tests
├── feature/              ← Feature tests
├── Entity/               ← Test objects
├── Service/              ← Service tests
├── Strategy/             ← Strategy pattern tests
├── ValueObject/          ← VO tests
└── [test helpers/bootstrap]
```
**STATUS:** Well-organized by test type

---

## 3. DEPENDENCY ANALYSIS

### 3.1 Critical Dependencies (Import Flow)

```
process_statements.php (User Entry)
    ↓
    class.bank_import_controller.php (Main Router)
    ↓ depends on ↓
    ├─ src/Controllers/BankImportController.php
    ├─ src/handlers/[TransactionHandler].php (by type: SP, CU, QE, BT, MA, ZZ)
    ├─ class.bi_transaction.php (Legacy model)
    ├─ class.bi_transactions.php (Legacy collection)
    └─ Service/*.php (Business logic)
        ├─ TransferMatchService.php
        ├─ TransactionFilterService.php
        ├─ TransactionUpdater.php
        └─ DuplicateDetector.php

import_statements.php (File Import Entry)
    ↓
    includes/parsers.inc (Parser registry)
    ↓ depends on ↓
    ├─ Parsers/[bank]/*.php (Format-specific parsers)
    ├─ includes/qfx_parser.php
    ├─ includes/ro_*.php
    ├─ src/handlers/ImportHandler.php
    ├─ src/Service/FileUploadService.php
    ├─ src/Service/ImportRunLogger.php
    └─ class.bi_statements.php (Staging model)
        ├─ class.bi_transaction.php (Single txn)
        ├─ class.bi_contact.php (Contact enrichment)
        └─ class.bi_partners_data.php (Partner matching)
```

### 3.2 Root-Level Dependencies

| File | Imports From | Imports Into |
|------|--------------|--------------|
| `process_statements.php` | `class.bank_import_controller.php`, FrontAccounting | Views |
| `import_statements.php` | `includes/parsers.inc`, `includes/qfx_parser.php`, `src/Ksfraser...` | Views |
| `class.bank_import_controller.php` | `class.bi_transaction.php`, `src/Controllers/...` | `process_statements.php` |
| `class.bi_statements.php` | FrontAccounting, `class.bi_transaction.php` | `import_statements.php`, handlers |
| `class.bi_transaction.php` | FrontAccounting, `class.bi_contact.php` | Multiple models |
| `class.bi_partners_data.php` | FrontAccounting, `class.bi_contact.php` | Matching logic |

### 3.3 Circular/Tangled Dependencies (RISK AREAS)

1. **bi_transaction ↔ bi_statements**
   - `bi_statements.php` uses `bi_transaction.php` methods
   - `bi_transaction.php` could reference `bi_statements.php`
   - **Impact:** Difficult to test in isolation

2. **class.bank_import_controller.php ↔ Legacy Models**
   - Controller depends heavily on legacy models
   - Models are not abstracted behind interfaces
   - **Impact:** Tight coupling; hard to swap implementations

3. **Services/ (root) vs src/Service/ (namespaced)**
   - Same service classes in two locations
   - Unclear which is authoritative
   - **Impact:** Maintenance burden; confusion about deprecation

4. **handlers/ vs Controllers/ **
   - Both handle transaction actions
   - Responsibility overlap unclear
   - **Impact:** Navigation difficulty; design confusion

---

## 4. COGNITIVE COMPLEXITY AREAS (Overlapping Concerns)

### 4.1 HIGH COMPLEXITY Zones

#### Zone 1: Transaction Processing (`process_statements.php` + `class.bank_import_controller.php`)
**Concerns:**
- UI rendering (filter forms, transaction tables)
- Transaction routing (by type: SP, CU, QE, BT, MA, ZZ)
- Data fetching (with complex filter logic)
- Business logic (matching, duplicate detection, amounts, GL posting)
- Error handling (validation, constraints, user feedback)

**Lines of Code:** ~800 combined  
**Cyclomatic Complexity:** High (multiple nested conditionals)  
**Testability:** Low (tightly coupled to UI, FrontAccounting)

#### Zone 2: File Import Pipeline (`import_statements.php`)
**Concerns:**
- File upload/storage management
- Parser selection and invocation
- Statement parsing (multiple formats: QFX, MT940, CSV)
- Statement staging (DB inserts)
- Transaction staging (DB inserts)
- Contact enrichment / partner matching
- Error handling and logging

**Lines of Code:** ~1,700  
**Cyclomatic Complexity:** High (nested loops with deep branching)  
**Testability:** Medium (some service extraction done)

#### Zone 3: Partner/Contact Resolution (`class.bi_contact.php` + `class.bi_partners_data.php` + `PartnerTypeDisplayStrategy.php`)
**Concerns:**
- Contact data enrichment (counters, titles)
- Partner type selection (SP, CU, QE)
- Keyword matching (scoring, multi-match resolution)
- UI rendering by partner type
- Strategy pattern implementation (different views per type)

**Lines of Code:** ~1,200 distributed  
**Cyclomatic Complexity:** Medium-High  
**Testability:** Medium (views are hard to test; logic could be extracted)

#### Zone 4: Duplicate Detection & Resolution
**Concerns:**
- Duplicate detection algorithms (amount, date, reference matching)
- Resolution workflow (user picks winner, reconciliation)
- State management (pending, resolved, rejected)
- UI interaction

**Files Involved:**
- `class.bi_transfer_matches.php`
- `src/handlers/DuplicateResolutionHandler.php`
- `Service/DuplicateDetector.php`
- `DTO/DuplicateResolutionDTO.php`

**Testability:** Low (state spread across session, DB, DTOs)

#### Zone 5: Bank Transfer Pair Matching (`TransferMatchService`, `TransferDirectionAnalyzer`, `BankTransferFactory`)
**Concerns:**
- Transaction pair matching (bank transfers)
- Direction analysis (expense vs. income)
- Amount calculations (with exchange rates)
- GL posting decisions
- Audit trail

**Files Involved:**
- `Service/TransferMatchService.php`
- `Service/TransferDirectionAnalyzer.php`
- `Service/BankTransferAmountCalculator.php`
- `src/handlers/BankTransferTransactionHandler.php`

**Testability:** Medium (services are reasonably isolated; handlers less so)

---

### 4.2 SEPARATION OF CONCERNS VIOLATIONS

| Violation | Location | Impact |
|-----------|----------|--------|
| **UI + Business Logic** | `process_statements.php` | Changes to UI require testing business logic; hard to extract business logic |
| **Database Access + Model Logic** | `class.bi_*.php` files | No clear abstraction layer; direct db_query() calls mixed with business rules |
| **Services Duplication** | `Services/` + `src/Service/` | Confusion about which is authoritative; potential sync issues |
| **View Logic in Controllers** | `process_statements.php` | HTML rendering directly in page file; not reusable |
| **Partner Type Handling** | Spread across Views, Handlers, Models | Business logic duplicated across multiple partner type implementations |
| **Error Handling** | Mixed patterns (try/catch, display_error(), exceptions) | Inconsistent error handling; hard to add centralized logging |
| **Logging + Result Tracking** | Multiple approaches (ImportRunLogger, display_notification, custom SQL) | Multiple systems competing; results not aggregated |
| **Configuration** | `config.php`, environment variables, class constants | Configuration scattered; no single source of truth |

---

## 5. GROWTH PATTERNS: Organic Evolution Evidence

### 5.1 File Naming Patterns Showing Evolution

#### Pattern 1: Legacy Class Naming (`class.*.php`)
```
ROOT:
class.bi_lineitem.php              ← Now EMPTY (migration marker)
class.bi_statements.php            ← Active (legacy)
class.bi_transaction.php           ← Active (legacy)
class.bi_transactions.php          ← Active (legacy)
class.bi_bank_accounts.php         ← Active (legacy)
...
```
**Interpretation:** WordPress/FrontAccounting convention (class prefix + underscore separator). These were the original module architecture. Now superseded by namespaced classes but still referenced in root entry points.

#### Pattern 2: Backup/Copy Files Indicating Uncertainty
```
class.bi_lineitem.php.20250607     ← Timestamped backup
class.bi_lineitem.php.corrupted    ← Corruption marker
class.bi_lineitem.php.prod         ← Production version
class.bi_lineitem.php~             ← Editor backup
...~                               ← Multiple editor backups
```
**Interpretation:** Multiple refactoring attempts; uncertainty about correctness; preservation of "known good" states.

#### Pattern 3: Refactored/Reference Files (Copilot-assisted)
```
class.bi_partners_data.copilot.php
class.bi_transaction.copilot.php
class.bi_transactions.copilot.php
process_statements.copilot_refactored.php
import_statements.copilot.php
```
**Interpretation:** AI-assisted refactoring experiments. Original + refactored version kept for comparison.

#### Pattern 4: Parallel Implementation Directories
```
Services/                     (Root-level services)
src/Ksfraser/FaBankImport/Service/   (Namespaced services)
src/Ksfraser/FaBankImport/Services/  (Services plural, symlink?)
```
**Interpretation:** Migration from root-level to namespaced organization; incomplete consolidation.

#### Pattern 5: View Implementation Evolution
```
views/
  └── ViewFactory.php              ← Modern factory pattern

src/Ksfraser/FaBankImport/
  ├── View/                        ← Legacy view namespace
  ├── Views/                       ← Modern view namespace
```
**Interpretation:** Gradual migration to strategy pattern for view rendering by transaction type.

#### Pattern 6: Handler Split (from monolithic file)
```
Handlers.php                 ← NOW JUST A COMMENT: "All handlers have been split into their own files"
    ↓ indicates split
src/handlers/
├── ActivityResolutionHandler.php
├── BankTransferTransactionHandler.php
├── CustomerTransactionHandler.php
├── SupplierTransactionHandler.php
...
```
**Interpretation:** Handlers were originally all in one file; extracted to separate files for clarity.

---

### 5.2 Documentation Growth Pattern (Session Memory)

Large collection of `.md` files shows iterative refinement:
```
PHASE Markers:
├── PHASE1_DUPLICATE_DETECTION_COMPLETE.md
├── PHASE1_PHASE2_COMPLETE.md
├── PHASE2_COMPLETE.md
├── PHASE4_INTEGRATION_COMPLETE.md

Refactoring Attempts:
├── REFACTORING_DETAILS.md
├── REFACTORING_NOTES.md
├── REFACTORING_SESSION_20251019_*.md
├── REFACTORING_PROGRESS.md

Analysis Documents (Investigation):
├── PROCESS_STATEMENTS_ANALYSIS.md         (~800 lines - comprehensive analysis)
├── IMPORT_STATEMENTS_ANALYSIS.md          (~700 lines - comprehensive analysis)
├── QE_MULTILINE_DUPLICATION_ANALYSIS.md
├── CRITICAL_ANALYSIS_QE_MULTILINE.md
├── PROD_VS_CURRENT_BUG_AUDIT.md

Decision Records:
├── ARCHITECTURAL_DECISION_FILE_ORGANIZATION.md
├── ARCHITECTURAL_VIOLATIONS_AUDIT.md
├── ARCHITECTURE_MIGRATION.md
```

**Interpretation:** Extensive analysis + planning happening in markdown; indicating systematic effort to understand then refactor. Tests and production deployment involved.

---

## 6. EXISTING DEPENDENCIES THAT MUST BE MAINTAINED

### 6.1 External Dependencies (Non-negotiable)

1. **FrontAccounting Framework**
   - `$path_to_root` configuration
   - `include_once($path_to_root . "/includes/session.inc")`
   - FA functions: `db_query()`, `db_insert_id()`, `display_error()`, `display_notification()`
   - FA UI: `page()`, `start_form()`, `end_form()`, `start_table()`, `submit_center()`
   - FA data models: Bank accounts, customers, suppliers, GL accounts

2. **FA Bank Import Module Structure**
   - Entry points: `process_statements.php`, `import_statements.php`, `admin_parsers.php`
   - Must be web-accessible via FA menu
   - Must respect `$page_security` permissions

3. **Database Tables** (Schema locked by existing deployments)
   ```
   bi_statements         ← Statement staging
   bi_transactions       ← Transaction staging
   bi_lineitem           ← Line item details
   bi_partners_data      ← Partner keywords
   bi_bank_accounts      ← Account mappings
   bi_uploaded_files     ← File tracking
   bi_transfer_matches   ← Paired transfer records
   ```

### 6.2 Internal Dependencies (Can Be Refactored But Must Maintain Interface)

1. **Controller Responsibility Chain**
   ```
   process_statements.php
       ↓
   class.bank_import_controller extends origin
       ↓
   $controller->processSupplierTransaction()
   $controller->processCustomerPayment()
   $controller->processTransactions()
   ```
   **Constraint:** These method signatures must remain stable

2. **Parser Registry Interface**
   ```
   include_once 'includes/parsers.inc'
       ↓
   $parser = ParserRegistry::get($format)
       ↓
   $statements = $parser->parse($content)
   ```
   **Constraint:** `parse()` method must return Statement objects with known structure

3. **Import Statement Flow**
   ```
   parse_uploaded_files() → Populate $_SESSION['statements']
       ↓
   import_statements() → Iterate and call importStatement()
       ↓
   importStatement($smt) → Create bi_statements_model, insert/update DB
   ```
   **Constraint:** Session structure, flow order, result format

4. **Transaction Handler Dispatch**
   ```
   $optypes = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ']
   switch($type):
       case 'SP': $controller->processSupplierTransaction()
       case 'CU': $controller->processCustomerPayment()
       case 'QE': $controller->processTransactions()
       ...
   ```
   **Constraint:** Handler method names, return values, state mutation

5. **Partner Type Strategy Pattern**
   ```
   views/PartnerTypeDisplayStrategy.php
       ↓
   Select view class by partner type (SP, CU, QE)
       ↓
   Views/BankTransferPartnerTypeView.php (etc.)
   ```
   **Constraint:** View factory interface, display method signature

---

## 7. SUMMARY: Restructure Recommendations

### 7.1 Current State Assessment

**STRENGTHS:**
- ✅ Modern namespace structure mostly in place (`src/Ksfraser/FaBankImport/`)
- ✅ Some services extracted and well-isolated (`TransferMatchService`, `DuplicateDetector`)
- ✅ Handlers separated by transaction type (clean separation)
- ✅ DTOs defined for inter-layer communication
- ✅ Test structure organized by test type

**WEAKNESSES:**
- ❌ **Dual Services locations** (root `Services/` + namespaced `src/Service/`) → confusion
- ❌ **Legacy models still primary** (root `class.*.php` used everywhere) → migration incomplete
- ❌ **Monolithic entry points** (`process_statements.php` ~563 lines, `import_statements.php` ~1700 lines) → hard to test/modify
- ❌ **No clear separation** between UI, business logic, data access in entry points → testing difficult
- ❌ **Partner type logic scattered** across Views, Models, Handlers → hard to modify one type
- ❌ **Circular imports** between models (`bi_transaction ↔ bi_statements`)

### 7.2 Consolidation Path

**Phase 1: Deduplicate Services**
1. Audit `Services/` vs `src/Service/` - identify duplicates
2. Keep authoritative in `src/Ksfraser/FaBankImport/Service/`
3. Delete root `Services/` directory
4. Update root entry points to import from namespaced location

**Phase 2: Decouple Entry Points from UI**
1. Extract business logic from `process_statements.php` into `ProcessStatementsOrchestrator`
2. Extract business logic from `import_statements.php` into `BankImportOrchestrator`
3. Entry points become thin controllers that call orchestrators
4. UI rendering delegated to view classes in `views/`

**Phase 3: Migrate Legacy Models**
1. Create new namespaced models in `src/Ksfraser/FaBankImport/Model/`
2. Add interfaces for each model
3. Update handlers/services to use interfaces
4. Deprecate legacy root `class.*.php` files (keep for compatibility, but add deprecation notices)

**Phase 4: Consolidate Partner Type Handling**
1. Create `src/Ksfraser/FaBankImport/PartnerTypes/PartnerTypeStrategy.php` interface
2. Implement concrete strategies for each type (SP, CU, QE, BT, MA, ZZ)
3. Each strategy encapsulates: handler, view, data provider, validation
4. Use factory pattern to select strategy by type

---

## 8. FILE ORGANIZATION MATRIX

| Category | Root | src/Ksfraser | includes/ | tests/ |
|----------|------|--------------|-----------|--------|
| **Controllers** | ⚠️ Procedural (process_statements.php) | ✅ OOP (Controllers/) | — | ✅ ControllerTests |
| **Models** | ⚠️ Legacy (class.*.php) | 🔄 Partial (Model/) | — | ✅ ModelTests |
| **Services** | ⚠️ Duplicate (Services/) | ✅ Primary (Service/) | — | ✅ ServiceTests |
| **Handlers** | — | ✅ (handlers/) | — | ✅ HandlerTests |
| **Views** | — | ✅ (View/, Views/) | — | ✅ ViewTests |
| **DTOs** | ⚠️ Legacy (DTOs.php) | ✅ (DTO/) | — | ✅ DTOTests |
| **Parsers** | — | — | ✅ (parsers.inc, qfx_parser.php) | 🔄 Partial |
| **Includes** | ⚠️ Procedural | — | ✅ Legacy | — |

---

## 9. DEPENDENCY GRAPH (Simplified)

```
process_statements.php ──┐
                        ├─→ class.bank_import_controller.php ──→ Handlers
import_statements.php ──┤
                        ├─→ includes/parsers.inc ──→ Parsers/ + qfx_parser.php
admin_parsers.php ──────┤
                        └─→ Services/ ──→ Models (class.bi_*.php)
                                      ├─→ DTOs/
                                      ├─→ DTO/ Orchestrators
                                      └─→ FrontAccounting APIs

MODERN LAYER (Namespace):
src/Ksfraser/FaBankImport/
  ├─ Controllers/ ────→ Handlers/ ────→ Services/ ────→ Repository/
  ├─ Views/ ────→ PartnerTypeDisplayStrategy
  ├─ Import/ ────→ Orchestrators
  ├─ DTO/ ────→ Data Transfer
  └─ Schema/ ────→ Database
```

---

## 10. KEY FINDINGS

1. **Architecture is HYBRID**: Modern namespaced code coexists with legacy procedural code. Not yet fully migrated.

2. **Entry Points are THIN**: `process_statements.php` and `import_statements.php` are designed as controllers but contain too much business logic.

3. **Models are FRAGMENTED**: Legacy models in root; modern models started in `src/` but incomplete.

4. **Service Layer is INCOMPLETE**: Services extracted but duplicated in two locations; unclear consolidation plan.

5. **Growth was PHASED**: Clear phases of development (Phase 1-4) with documentation; systematic approach to refactoring.

6. **Testing is STRUCTURED**: Tests organized by type (unit, integration, functional, acceptance); good separation of concerns.

7. **Partner Handling is COMPLEX**: Partner type logic spread across multiple view classes and handlers; high maintenance cost.

8. **Documentation is EXTENSIVE**: Detailed architectural analysis documents suggest careful planning; significant investment in understanding the problem.

---

**END OF ANALYSIS**

**Next Steps for Refactoring:**
1. Read [PROCESS_STATEMENTS_ANALYSIS.md](PROCESS_STATEMENTS_ANALYSIS.md) and [IMPORT_STATEMENTS_ANALYSIS.md](IMPORT_STATEMENTS_ANALYSIS.md) for detailed line-by-line breakdowns
2. Review [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for current target architecture
3. Execute Phase 1: Consolidate Services/ directory
4. Execute Phase 2: Thin out entry points using orchestrators
5. Execute Phase 3: Migrate legacy models to namespaced versions
