# Phase 2.2 Service Implementation Analysis

**Analysis Date**: April 4, 2026  
**Status**: All Phase 2.1 interfaces defined, ready for Phase 2.2 implementation  
**Scope**: Import module service layer

---

## Executive Summary

Phase 2.1 successfully created all service interface contracts for the bank import pipeline. Phase 2.2 now requires implementing these 7 core services plus 6 handlers to complete the import pipeline.

### Phase 2.1 Completion Status
- ✅ 7 service interfaces defined (all with complete contracts)
- ✅ 7 domain exception types defined
- ✅ 4 DTOs created (ImportSessionDTO, ParsedStatementDTO, ValidationResultDTO, ImportProgressDTO)
- ✅ BaseImportHandler created for handler inheritance
- ✅ 3 utility services implemented (BankAccountResolver, ChargeCalculator, TransactionStateManager)
- 📋 **PENDING**: 7 core service implementations
- 📋 **PENDING**: 6 handler implementations

---

## Phase 2.1 Service Interfaces Status

### 1. ParserInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/ParserInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ParserInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: Parser implementation interface  
**Key Methods**:
- `parse(string $filePath, array $options = []): array`
- `getSupportedTypes(): array`
- `getName(): string`

**Expected Exceptions**:
- `FileNotFoundException` (from `Ksfraser\Exceptions\Utility`)
- `UnsupportedFileTypeException` (from `Ksfraser\Exceptions\Utility`)
- `ParsingFailedException` (from `Ksfraser\Exceptions\Utility`)
- `EncodingMismatchException` (from `Ksfraser\Exceptions\Utility`)

**Related Files**:
- DTO Output: `ParsedStatementDTO`
- Used By: `ParserFactory`, `ImportOrchestrator`

---

### 2. ParserFactoryInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/ParserFactoryInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ParserFactoryInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: Factory pattern for parser selection  
**Key Methods**:
- `getParser(string $filePath): ParserInterface`
- `registerParser(ParserInterface $parser): void`
- `getParsers(): array`

**Expected Exceptions**:
- `ParserException` - If no suitable parser found for file type

**Responsibilities**:
1. Detect file type from extension/content
2. Instantiate appropriate parser
3. Validate parser compatibility
4. Manage parser registry

**Related Files**:
- Implements: Creates instances of `ParserInterface`
- Used By: `ImportOrchestrator`, `ParserSelectionHandler`

**Implementation Notes**:
- Should support: CSV, XLS, XLSX, OFX, QFX files
- Phase 2.4 will consolidate existing parsers into unified interface

---

### 3. ValidatorInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/ValidatorInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ValidatorInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: Multi-rule validation service  
**Key Methods**:
- `validate(ParsedStatementDTO $statement): ValidationResultDTO`
- `getName(): string`

**Expected Exceptions**:
- `ValidationException` - If validation fails

**Related DTO**:
- Input: `ParsedStatementDTO`
- Output: `ValidationResultDTO`

**Responsibilities**:
1. Apply validation rules in sequence
2. Collect all errors (non-blocking)
3. Collect warnings separately
4. Determine if import can proceed

**Related Files**:
- Processes: `ParsedStatementDTO` from parser
- Used By: `ImportOrchestrator`, `ValidationHandler`

---

### 4. TransformerInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/TransformerInterface.php`](src/Ksfraser/FaBankImport/Import/Services/TransformerInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: DTO-to-Entity transformation service  
**Key Methods**:
- `transform(ParsedStatementDTO $statement): array`
- `getName(): string`

**Expected Exceptions**:
- `TransformException` - If transformation fails (entity creation, type mismatch, missing data)

**Related DTOs**:
- Input: `ParsedStatementDTO` from validator
- Output: Domain entities (BiStatement, BiTransaction, BiLineItem)

**Responsibilities**:
1. Create immutable domain entities
2. Apply business rule calculations (charges, dates, etc.)
3. Set default values and cross-references
4. Validate entity constraints

**Related Files**:
- Creates: BiStatement, BiTransaction, BiLineItem entities
- Used By: `ImportOrchestrator`, `TransformationHandler`

---

### 5. DuplicateDetectorInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/DuplicateDetectorInterface.php`](src/Ksfraser/FaBankImport/Import/Services/DuplicateDetectorInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: Duplicate detection and matching service  
**Key Methods**:
- `detectDuplicates(array $transaction): array`
- `getName(): string`

