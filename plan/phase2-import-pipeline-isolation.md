# Phase 2 Implementation Plan: Import Pipeline Consolidation

## Executive Summary
Phase 2 consolidates scattered import logic into a unified service layer using Phase 1 repositories and domain entities. The import pipeline will follow handler-service-repository patterns established in Phase 1.

**Duration**: 2-3 weeks  
**Risk**: Medium  
**Dependencies**: Phase 1 repositories (✅ COMPLETE)

---

## 1. Overview & Objectives

### Current State (Pain Points)
- Import logic scattered across multiple files
  - `import_statements.php` (1,700+ LOC)
  - `admin_parsers.php` (400+ LOC)
  - Multiple parser implementations
  - File upload, validation, transformation mixed together

### Target State (Phase 2)
- Unified `Import/` namespace with clear separation of concerns
- Orchestrated workflow: Upload → Parse → Validate → Transform → Detect Duplicates → Stage Review
- Reusable services and handlers
- Full test coverage
- Follows Phase 1 architectural patterns

### Success Criteria
✅ All import logic consolidated into `Import/` namespace  
✅ Handler-service-repository pattern throughout  
✅ 85%+ test coverage  
✅ No breaking changes to existing imports  
✅ Dependency injection throughout  

---

## 2. Architecture Overview

### Import Pipeline Workflow
```
┌──────────────────────────────────────────────────────────────────┐
│                     ImportPipelineController                      │
│                      (handles routing)                            │
└──────────────────┬─────────────────────────────────────────────────┘
                   │
┌──────────────────▼─────────────────────────────────────────────────┐
│                    ImportOrchestrator                              │
│            (coordinates multi-step workflow)                       │
└──────────────────┬─────────────────────────────────────────────────┘
                   │
     ┌─────────────┼─────────────┬──────────────┬──────────────┐
     │             │             │              │              │
  Step 1         Step 2       Step 3        Step 4         Step 5
  Upload        Parser       Validate      Transform      Duplicate
  Handler      Handler       Handler       Handler        Handler
     │             │             │              │              │
  UploadService  ParserFactory ValidationService  TransformService  DuplicateService
     │             │             │              │              │
  Database      Abstract     Statement    BiLineItem     Transaction
                Parser       Validation   Calculation     Matching
```

### Component Hierarchy

```
Import/
├── Controllers/
│   └── ImportPipelineController.php          ← Main entry point
├── Services/
│   ├── ImportOrchestrator.php                ← Workflow coordinator
│   ├── ParserFactory.php                     ← Parser selection
│   ├── ValidationService.php                 ← Data validation
│   ├── TransformationService.php             ← Statement→Entity conversion
│   ├── DuplicateDetectionService.php         ← Duplicate checking
│   ├── ReviewStagerService.php               ← Preparation for review
│   └── ImportProgressTracker.php             ← Progress tracking
├── Handlers/
│   ├── FileUploadHandler.php                 ← Handle file uploads
│   ├── ParserSelectionHandler.php            ← Select appropriate parser
│   ├── ValidationHandler.php                 ← Run validation pipeline
│   ├── TransformationHandler.php             ← Convert to domain entities
│   ├── DuplicateDetectionHandler.php         ← Detect duplicates
│   └── ReviewStagerHandler.php               ← Prepare for manual review
├── Parsers/
│   ├── AbstractBankStatementParser.php       ← Base parser
│   ├── CsvParser.php                         ← CSV implementation
│   ├── XlsParser.php                         ← Excel implementation
│   └── QfxParser/
│       ├── AbstractQfxParser.php
│       ├── CibcQfxParser.php
│       ├── ManulifeQfxParser.php
│       └── PcmcQfxParser.php
├── Validators/
│   └── StatementDataValidator.php            ← Multi-rule validation
├── DTOs/
│   ├── ImportSessionDTO.php                  ← Track import state
│   ├── ParsedStatementDTO.php                ← Parser→Entity bridge
│   ├── ValidationResultDTO.php               ← Validation outcome
│   └── ImportProgressDTO.php                 ← Progress tracking
└── Exceptions/
    ├── ImportException.php                   ← Base exception
    ├── ParserException.php
    ├── ValidationException.php
    ├── DuplicateDetectedException.php
    └── ImportCancelledException.php
```

---

