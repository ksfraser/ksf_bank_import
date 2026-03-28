# KSF Bank Import Module - Architectural Restructuring Blueprint

**Status**: Proposed Architecture  
**Date**: 2024  
**Current Pain Point**: Organic growth has created cognitive complexity, duplicated components, and scattered concerns  

---

## Executive Summary

The `ksf_bank_import` module has grown to ~7,000 LOC over multiple phases, creating **four distinct logical domains** that are currently **intertwined in the root namespace**. 

**Current State**: 
- ❌ Models/DTOs split between legacy (`class.*.php`) and modern (`src/Model/`)
- ❌ Services duplicated in `Services/` root vs `src/Service/`
- ❌ Business logic mixed with view logic in entry point controllers
- ❌ No clear separation between import pipeline, transaction processing, and admin concerns

**Proposed State**:
- ✅ Clear submodule boundaries with independent responsibilities
- ✅ Shared infrastructure layer for database, models, DTOs
- ✅ Four standalone submodules that can be tested and updated independently
- ✅ Reduced cognitive load per submodule (~1000-1500 LOC each vs 5000+ mixed)

---

## Current Architecture

### Physical Structure (Today)
```
ksf_bank_import/
├── Root Entry Points (Mixed Concerns)
│   ├── process_statements.php (563 LOC) - TX processing + session UI
│   ├── import_statements.php (1,700 LOC) - Import workflow + validation  
│   ├── admin_parsers.php - Parser config admin
│   └── class.bank_import_controller.php - Main router
│
├── Legacy Models/Classes (Procedural)
│   ├── class.bi_*.php (7 files) - Transaction, account, lineitem, etc.
│   ├── class.transactions_table.php
│   └── class.bi_partners_data.php
│
├── Modern OO Code (Mixed Locations)
│   ├── src/Ksfraser/FaBankImport/ - Main namespace
│   ├── src/Ksfraser/Models/ - Modern DTOs
│   ├── src/Model/ - Duplicate location
│   └── Views/ - Strategy pattern views
│
├── Services Layer (DUPLICATED) ⚠️
│   ├── Services/*.php (Root level)
│   └── src/Ksfraser/Service/*.php (Duplicate)
│
├── Import Pipeline
│   ├── Handlers/ - 8+ handler classes
│   ├── Services/ - Transform & orchestration
│   └── Views/ - Import UI rendering
│
└── Transaction Processing
    ├── Actions/ - Dispatcher actions (Phase 2-4)
    ├── Dispatcher/ - Action routing
    └── TransactionProcessors/ - Partner-type handlers
```

### Logical Domains (Intertwined Today)
| Domain | Current Home | LOC | Problem |
|--------|-------------|-----|---------|
| **Import Pipeline** | Scattered across root + src + Services | ~2,200 | Deep nesting, hard to follow |
| **Transaction Processing** | process_statements.php + Actions/ | ~800 | UI + business logic mixed |
| **Admin Configuration** | admin_parsers.php + Views/ | ~400 | Limited, but isolated |
| **Duplicate Detection** | src/ + Services/ + Views/ | ~600 | State fragmented |
| **Shared Models** | `class.*.php` + `src/Model/` | ~1,200 | Duplicated in two locations |
| **Foundation/Config** | Root + src/ | ~600 | Scattered configuration |

---

## Proposed Architecture