**Expected Exceptions**:
- `DuplicateDetectedException` - If exact duplicate found

**Responsibilities**:
1. Use transaction fingerprinting (code+amount+date pattern)
2. Check against existing transactions in database
3. Find exact and probable duplicates
4. Apply intelligent matching based on fuzzy logic
5. Return array of matching transactions

**Related Files**:
- Input: Transformed entities from TransformerInterface
- Used By: `ImportOrchestrator`, `DuplicateDetectionHandler`

---

### 6. ReviewStagerInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/ReviewStagerInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ReviewStagerInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: Review session management service  
**Key Methods**:
- `createReviewSession(string $sessionId, array $duplicates): array`
- `getPendingReviews(?int $limit = null): array`
- `recordDecision(string $reviewId, string $decision, array $metadata = []): void`
- `getName(): string`

**Expected Exceptions**:
- `DuplicateDetectedException` - If no duplicates to review

**Responsibilities**:
1. Create review sessions for suspected duplicates
2. Store review data for user decision
3. Retrieve pending reviews
4. Record user decisions (accept, reject, link)
5. Queue for merge or skip

**Related Files**:
- Used By: `ImportOrchestrator`, `ReviewStagerHandler`

---

### 7. OrchestratorInterface
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/OrchestratorInterface.php`](src/Ksfraser/FaBankImport/Import/Services/OrchestratorInterface.php)  
**Status**: ✅ Interface defined, **NOT implemented**  
**Type**: Core pipeline orchestrator  
**Key Methods**:
- `executeImport(ImportSessionDTO $session): ImportSessionDTO`
- `cancelImport(string $sessionId, string $reason): void`
- `getName(): string`

**Expected Exceptions**:
- All service exceptions bubble up (no explicit throws, but handles delegation)

**Responsibilities**:
1. Coordinate handlers through the pipeline
2. Manage session state through all steps
3. Handle errors and rollback
4. Report progress
5. Pipeline steps: upload → parse → validate → transform → detect-duplicates → review → complete

**Related Files**:
- Delegates To: All other services via handlers
- Used By: ImportPipelineController, ImportStatementsCommand

**Pipeline Steps**:
1. Upload: File storage and validation
2. Parse: Detect parser and parse file
3. Validate: Run validation rules
4. Transform: Convert to domain entities
5. Detect-Duplicates: Find matching transactions
6. Review: Stage for manual review if needed
7. Complete: Persist entities to database

---

## Support Services (Already Implemented)

### Utility Services
These are helper services already implemented in Phase 2.1. Phase 2.2 will use them:

#### 1. BankAccountResolver
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/BankAccountResolver.php`](src/Ksfraser/FaBankImport/Import/Services/BankAccountResolver.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: Resolve and validate bank accounts  
**Key Method**: `resolveByAccountNumber(string $accountNumber): array`  
**Throws**: `BankAccountNotFoundException`

#### 2. ChargeCalculator
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/ChargeCalculator.php`](src/Ksfraser/FaBankImport/Import/Services/ChargeCalculator.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: Calculate charges from collection IDs  
**Key Method**: `calculate(int $transactionId, string $collectionIdsCsv): float`  
**Throws**: `ChargeCalculationException`

#### 3. TransactionStateManager
**Location**: [`src/Ksfraser/FaBankImport/Import/Services/TransactionStateManager.php`](src/Ksfraser/FaBankImport/Import/Services/TransactionStateManager.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: Apply transaction state to bank import controller  
**Key Method**: `applyTransactionState(...): void`  
**No Exceptions**

---

## Exception Hierarchy

All exceptions defined in [`src/Ksfraser/FaBankImport/Import/Exceptions/`](src/Ksfraser/FaBankImport/Import/Exceptions/)

### Core Exception Types
```
ImportException (base)
├── ParserException
│   └── For unsupported types, parsing failures, encoding issues
├── ValidationException
│   └── For validation rule violations
├── TransformException
│   └── For entity creation failures
├── DuplicateDetectedException
│   └── For duplicate detection
├── ImportCancelledException
│   └── For cancelled imports
├── ChargeCalculationException
│   └── For charge calculation failures
├── BankAccountNotFoundException
│   └── For account resolution failures
├── BankTransferException
│   └── For bank transfer creation failures
├── ContactImportException
│   └── For contact import failures
├── ProcessorNotFoundException
│   └── For processor lookup failures
├── TransactionFetchException
│   └── For transaction fetch operations
├── TransactionProcessingException
│   └── For transaction processing failures
├── TransactionValidationException
│   └── For transaction validation failures
├── StatementValidationException
│   └── For statement validation failures
└── ProcessStatementsFetchException
    └── For process statements fetch operations
```

### Using Exceptions in Phase 2.2 Services
Each service should:
1. Catch any low-level exceptions
2. Wrap in appropriate domain exception
3. Include context (optional data dict)
4. Mark as recoverable if appropriate (for batch processing)

Example:
```php
try {
    // Operation
} catch (\Throwable $e) {
    throw ParserException::parsingFailed('CSV format error', $lineNumber);
}
```

---

## DTOs Required for Phase 2.2

### 1. ImportSessionDTO
**Location**: [`src/Ksfraser/FaBankImport/Import/DTOs/ImportSessionDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ImportSessionDTO.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: State container for entire import session  
**Key Properties**:
- `session_id`: string (UUID)
- `uploaded_file_id`: int
- `file_name`: string
- `parser_type`: string
- `step`: string (upload|parsing|validation|transform|duplicate|review|complete)
- `status`: string (in_progress|success|error|cancelled)
- `parsed_data`: ParsedStatementDTO (nullable)
- `validation_result`: ValidationResultDTO (nullable)
- `entities_created`: array (BiStatement, BiTransaction, BiLineItem)
- `duplicates_found`: array
- `errors`: array
- `created_at`: int (timestamp)
- `updated_at`: int (timestamp)

### 2. ParsedStatementDTO
**Location**: [`src/Ksfraser/FaBankImport/Import/DTOs/ParsedStatementDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ParsedStatementDTO.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: Parser output - normalized statement data  
**Key Properties**:
- `statement_date`: string (YYYY-MM-DD)
- `account_reference`: string
- `currency`: string (e.g., CAD, USD)
- `opening_balance`: float
- `closing_balance`: float
- `transactions`: array of transaction arrays

### 3. ValidationResultDTO
**Location**: [`src/Ksfraser/FaBankImport/Import/DTOs/ValidationResultDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ValidationResultDTO.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: Validator output - validation outcome  
**Key Properties**:
- `valid`: bool
- `errors`: array (blocking errors)
- `warnings`: array (non-blocking warnings)
- `rule_violations`: array (keyed by rule name)

### 4. ImportProgressDTO
**Location**: [`src/Ksfraser/FaBankImport/Import/DTOs/ImportProgressDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ImportProgressDTO.php)  
**Status**: ✅ **IMPLEMENTED**  
**Purpose**: Progress tracking for long-running imports  
**Key Properties**:
- `total_items`: int
- `processed_items`: int
- `percent_complete`: float
- `current_step`: string
- `estimated_remaining_seconds`: int

---

## Handlers Needing Implementation

### Handler Base Class
**Location**: [`src/Ksfraser/FaBankImport/Import/Handlers/BaseImportHandler.php`](src/Ksfraser/FaBankImport/Import/Handlers/BaseImportHandler.php)  
**Status**: ✅ **IMPLEMENTED**  
**Abstract Methods**: 
- `getName(): string`
- `handle(ImportSessionDTO $session): array`

### Required Handler Implementations (Phase 2.3)

1. **FileUploadHandler**
   - Validates file (type, size, encoding)
   - Stores to temporary location
   - Returns file reference
   
2. **ParserSelectionHandler**
   - Detects parser type via ParserFactory
   - Validates parser supports file
   - Returns parser instance
   
3. **ValidationHandler**
   - Runs validation pipeline via ValidatorInterface
   - Collects errors
   - Flags warnings
   - Determines Continue or Review
   
4. **TransformationHandler**
   - Converts parsed DTO to entities via TransformerInterface
   - Applies calculations
   - Creates BiLineItems
   - Links with statements
   
5. **DuplicateDetectionHandler**
   - Finds matching transactions via DuplicateDetectorInterface
   - Marks duplicates
   - Prepares merge suggestions
   
6. **ReviewStagerHandler**
   - Creates review session via ReviewStagerInterface
   - Queues for manual review if needed
   - Prepares conflict resolution data

---

## Phase 2.2 Implementation Priority Order

### High Priority (Complete First - Core Pipeline)

1. **ParserFactoryInterface Implementation** (3-4 hours)
   - Enables file type detection
   - Prepares for parser consolidation
   - Blocks: Orchestrator
   
2. **ValidatorInterface Implementation** (3-4 hours)
   - Enables data validation
   - Core business rule checks
   - Blocks: Orchestrator

3. **TransformerInterface Implementation** (4-5 hours)
   - Enables entity creation
   - Applies business calculations
   - Blocks: Orchestrator
   
4. **OrchestratorInterface Implementation** (5-6 hours)
   - Coordinates all services
   - Manages state and errors
   - Enables end-to-end pipeline
   - **Depends on**: Parser, Validator, Transformer implementations

### Medium Priority (Enable Review Process)

5. **DuplicateDetectorInterface Implementation** (3-4 hours)
   - Identifies duplicate transactions
   - Enables deduplication workflow
   - Blocks: ReviewStager
   
6. **ReviewStagerInterface Implementation** (2-3 hours)
   - Manages review sessions
   - Stores user decisions
   - Completes import pipeline

### Lower Priority (Handler Implementation - Phase 2.3)

7. **All 6 Handlers** (8-10 hours)
   - Implement after services
   - Follow same pattern
   - Inherit from BaseImportHandler

---

## Detailed Service Implementation Requirements

### Service Implementation Template

Each Phase 2.2 service should:

1. **Implement the interface**
   ```php
   class MyService implements MyServiceInterface
   ```

2. **Dependency injection via constructor**
   ```php
   public function __construct(
       private readonly SomeRepository $repo,
       private readonly AnotherService $service
   ) {}
   ```

3. **Throw domain exceptions**
   ```php
   throw MyException::specificReason('context');
   ```

4. **Add comprehensive PHPDoc**
   - Method descriptions
   - Parameter types with @param
   - Return types with @return
   - Exception documentation with @throws

5. **Follow SOLID principles**
   - Single Responsibility
   - Dependency Injection
   - Interface segregation
   - Open/Closed for extension

### Exception Handling Pattern

```php
public function process($data)
{
    try {
        // Business logic
    } catch (DomainException $e) {
        // Re-throw domain exceptions
        throw $e;
    } catch (\Throwable $e) {
        // Wrap low-level exceptions
        throw MyServiceException::reason($e->getMessage(), context: [...], previous: $e);
    }
}
```

---

## Testing Requirements for Phase 2.2

For each service implementation, create tests for:

1. **Happy Path** (1-2 tests)
   - Normal operation with valid input
   - Expected output format

2. **Error Cases** (2-3 tests per service)
   - Each expected exception type
   - Invalid input handling

3. **Edge Cases** (1-2 tests)
   - Empty data
   - Boundary conditions
   - Special characters

4. **Integration Tests** (1-2 tests per service)
   - With other services
   - With repositories
   - Full pipeline end-to-end

**Target**: 50+ tests for Phase 2.2 (7 services × 5-7 tests each)

---

## Files to Create in Phase 2.2

### Service Implementations (7 files)
```
src/Ksfraser/FaBankImport/Import/Services/
├── ParserFactory.php              (implements ParserFactoryInterface)
├── Validator.php                  (implements ValidatorInterface)
├── Transformer.php                (implements TransformerInterface)
├── DuplicateDetector.php           (implements DuplicateDetectorInterface)
├── ReviewStager.php               (implements ReviewStagerInterface)
├── ImportOrchestrator.php          (implements OrchestratorInterface)
```

### Test Files (7 files)
```
tests/unit/Import/Services/
├── ParserFactoryTest.php
├── ValidatorTest.php
├── TransformerTest.php
├── DuplicateDetectorTest.php
├── ReviewStagerTest.php
├── ImportOrchestratorTest.php
├── ... integration tests
```

---

## Acceptance Criteria for Phase 2.2

### Functionality ✅
- All 7 services implement their interfaces
- All services throw appropriate domain exceptions
- Services integrate with orchestrator
- Full pipeline executes without errors
- Error handling and rollback works

### Code Quality ✅
- Type hints on all methods
- PHPDoc on all public methods
- 85%+ test coverage
- No deprecated FA function calls
- SOLID principles followed
- Static analysis passes

### Performance ✅
- Import <1000 transactions in <5 seconds
- No memory leaks on large files
- Progress tracking works

---

## Quick Reference: Service Interface Contracts

| Service | Interface | Status | Exception | Dependencies |
|---------|-----------|--------|-----------|--------------|
| Parser | ParserInterface | Stub | ParsingFailedException | - |
| ParserFactory | ParserFactoryInterface | Stub | ParserException | ParserInterface |
| Validator | ValidatorInterface | Stub | ValidationException | - |
| Transformer | TransformerInterface | Stub | TransformException | BiStatement, BiTransaction, BiLineItem entities |
| DuplicateDetector | DuplicateDetectorInterface | Stub | DuplicateDetectedException | TransactionRepository |
| ReviewStager | ReviewStagerInterface | Stub | DuplicateDetectedException | Database |
| Orchestrator | OrchestratorInterface | Stub | Any service exception | All other services |

---

## Referenced Files

### Interface Definitions
- [`src/Ksfraser/FaBankImport/Import/Services/ParserInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ParserInterface.php)
- [`src/Ksfraser/FaBankImport/Import/Services/ParserFactoryInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ParserFactoryInterface.php)
- [`src/Ksfraser/FaBankImport/Import/Services/ValidatorInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ValidatorInterface.php)
- [`src/Ksfraser/FaBankImport/Import/Services/TransformerInterface.php`](src/Ksfraser/FaBankImport/Import/Services/TransformerInterface.php)
- [`src/Ksfraser/FaBankImport/Import/Services/DuplicateDetectorInterface.php`](src/Ksfraser/FaBankImport/Import/Services/DuplicateDetectorInterface.php)
- [`src/Ksfraser/FaBankImport/Import/Services/ReviewStagerInterface.php`](src/Ksfraser/FaBankImport/Import/Services/ReviewStagerInterface.php)
- [`src/Ksfraser/FaBankImport/Import/Services/OrchestratorInterface.php`](src/Ksfraser/FaBankImport/Import/Services/OrchestratorInterface.php)

### Exception Definitions
- [`src/Ksfraser/FaBankImport/Import/Exceptions/`](src/Ksfraser/FaBankImport/Import/Exceptions/) (15 exception types)

### DTOs
- [`src/Ksfraser/FaBankImport/Import/DTOs/ImportSessionDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ImportSessionDTO.php)
- [`src/Ksfraser/FaBankImport/Import/DTOs/ParsedStatementDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ParsedStatementDTO.php)
- [`src/Ksfraser/FaBankImport/Import/DTOs/ValidationResultDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ValidationResultDTO.php)
- [`src/Ksfraser/FaBankImport/Import/DTOs/ImportProgressDTO.php`](src/Ksfraser/FaBankImport/Import/DTOs/ImportProgressDTO.php)

### Handler Base
- [`src/Ksfraser/FaBankImport/Import/Handlers/BaseImportHandler.php`](src/Ksfraser/FaBankImport/Import/Handlers/BaseImportHandler.php)

### Utility Services (Already Implemented)
- [`src/Ksfraser/FaBankImport/Import/Services/BankAccountResolver.php`](src/Ksfraser/FaBankImport/Import/Services/BankAccountResolver.php)
- [`src/Ksfraser/FaBankImport/Import/Services/ChargeCalculator.php`](src/Ksfraser/FaBankImport/Import/Services/ChargeCalculator.php)
- [`src/Ksfraser/FaBankImport/Import/Services/TransactionStateManager.php`](src/Ksfraser/FaBankImport/Import/Services/TransactionStateManager.php)

### Planning Documents
- [`plan/phase2-import-pipeline-isolation.md`](plan/phase2-import-pipeline-isolation.md) - Full Phase 2 plan
- [`PHASE_0_IMPLEMENTATION_GUIDE.md`](PHASE_0_IMPLEMENTATION_GUIDE.md) - Phase 0 shared kernel patterns
- [`PHASE_0_IMPLEMENTATION_PLAN.md`](PHASE_0_IMPLEMENTATION_PLAN.md) - Phase 0 reference

---

## Next Steps

1. **Create implementation plan** using breakdown-plan skill
2. **Start with ParserFactory** (enables other services)
3. **Implement services in priority order**
4. **Write tests for each service** (50+ tests total)
5. **Verify orchestrator integration**
6. **Begin Phase 2.3 handlers** after Phase 2.2 complete