## 3. Core Services (Phase 2.1-2.3)

### 2.1 ImportOrchestrator (Core Workflow)
**Purpose**: Coordinates multi-step import workflow  
**Implements**: OrchestratorInterface  
**Dependencies**: Handlers (via DI), ProgressTracker, FileRepository

**Key Methods**:
```php
public function orchestrateImport(
    UploadedFile $file,
    ImportContext $context
): ImportResult

public function getProgress(): ImportProgressDTO

public function cancel(): void

public function rollback(string $sessionId): bool
```

**Responsibilities**:
1. Delegate to appropriate handler for each step
2. Track progress and allow cancellation
3. Handle error rollback
4. Coordinate handler chain

### 2.2 ParserFactory (Parser Selection)
**Purpose**: Detect and instantiate appropriate parser  
**Implements**: ParserFactoryInterface  
**Dependencies**: Parser implementations

**Key Methods**:
```php
public function detectParserType(UploadedFile $file): string

public function createParser(string $type): BankStatementParserInterface

public function supportedFormats(): array
```

**Responsibilities**:
1. Detect file type from extension/content
2. Create appropriate parser instance
3. Validate parser compatibility

### 2.3 ValidationService (Multi-Rule Validation)
**Purpose**: Validate parsed statement data against business rules  
**Implements**: ValidatorInterface  
**Dependencies**: StatementDataValidator

**Key Methods**:
```php
public function validate(ParsedStatementDTO $data): ValidationResultDTO

public function addValidator(RuleValidator $validator): void

public function getActiveValidators(): array
```

**Responsibilities**:
1. Apply validation rules in sequence
2. Collect all errors (non-blocking)
3. Determine if import can proceed

### 2.4 TransformationService (DTO→Entity Conversion)
**Purpose**: Convert parsed DTO to domain entities  
**Implements**: TransformerInterface  
**Dependencies**: Entity factories, BiLineItemRepository

**Key Methods**:
```php
public function transformStatement(
    ParsedStatementDTO $dto
): BiStatement

public function transformTransaction(
    array $txnData
): BiTransaction

public function transformLineItem(
    array $itemData
): BiLineItem
```

**Responsibilities**:
1. Create immutable domain entities
2. Apply business rule calculations
3. Set defaults and cross-references

### 2.5 DuplicateDetectionService (Duplicate Checking)
**Purpose**: Identify and flag duplicate transactions  
**Implements**: DuplicateDetectorInterface  
**Dependencies**: TransactionRepository

**Key Methods**:
```php
public function findDuplicates(BiStatement $statement): array

public function markDuplicate(BiTransaction $txn, int $existingId): void

public function getDuplicateReport(): array
```

**Responsibilities**:
1. Use transaction fingerprinting (code+amount+date)
2. Check against existing transactions
3. Flag for review/merge

---

## 4. Handlers (Phase 2.4-2.5)

### Handler Pattern (from Phase 1)
```php
abstract class BaseImportHandler {
    protected ServiceContainer $container;
    protected array $result = ['success' => true, 'errors' => []];
    
    abstract public function handle(ImportSessionDTO $session): ImportSessionDTO;
}
```

All handlers implement:
- Dependency injection via constructor
- Result DTO with success/errors fields
- Rollback capability
- Exception handling to domain exceptions

### 2.4 Phase 2 Handlers

1. **FileUploadHandler**
   - Validate file (type, size, encoding)
   - Store to temporary location
   - Return file reference

2. **ParserSelectionHandler**
   - Detect parser type
   - Validate parser supports file
   - Return parser instance

3. **ValidationHandler**
   - Run validation pipeline
   - Collect errors
   - Flag warnings
   - Determine if Continue or Review

4. **TransformationHandler**
   - Convert parsed DTO to entities
   - Apply calculations
   - Create BiLineItems
   - Link with statements

5. **DuplicateDetectionHandler**
   - Find matching transactions
   - Mark duplicates
   - Prepare merge suggestions

6. **ReviewStagerHandler**
   - Create review session
   - Queue for manual review if needed
   - Prepare conflict resolution data

---

## 5. DTOs & Data Flow