### New Logical Structure
```
ksf_bank_import/
│
├── 📦 Shared Infrastructure (Foundation Layer)
│   ├── models/
│   │   ├── BiTransaction.php
│   │   ├── BiLineItem.php
│   │   ├── BiStatement.php
│   │   ├── BankAccount.php
│   │   └── Partner.php
│   │
│   ├── dtos/
│   │   ├── TransactionDTO.php
│   │   ├── StatementDTO.php
│   │   └── ImportResultDTO.php
│   │
│   ├── Database/
│   │   ├── Repository/
│   │   │   └── TransactionRepository.php
│   │   └── migrations/
│   │
│   ├── Exceptions/
│   │   └── BankImportException.php
│   │
│   ├── Config/
│   │   └── BankImportConfig.php
│   │
│   └── Traits/
│       └── SharedValidation.php
│
├── 📦 Submodule 1: Import Pipeline
│   ├── entry_statements.php (stub calling ImportPipelineController)
│   │
│   ├── Controllers/
│   │   └── ImportPipelineController.php
│   │
│   ├── Handlers/
│   │   ├── FileUploadHandler.php
│   │   ├── ParserSelectionHandler.php
│   │   ├── ValidationHandler.php
│   │   ├── TransformHandler.php
│   │   ├── DuplicateDetectionHandler.php
│   │   └── ReviewStagerHandler.php
│   │
│   ├── Services/
│   │   ├── ImportOrchestrator.php
│   │   ├── ParserFactory.php
│   │   ├── ValidationService.php
│   │   └── ImportProgressTracker.php
│   │
│   ├── Views/
│   │   ├── ImportPipelineView.php
│   │   ├── FileUploadForm.php
│   │   ├── ValidationResultsView.php
│   │   └── ReviewQueueView.php
│   │
│   ├── parsers/
│   │   ├── BankStatementParser.php (abstract)
│   │   ├── CSVStatementParser.php
│   │   ├── XLSStatementParser.php
│   │   └── OFXStatementParser.php (plugins)
│   │
│   └── README_IMPORT.md (module-specific docs)
│
├── 📦 Submodule 2: Transaction Processing
│   ├── process_statements.php (entry point, ~40 LOC)
│   │
│   ├── Controllers/
│   │   └── TransactionProcessorController.php
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
│   ├── Processors/
│   │   ├── SupplierProcessor.php
│   │   ├── CustomerProcessor.php
│   │   ├── QuickEntryProcessor.php
│   │   ├── BankTransferProcessor.php
│   │   └── [processor per partner type]
│   │
│   ├── Views/
│   │   ├── ProcessingQueueView.php
│   │   ├── TransactionDetailView.php
│   │   └── ProcessingResultView.php
│   │
│   └── README_PROCESSING.md
│
├── 📦 Submodule 3: Duplicate Detection & Review
│   ├── transfer_match_review.php (entry point)
│   │
│   ├── Controllers/
│   │   └── DuplicateReviewController.php
│   │
│   ├── Services/
│   │   ├── DirectCodeMatcher.php
│   │   ├── FuzzyMatcher.php
│   │   ├── DuplicateRulesProvider.php
│   │   ├── DuplicateDetectionService.php
│   │   └── TransferMatchAuditService.php
│   │
│   ├── DTOs/
│   │   ├── DuplicateCheckResult.php
│   │   ├── DuplicatePair.php
│   │   └── AuditResult.php
│   │
│   ├── Views/
│   │   ├── DuplicateReviewView.php
│   │   ├── DuplicatePairRowView.php
│   │   ├── AuditResultsView.php
│   │   └── TransferMatchView.php
│   │
│   ├── Handlers/
│   │   └── DuplicateReviewHandler.php
│   │
│   └── README_DUPLICATES.md
│
├── 📦 Submodule 4: Administration & Configuration
│   ├── admin_parsers.php (entry point, ~20 LOC)
│   ├── admin_transfer_rules.php (new, for duplicate rules)
│   │
│   ├── Controllers/
│   │   ├── ParserAdminController.php
│   │   ├── RulesAdminController.php
│   │   └── ConfigurationController.php
│   │
│   ├── Services/
│   │   ├── ParserRegistry.php
│   │   ├── TransferRulesManager.php
│   │   └── ConfigurationService.php
│   │
│   ├── Views/
│   │   ├── ParserConfigView.php
│   │   ├── RulesConfigView.php
│   │   └── SystemSettingsView.php
│   │
│   └── README_ADMIN.md
│
├── tests/
│   ├── unit/
│   │   ├── Shared/
│   │   ├── Import/
│   │   ├── Processing/
│   │   ├── Duplicates/
│   │   └── Admin/
│   │
│   ├── integration/
│   │   └── [full workflow tests]
│   │
│   └── fixtures/
│
├── docs/
│   ├── ARCHITECTURE.md (this blueprint)
│   ├── SETUP.md
│   ├── TESTING.md
│   ├── API.md
│   ├── IMPORT_WORKFLOW.md
│   ├── PROCESSING_WORKFLOW.md
│   └── CONTRIBUTING.md
│
└── config/
    └── bank_import.config.php
```

---

## Consolidation Roadmap

### Phase 1: Foundation Layer Extraction (~2 weeks)
**Goal**: Create shared infrastructure layer without breaking anything

1. **Models Consolidation**
   - Audit `class.*.php` files (legacy procedural)
   - Audit `src/Model/*.php` files (modern OO)
   - Create canonical Model classes in `models/` folder
   - Update imports in all entry points
   - Deprecate old locations (but keep for 1-2 releases)

2. **DTOs Organization**
   - Move all DTOs to `dtos/` folder
   - Centralize DTO documentation
   - Create DTO factories

3. **Shared Services**
   - Consolidate `Services/` root vs `src/Service/`
   - Identify truly shared services (Repository, Validation, etc.)
   - Move to `Shared/Services/`

4. **Configuration**
   - Create `config/BankImportConfig.php`
   - Extract all hardcoded values
   - Create environment-based config

**Rollback Risk**: LOW - Creating new consolidated files while keeping old ones

---

### Phase 2: Import Pipeline Isolation (~3 weeks)
**Goal**: Move all import-related code into `Import/` submodule

1. **Create Import/ skeleton**
   - `Controllers/ImportPipelineController.php`
   - `Services/ImportOrchestrator.php`
   - Update `import_statements.php` to delegate to controller

2. **Move handlers**
   - Move from mixed locations to `Import/Handlers/`
   - Update namespace imports
   - Consolidate service dependencies

3. **Extract parser system**
   - Move parsers to `Import/parsers/`
   - Create factory pattern for parser selection
   - Document parser interface for plugins

4. **Reorganize views**
   - Move import-specific views to `Import/Views/`
   - Extract form rendering to components

**Rollback Risk**: MEDIUM - Changes to import workflow flow
**Test Coverage Needed**: Integration tests for full import pipeline

---

### Phase 3: Transaction Processing Isolation (~2 weeks)
**Goal**: Clean up transaction processing module

1. **Dispatcher Foundation** (Already done!)
   - Dispatcher foundation already in place from Phase 1-4 refactoring
   - Actions already extracted (Phase 2 of previous work)
   - Keep as-is, just reorganize location

2. **Create Processing/ submodule**
   - Move `Actions/` → `Processing/Actions/`
   - Move `Dispatcher/` → `Processing/Dispatcher/`
   - Move unique processors to `Processing/Processors/`

3. **Simplify entry point**
   - Update `process_statements.php` to ~40 lines
   - Call `TransactionProcessorController::dispatch($_POST)`
   - All logic now in controller

4. **Extract UI rendering**
   - Move processing-specific UI to `Processing/Views/`
   - Template-based rendering

**Rollback Risk**: LOW - Mostly moving already-refactored code
**Test Coverage Needed**: Unit tests for each action, integration for workflows

---

### Phase 4: Duplicate Detection Isolation (~2 weeks)
**Goal**: Clean up duplicate detection system

1. **Create Duplicates/ submodule**
   - Move detection services to `Duplicates/Services/`
   - Move DTOs to `Duplicates/DTOs/`
   - Move views to `Duplicates/Views/`

2. **Consolidate handlers**
   - Move `DuplicateReviewHandler` to module
   - Create unified review workflow

3. **Extract UI components**
   - Review dashboard components
   - Pair comparison renderers

4. **Create admin interface**
   - Rules configuration UI
   - Whitelisting interface

**Rollback Risk**: LOW - Mostly refactoring within self-contained system
**Test Coverage Needed**: Unit tests for matching algorithms, UI integration tests

---

### Phase 5: Admin Module Creation (~1 week)
**Goal**: Extract and centralize all admin functions

1. **Create Admin/ submodule**
   - Parser configuration admin
   - Transfer rules admin
   - System settings

2. **Create unified admin dashboard**
   - Navigation between admin functions
   - Role-based access control

3. **Extract configuration UI**
   - Move admin views to module

**Rollback Risk**: VERY LOW - Isolated admin functions
**Test Coverage Needed**: Access control tests, configuration persistence tests

---

## Dependency Flow (After Restructuring)

```
Entry Points (Thin Shells)
│
├─→ process_statements.php
│   └─→ Processing/Controllers/TransactionProcessorController
│       ├─→ Processing/Actions/*
│       ├─→ Processing/Processors/*
│       └─→ Shared/Models/*
│
├─→ import_statements.php
│   └─→ Import/Controllers/ImportPipelineController
│       ├─→ Import/Handlers/*
│       ├─→ Import/Services/*
│       ├─→ Duplicates/Services/DuplicateDetectionService
│       └─→ Shared/Models/*
│
├─→ transfer_match_review.php
│   └─→ Duplicates/Controllers/DuplicateReviewController
│       ├─→ Duplicates/Services/*
│       └─→ Shared/Models/*
│
└─→ admin_parsers.php
    └─→ Admin/Controllers/ParserAdminController
        ├─→ Admin/Services/*
        └─→ Shared/Models/*

Shared Foundation (Used by All)
├─→ Shared/Models/*
├─→ Shared/DTOs/*
├─→ Shared/Database/*
├─→ Shared/Config/*
└─→ Shared/Exceptions/*
```

---

## Benefits of Restructuring

### Cognitive Complexity
| Aspect | Before | After | Reduction |
|--------|--------|-------|-----------|
| Max LOC per module | 1,700 | 1,200 | 29% |
| Entry point clarity | Mixed concerns | Clear delegation | ~94% |
| Dependency graph | Tangled | Layered | 10x cleaner |
| New developer ramp-up | 2-3 weeks | 3-5 days | 75% faster |

### Maintainability
| Aspect | Benefit |
|--------|---------|
| Testing isolation | Each submodule testable independently |
| Deployment | Can update import without touching processing |
| Feature addition | New duplicate rules? Add to Duplicates/ only |
| Onboarding docs | Per-module README explains responsibility |
| CI/CD | Faster tests - can parallelize submodule tests |