### ImportSessionDTO (State Container)
```php
{
    "session_id": "uuid",
    "uploaded_file_id": 123,
    "file_name": "statements.csv",
    "parser_type": "csv",
    "step": "parsing",  // upload→parsing→validation→transform→duplicate→review
    "status": "in_progress",  // in_progress, success, error, cancelled
    "parsed_data": ParsedStatementDTO,
    "validation_result": ValidationResultDTO,
    "entities_created": [BiStatement, BiTransaction, BiLineItem],
    "duplicates_found": [],
    "errors": [],
    "created_at": timestamp,
    "updated_at": timestamp
}
```

### ParsedStatementDTO (Parser Output)
```php
{
    "statement_date": "2024-01-15",
    "account_reference": "1234567",
    "currency": "CAD",
    "opening_balance": 10000.00,
    "closing_balance": 12000.00,
    "transactions": [
        {
            "date": "2024-01-10",
            "description": "DEPOSIT",
            "amount": 1000.00,
            "dc": "C",
            // ... more fields
        }
    ]
}
```

### ValidationResultDTO (Validation Outcome)
```php
{
    "valid": true,
    "errors": [],
    "warnings": ["Opening balance mismatch"],
    "rule_violations": {
        "balance_check": ["Opening balance doesn't match expected"],
    }
}
```

---

## 6. Implementation Phases

### Phase 2.1: Foundation (Week 1)
- [ ] Create `Import/` directory structure
- [ ] Define all interfaces (Orchestrator, Parser, Validator, Transformer, Duplicate, etc.)
- [ ] Create DTOs (ImportSessionDTO, ParsedStatementDTO, ValidationResultDTO, ImportProgressDTO)
- [ ] Create base handler class
- [ ] Create domain exceptions (ImportException, ParserException, etc.)
- [ ] Tests: 0 tests (foundation only)

**Deliverable**: Structure and interfaces, ready for implementation

### Phase 2.2: Core Services (Week 2)
- [ ] Implement ImportOrchestrator
- [ ] Implement ParserFactory
- [ ] Implement ValidationService
- [ ] Implement TransformationService  
- [ ] Implement DuplicateDetectionService
- [ ] Implement ReviewStagerService
- [ ] Tests: 50+ tests covering services

**Deliverable**: Fully functional services with test coverage

### Phase 2.3: Handlers & Controllers (Week 2-3)
- [ ] Implement all 6 handlers
- [ ] Implement ImportPipelineController
- [ ] Update `import_statements.php` to delegate to controller
- [ ] Remove old import code (after validation)
- [ ] Tests: 40+ handler tests + integration tests

**Deliverable**: Complete import pipeline running through new service layer

### Phase 2.4: Parser Consolidation (Week 3)
- [ ] Create AbstractBankStatementParser
- [ ] Consolidate QFX parsers (Cibc, Manulife, Pcmc)
- [ ] Create/update CSV, XLS parsers
- [ ] Register in ParserFactory
- [ ] Tests: Parser-specific tests

**Deliverable**: All parsers working through unified interface

### Phase 2.5: Integration & Optimization (Week 3+)
- [ ] End-to-end testing (upload through review)
- [ ] Performance optimization
- [ ] Error handling improvements
- [ ] Documentation updates
- [ ] Tests: E2E tests

**Deliverable**: Production-ready import pipeline

---

## 7. Acceptance Criteria

### Functionality
- ✅ All file types (CSV, XLS, OFX, QFX) parse correctly
- ✅ Validation catches all known errors
- ✅ Duplicates detected with 95%+ accuracy
- ✅ No data loss in transformation
- ✅ Rollback restores clean state
- ✅ Progress tracking works for long imports (>1000 transactions)

### Code Quality
- ✅ 85%+ test coverage
- ✅ All classes pass static analysis
- ✅ No deprecated FA function calls
- ✅ Type hints on all methods
- ✅ SOLID principles followed

### Performance
- ✅ Import <1000 txn in <5 seconds
- ✅ Import <10000 txn in <30 seconds
- ✅ No memory leaks (stays <50MB for <10k txn)

### Documentation
- ✅ README explaining import workflow
- ✅ Handler documentation
- ✅ Parser implementation guide
- ✅ Error handling guide

---

## 8. Key Dependencies & Integration Points

### Repositories Used
- `TransactionRepository` - save new transactions
- `StatementRepository` - save statements
- `LineItemRepository` - save line items (Phase 1.3)
- `BankAccountMappingRepository` - validate accounts
- `UploadedFileRepository` - track file uploads
- `ConfigRepository` - get validation rules