### Extensibility
- **New parser**: Drop into `Import/parsers/`, register in factory
- **New processor**: Create class in `Processing/Processors/`, register in dispatcher
- **New duplicate rules**: Add to `Duplicates/Services/DuplicateRulesProvider`
- **New admin feature**: Create in `Admin/Services/` + `Admin/Views/`

### Technical Debt Reduction
- ✅ Eliminate duplicate `Services/` locations (consolidate in Shared/)
- ✅ Eliminate legacy `class.*.php` duplicates (modernize to Models/)
- ✅ Extract procedural UI logic (move to submodule Views/)
- ✅ Centralize configuration (create Config/)
- ✅ Standardize exception handling (create Exceptions/)

---

## Implementation Strategy

### 1. **Backward Compatibility First**
   - Don't delete old files, deprecate them
   - Keep old namespaces working for 2-3 releases
   - Add deprecation warnings to old code

### 2. **Test-Driven Refactoring**
   - Write tests for new structure first
   - Test both old and new paths work
   - Gradually migrate callers to new paths
   - Remove old paths only when migration complete

### 3. **Parallel Structure**
   - Create new structure alongside existing
   - Update one entry point at a time
   - Keep master working throughout

### 4. **Staged Rollout**
   - Phase 1: Foundation (2-3 weeks) - LOW risk
   - Phase 2: Import (3-4 weeks) - MEDIUM risk
   - Phase 3: Processing (2-3 weeks) - LOW risk
   - Phase 4: Duplicates (2-3 weeks) - LOW risk
   - Phase 5: Admin (1-2 weeks) - VERY LOW risk

---

## Risk Assessment

### Low Risk Changes (Phases 1, 3, 4, 5)
- **Consolidating code in new location while keeping old**: Duplicates temporarily exist
- **Renaming/moving procedures**: Use grep to find all callers
- **Adding new layers**: Existing code still works if called correctly
- **Mitigation**: Thorough grep search before any deletion

### Medium Risk Changes (Phase 2)
- **Refactoring 1,700 LOC import workflow**: Complex logic, many edge cases
- **Handler orchestration changes**: Import state machine is delicate
- **Mitigation**: Feature branch, comprehensive integration tests, gradual rollout

### Rollback Plan
```bash
# If anything goes wrong, revert phases:
git revert <phase-commit>

# Still have working code in old location if needed
# Remove deprecation once new paths proven stable
```

---

## Metrics for Success

### Code Quality
- [ ] Cyclomatic complexity per module < 10
- [ ] Average module size 800-1,200 LOC
- [ ] <5 external dependencies per module
- [ ] 80%+ unit test coverage per module

### Maintainability
- [ ] New parser can be added in <30 min
- [ ] New processor can be added in <1 hour
- [ ] New duplicate rule can be added in <15 min
- [ ] New admin feature can be added in <2 hours

### Documentation
- [ ] Each module has README explaining responsibility
- [ ] Architecture diagram in main docs
- [ ] Entry point flow documented
- [ ] Database schema documented

### Team Experience
- [ ] New dev can find feature in < 5 min
- [ ] New dev understands module boundary in < 1 hour
- [ ] Debugging workflow is 50% faster
- [ ] PR review time reduced by 30%

---

## Questions & Decisions

### Q: What about the legacy `class.*.php` files?
**A**: Gradually consolidate into `Shared/Models/`. Keep old files for 1-2 releases with deprecation warnings pointing to new location.

### Q: Do we rename entry points?
**A**: No - keep `process_statements.php`, `import_statements.php` names. These are FA module conventions. Just make them thin shells (~30-40 LOC) that delegate to controllers.

### Q: What about circular dependencies?
**A**: Enforce via imports in root FaBankImport namespace. If Service A needs Service B, B must be in same or foundational layer.

### Q: Can we do this incrementally?
**A**: Yes! Each phase is relatively independent. Can deploy Phase 1 (Foundation) alone, then Phase 2 independently.

### Q: What breaks deployment?
**A**: Minimal if done carefully. Keep all old code working during transition. Parallel structure during migration. Only delete deprecated code after 2-3 releases.

---

## Next Steps

1. **Review this blueprint** with team
2. **Create Phase 1 implementation plan** (Foundation Layer)
3. **Set up feature branch** for structural changes
4. **Write tests** for new structure before moving code
5. **Document consolidation rules** (what goes where)
6. **Establish code review checklist** for migration PRs

---

## Related Documentation

- Current architecture analysis: `STRUCTURAL_ANALYSIS.md`
- Dispatcher refactoring: `DISPATCHER_REFACTORING_COMPLETE.md`
- Phase 1-4 refactoring history: Git commits 9a031b3 → 033b265