### Entities Used
- `BiStatement` - immutable statement entity
- `BiTransaction` - immutable transaction entity
- `BiLineItem` - immutable line item entity
- `BankAccountMapping` - account cross-reference

### ViewModels/DTOs
- All in `Shared/DTOs/` namespace
- Unified naming and structure
- No circular dependencies

---

## 9. Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Parser inconsistencies | High | Test each parser with real bank data; create parser validator |
| Validation rule conflicts | Medium | Document all rules; create validation matrix |
| Performance on large imports | Medium | Profile early; implement streaming where needed |
| Breaking existing imports | Critical | Feature flag old import path; run parallel; gradual cutover |
| File encoding issues | Medium | Test with UTF-8, UTF-16, Latin-1; robust encoding detection |

---

## 10. Success Metrics

**By end of Phase 2:**
- ✅ 100% of legacy import code paths consolidated
- ✅ 85%+ test coverage (tests written first)
- ✅ 0 breaking changes to existing API
- ✅ 50% reduction in import bug reports
- ✅ Import performance within SLA

---

## 11. Next Steps

1. **Immediate** (Now)
   - Create `src/Ksfraser/FaBankImport/Import/` directory structure
   - Create base interfaces and DTOs
   - Create domain exceptions
   - Write tests for structure

2. **Week 1**
   - Implement services (Orchestrator, Factory, Validator, etc.)
   - Write 50+ service tests
   - Establish pattern for remaining services

3. **Week 2**
   - Implement handlers
   - Implement ImportPipelineController
   - Write integration tests

4. **Week 3**
   - Consolidate parsers
   - Performance optimization
   - Production release

---

## Appendix A: File Structure

```
src/Ksfraser/FaBankImport/
├── Import/
│   ├── Controllers/ImportPipelineController.php
│   ├── Services/
│   │   ├── ImportOrchestrator.php
│   │   ├── ParserFactory.php
│   │   ├── ValidationService.php
│   │   ├── TransformationService.php
│   │   ├── DuplicateDetectionService.php
│   │   ├── ReviewStagerService.php
│   │   └── ImportProgressTracker.php
│   ├── Handlers/
│   │   ├── FileUploadHandler.php
│   │   ├── ParserSelectionHandler.php
│   │   ├── ValidationHandler.php
│   │   ├── TransformationHandler.php
│   │   ├── DuplicateDetectionHandler.php
│   │   └── ReviewStagerHandler.php
│   ├── Parsers/
│   │   ├── AbstractBankStatementParser.php
│   │   ├── CsvParser.php
│   │   ├── XlsParser.php
│   │   └── QfxParser/
│   ├── Validators/
│   │   └── StatementDataValidator.php
│   ├── DTOs/
│   │   ├── ImportSessionDTO.php
│   │   ├── ParsedStatementDTO.php
│   │   ├── ValidationResultDTO.php
│   │   └── ImportProgressDTO.php
│   └── Exceptions/
│       ├── ImportException.php
│       ├── ParserException.php
│       ├── ValidationException.php
│       ├── DuplicateDetectedException.php
│       └── ImportCancelledException.php

tests/
├── Unit/Import/
│   ├── Services/
│   │   ├── ImportOrchestratorTest.php
│   │   ├── ParserFactoryTest.php
│   │   ├── ValidationServiceTest.php
│   │   ├── TransformationServiceTest.php
│   │   ├── DuplicateDetectionServiceTest.php
│   │   └── ReviewStagerServiceTest.php
│   ├── Handlers/
│   │   ├── FileUploadHandlerTest.php
│   │   ├── ParserSelectionHandlerTest.php
│   │   ├── ValidationHandlerTest.php
│   │   ├── TransformationHandlerTest.php
│   │   ├── DuplicateDetectionHandlerTest.php
│   │   └── ReviewStagerHandlerTest.php
│   └── Parsers/
│       ├── CsvParserTest.php
│       └── QfxParserTest.php
└── Integration/
    └── ImportPipelineIntegrationTest.php
```

---

**Plan Created**: April 4, 2026  
**Phase**: 2 - Import Pipeline Consolidation  
**Status**: Ready for Phase 2.1 (Foundation) implementation
