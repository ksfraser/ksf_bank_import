---
goal: Implement Phase 2.2 Service Layer - Complete the core import pipeline with parser, validator, transformer, and orchestrator services
version: 1.0
date_created: 2026-04-04
last_updated: 2026-04-04
owner: KS Fraser
status: 'Planned'
tags: ['feature', 'phase-2-2', 'services', 'pipeline', 'architecture']
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

Phase 2.2 implements the core service layer for the bank import pipeline. This phase builds upon Phase 2.1 (foundation with DTOs, exceptions, interfaces) to create production-ready service implementations that handle file parsing, data validation, entity transformation, and pipeline orchestration.

**Scope**: 6 service implementations across Parser, Validator, Transformer, Orchestrator, DuplicateDetector, and ReviewStager ecosystems.

**Estimated Effort**: 21-26 hours across 2-3 implementation phases

**Key Deliverables**: 6 production service classes, 50+ unit tests, comprehensive error handling using new exception architecture

---

## 1. Requirements & Constraints

### Functional Requirements

- **REQ-001**: Parser implementations must detect file types and delegate to appropriate parsers
- **REQ-002**: Validator must enforce business rules without modifying data (immutability principle)
- **REQ-003**: Transformer must create domain entities from validated DTOs with type safety
- **REQ-004**: Orchestrator must coordinate the entire pipeline with rollback on failure
- **REQ-005**: DuplicateDetector must identify duplicate transactions using configurable matching strategies
- **REQ-006**: ReviewStager must manage transaction review workflow with persistence

### Exception Handling Requirements

- **EXC-001**: Parsers must throw specific exceptions: FileNotFoundException, UnsupportedFileTypeException, ParsingFailedException, EncodingMismatchException
- **EXC-002**: Validators must throw ValidationException with error collection
- **EXC-003**: Transformers must throw TransformException with entity/field context
- **EXC-004**: Orchestrator must catch all service exceptions and convert to pipeline exceptions
- **EXC-005**: Exception chaining required for root cause debugging

### Architecture Constraints

- **ARC-001**: All services must follow dependency injection pattern (constructor-based)
- **ARC-002**: Services must be stateless (no instance properties except dependencies)
- **ARC-003**: Services must implement their Phase 2.1 interfaces exactly (no additional methods)
- **ARC-004**: Must use shared exception namespace (Ksfraser\Exceptions\Utility)
- **ARC-005**: Must leverage existing services: BankAccountResolver, ChargeCalculator, TransactionStateManager

### Testing Requirements

- **TST-001**: Minimum 85% code coverage for all services
- **TST-002**: Each service must have unit tests covering success and all exception paths
- **TST-003**: Integration tests must verify orchestrator pipeline end-to-end
- **TST-004**: Test data must include edge cases (empty files, encoding issues, duplicates)

### Design Constraints

- **DES-001**: Immutable input data (no side effects on parameters)
- **DES-002**: Fail-fast error detection (parse errors reported immediately)
- **DES-003**: Type-safe exception catching (specific exception types, not string matching)
- **DES-004**: No database transactions in service layer (transactional wrapper responsibility)

### Dependency Constraints

- **DEP-001**: Phase 2.1 interfaces and DTOs already exist and must be used as-is
- **DEP-002**: ParserFactory depends on concrete parser implementations
- **DEP-003**: Orchestrator depends on all 5 other services
- **DEP-004**: Concrete parsers depend on file detection library

---

## 2. Implementation Steps

### Phase 2.2.1: Parser Implementation (Hours 0-6)

**GOAL-P1**: Implement ParserFactory and base parser implementations for CSV and OFX file formats

#### TASK-P1-001: Create CSV Parser Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/CsvParser.php`
- **Interface**: Implement `ParserInterface` with:
  - `parse(string $filePath, array $options = []): array` - Main entry point
  - `getSupportedTypes(): array` - Return ['text/csv', 'application/csv']
  - `getName(): string` - Return 'CSV Parser'
- **Exceptions to throw**:
  - `FileNotFoundException::create()` if file not found
  - `UnsupportedFileTypeException::create()` if not CSV file
  - `EncodingMismatchException::create()` if encoding issues detected
  - `ParsingFailedException::withLineContent()` for parse errors with line numbers
- **Processing Logic**:
  - Validate file exists and is readable
  - Detect encoding (UTF-8, ISO-8859-1, UTF-16)
  - Parse CSV header row
  - Validate required columns: account, valueTimestamp, transactionAmount, transactionTitle
  - Parse data rows with line number tracking
  - Return normalized array matching ParsedStatementDTO structure
- **Dependencies**: PHP League CSV library, EncodingDetector
- **Test Coverage**: 85% minimum
  - Success: Valid CSV file parsing
  - Error: Missing file, invalid format, encoding mismatch, missing columns, corrupted data

#### TASK-P1-002: Create OFX Parser Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/OFXParser.php`
- **Interface**: Implement `ParserInterface` with same signature
- **Supported MIME Types**: ['application/vnd.intu.qbo', 'x-ofx', 'application/x-ofx']
- **Processing Logic**:
  - Validate OFX headers
  - Parse STMTRS response blocks
  - Extract STMTTRN transaction records
  - Map OFX fields to ParsedStatementDTO structure
  - Handle different OFX versions (1.0, 2.0)
- **Exceptions**: Same as CsvParser
- **Dependencies**: OFX parser library (or implement custom parser)
- **Test Coverage**: 85% minimum

#### TASK-P1-003: Create ParserFactory Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/ParserFactory.php`
- **Interface**: Implement `ParserFactoryInterface` with:
  - `create(string $filePath): ParserInterface` - Factory method
  - `getAvailableParsers(): array` - Return available parser instances
  - `detectFileType(string $filePath): string` - Detect MIME type
- **Exceptions to throw**:
  - `FileNotFoundException::create()` if file not found
  - `UnsupportedFileTypeException::create()` if no parser available for type
- **Implementation Details**:
  - Use Finfo or file command to detect MIME type
  - Maintain registry of ParserInterface implementations
  - Return appropriate parser instance based on MIME type
  - Support file extension fallback if MIME detection fails
- **Dependencies**: CsvParser, OFXParser instances, Finfo extension
- **Test Coverage**: 85% minimum

#### TASK-P1-004: Create ParserFactory Tests
- **File Path**: `tests/Unit/Services/Parsers/ParserFactoryTest.php`
- **Test Cases**:
  - ✓ Factory creates CSV parser for CSV files
  - ✓ Factory creates OFX parser for OFX files
  - ✓ Factory throws FileNotFoundException for missing files
  - ✓ Factory throws UnsupportedFileTypeException for unknown types
  - ✓ getAvailableParsers returns all registered parsers
  - ✓ detectFileType returns correct MIME for known files
  - ✓ Extension fallback works when MIME detection unavailable

---

### Phase 2.2.2: Validator & Transformer Implementation (Hours 6-13)

**GOAL-P2**: Implement business rule validation and entity transformation

#### TASK-P2-001: Create Validator Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Validators/StatementValidator.php`
- **Interface**: Implement `ValidatorInterface` with:
  - `validate(ParsedStatementDTO $dto): ValidationResultDTO` - Main validation entry
- **Business Rules to Validate**:
  - All required DTO fields present and non-empty
  - Account number format valid (alphanumeric, no spaces)
  - Statement dates valid (start < end, dates in reasonable range)
  - Currency code is valid ISO 4217 code (if present)
  - Transaction amounts within reasonable bounds (not zero, not extreme)
  - Transaction timestamps within statement date range
  - No duplicate transaction codes within same account/date
- **Exceptions to throw**:
  - `ValidationException::error()` for each validation failure
  - `ValidationException::missingFields()` for required fields
  - `ValidationException::invalidFields()` for bad format/type
- **Implementation Details**:
  - Use dedicated validator rules (separate classes for each rule)
  - Collect ALL errors before throwing (don't fail-fast on first error)
  - Return ValidationResultDTO with errors and warnings
  - Support rule configuration (optional rules, severity levels)
- **Dependencies**: None (immutable input)
- **Test Coverage**: 85% minimum
  - Valid statement passes all rules
  - Each business rule violation detected
  - Multiple errors collected in single result
  - Warnings vs. errors properly categorized

#### TASK-P2-002: Create Transformer Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Transformers/BiStatementTransformer.php`
- **Interface**: Implement `TransformerInterface` with:
  - `transform(ParsedStatementDTO $dto): BiStatement` - Transform to domain entity
- **Transformation Logic**:
  - Map ParsedStatementDTO fields to BiStatement entity
  - Create BankAccountMapping from account data
  - Calculate statement totals and checksums
  - Assign transaction sequence numbers
  - Set import metadata (timestamp, source, version)
- **Exceptions to throw**:
  - `TransformException::entityCreationFailed()` if entity construction fails
  - `TransformException::typeMismatch()` if field type conversion fails
  - `TransformException::missingRequiredData()` if required entity fields cannot be populated
- **Implementation Details**:
  - Validate all field conversions are lossless
  - Preserve audit trail (original values accessible)
  - Support entity versioning
  - Handle legacy account format to modern format conversion
- **Dependencies**: BiStatement entity, BankAccountMapping entity, ChargeCalculator service
- **Test Coverage**: 85% minimum
  - Successful transformation to BiStatement
  - All DTO fields properly mapped
  - Type conversions verified
  - Exception thrown on invalid data

#### TASK-P2-003: Create Transaction Transformer
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Transformers/BiTransactionTransformer.php`
- **Interface**: Implement `TransformerInterface` for transactions
- **Transformation Logic**:
  - Map transaction row to BiTransaction entity
  - Parse transaction reference (transactionCode)
  - Calculate debit/credit amounts
  - Assign appropriate transaction type
  - Link to parent statement
- **Exceptions**: Same as BiStatementTransformer
- **Dependencies**: BiTransaction entity, ChargeCalculator
- **Test Coverage**: 85% minimum

#### TASK-P2-004: Create Validator & Transformer Tests
- **Files**:
  - `tests/Unit/Services/Validators/StatementValidatorTest.php` (12 test cases)
  - `tests/Unit/Services/Transformers/BiStatementTransformerTest.php` (14 test cases)
  - `tests/Unit/Services/Transformers/BiTransactionTransformerTest.php` (10 test cases)
- **Each test file covers**: Success path, all exception paths, edge cases

---

### Phase 2.2.3: Orchestrator & Review Services Implementation (Hours 13-21)

**GOAL-P3**: Implement pipeline orchestration and review management

#### TASK-P3-001: Create Orchestrator Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/Orchestrators/ImportOrchestrator.php`
- **Interface**: Implement `OrchestratorInterface` with:
  - `executeImportPipeline(string $filePath, array $options = []): ImportResult` - Main pipeline
- **Pipeline Flow** (orchestrated):
  1. **Parse Phase**: ParserFactory → Parser → ParsedStatementDTO
     - Exception handling: FileNotFoundException, UnsupportedFileTypeException, ParsingFailedException, EncodingMismatchException
  2. **Validate Phase**: Validator → ValidationResultDTO
     - Exception handling: ValidationException with error rollup
  3. **Transform Phase**: StatementTransformer → BiStatement
     - Exception handling: TransformException
  4. **Review Phase**: ReviewStager → ReviewSession
     - Exception handling: DuplicateDetectedException
  5. **Persist Phase**: Repository store (outside orchestrator)
- **State Management**:
  - Track ImportSessionDTO through pipeline
  - Record elapsed time at each phase
  - Maintain error context for debugging
- **Rollback Strategy**:
  - On any service exception, collect and return failure result
  - Do NOT commit to database on exception
  - Support retry with recovered state
- **Exceptions to throw**:
  - Aggregate any service exception into `ImportException` with context
  - Preserve exception chain (use exception chaining with `$previous`)
- **Dependencies**: ParserFactory, Validator, Transformers, DuplicateDetector, ReviewStager, BankAccountResolver
- **Test Coverage**: 85% minimum
  - Happy path: successful end-to-end import
  - Parser exception propagates
  - Validator exception propagates with error recovery
  - Transformer exception propagates
  - Duplicate detection blocks import
  - Partial success with warnings

#### TASK-P3-002: Create DuplicateDetector Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateMatcher.php`
- **Interface**: Implement `DuplicateDetectorInterface` with:
  - `findDuplicates(BiTransaction $transaction): array` - Find all duplicates
  - `isDuplicate(BiTransaction $transaction, BiTransaction $existing): bool` - Check single duplicate
- **Matching Strategies**:
  - **Exact Match**: Same account + code + amount + date
  - **Fuzzy Match**: Similar amount (±0.01%) + similar date (±2 days) + same account
  - **Pattern Match**: Configurable rules for institution-specific patterns
- **Exception Handling**:
  - Throw `DuplicateDetectedException::exactDuplicate()` on exact match
  - Throw `DuplicateDetectedException::fuzzyDuplicate()` on fuzzy match
  - Support `DuplicateDetectedException::flagForReview()` for uncertain matches
- **Implementation Details**:
  - Query transaction repository efficiently
  - Support configurable matching thresholds
  - Preserve matched transaction references
- **Dependencies**: TransactionRepository, DuplicateMatchingRules
- **Test Coverage**: 85% minimum

#### TASK-P3-003: Create ReviewStager Implementation
- **File Path**: `src/Ksfraser/FaBankImport/Import/Services/ReviewStaging/ReviewStager.php`
- **Interface**: Implement `ReviewStagerInterface` with:
  - `stageForReview(BiTransaction $transaction, string $reason): ReviewSession` - Stage transaction
  - `getReviewQueue(int $limit = 250): array` - Get pending reviews
  - `markResolved(int $transactionId, string $resolution): bool` - Complete review
- **Review Reasons**:
  - DUPLICATE_DETECTED - Possible duplicate found
  - VALIDATION_WARNING - Non-fatal validation issue
  - MANUAL_OVERRIDE - User requested review
  - POLICY_VIOLATION - Business rule violation
- **Exception Handling**:
  - Throw `DuplicateDetectedException::flagForReview()` for duplicates
  - Throw `ValidationException` if transaction cannot be staged
- **Implementation Details**:
  - Persist staging to database
  - Track review history and audit trail
  - Support review session state machine (pending → reviewed → resolved)
- **Dependencies**: Database, AuditLog service
- **Test Coverage**: 85% minimum

#### TASK-P3-004: Create Orchestrator & Review Tests
- **Files**:
  - `tests/Integration/Services/Orchestrators/ImportOrchestratorTest.php` (16 test cases)
  - `tests/Unit/Services/DuplicateDetection/DuplicateMatcherTest.php` (12 test cases)
  - `tests/Unit/Services/ReviewStaging/ReviewStagerTest.php` (10 test cases)
- **Integration test coverage**:
  - End-to-end import workflow (parser → validator → transformer → persist)
  - Exception handling at each pipeline stage
  - Rollback on failure
  - State recovery and retry scenarios

---

## 3. Implementation Order & Dependencies

### Critical Path (Sequential)

1. **CsvParser** (no dependencies) → Enables ParserFactory testing
2. **ParserFactory** (depends: CsvParser) → Enables Orchestrator testing
3. **StatementValidator** (no dependencies) → Enables pipeline validation
4. **BiStatementTransformer** (depends: Validator, ChargeCalculator) → Enables transformation
5. **ImportOrchestrator** (depends: all above) → Core pipeline
6. **DuplicateMatcher** (depends: TransactionRepository) → Parallel with Orchestrator
7. **ReviewStager** (depends: Database schema) → Parallel with Orchestrator

### Parallelizable Tasks

- CsvParser + OFXParser can be implemented in parallel (no inter-dependencies)
- StatementValidator + BiStatementTransformer can be parallelized
- DuplicateMatcher + ReviewStager can be parallelized after Orchestrator

---

## 4. Testing Strategy

### Unit Test Requirements

| Component | Test Cases | Coverage | Status |
|-----------|-----------|----------|--------|
| CsvParser | 9 | 85% | Planned |
| OFXParser | 9 | 85% | Planned |
| ParserFactory | 7 | 85% | Planned |
| StatementValidator | 12 | 85% | Planned |
| BiStatementTransformer | 14 | 85% | Planned |
| BiTransactionTransformer | 10 | 85% | Planned |
| DuplicateMatcher | 12 | 85% | Planned |
| ReviewStager | 10 | 85% | Planned |
| **TOTAL** | **83 unit tests** | **85% avg** | **Planned** |

### Integration Test Requirements

| Scenario | Test Cases | Status |
|----------|-----------|--------|
| End-to-end import with valid file | 4 | Planned |
| Exception handling and rollback | 5 | Planned |
| Duplicate detection | 3 | Planned |
| Review staging workflow | 4 | Planned |
| **TOTAL** | **16+ integration tests** | **Planned** |

### Test Data Requirements

- Valid CSV file with 50+ transactions
- Valid OFX file with example data
- Corrupted/invalid files (encoding issues, missing columns, syntax errors)
- Files with duplicate transactions
- Files with boundary case amounts and dates

---

## 5. Success Criteria

### Code Quality

- ✅ All 6 services implement their Phase 2.1 interfaces exactly
- ✅ 85%+ code coverage for all services
- ✅ No public methods beyond interface contract
- ✅ All dependencies injected via constructor
- ✅ All service methods are stateless (no instance state except dependencies)
- ✅ Zero code duplication across services
- ✅ Full phpDoc documentation on all public methods

### Exception Handling

- ✅ All exceptions caught and converted to specific types
- ✅ Exception chains preserved (using `$previous` parameter)
- ✅ Root cause always traceable in exception stack
- ✅ All 8 exception types used appropriately (no generic exceptions)
- ✅ Each service throws only its declared exception types (per interface)

### Functional Correctness

- ✅ ParserFactory correctly detects and routes file types
- ✅ Validator enforces all 8+ business rules
- ✅ Transformer creates valid domain entities
- ✅ Orchestrator completes full pipeline without errors on valid input
- ✅ Duplicate detection identifies all matching patterns
- ✅ Review staging persists and retrieves correctly

### Testing Coverage

- ✅ All 83+ unit tests passing
- ✅ All 16+ integration tests passing
- ✅ Phase2_1_FoundationTest still passes (26/26)
- ✅ No test failures or warnings
- ✅ Test coverage >= 85% for all services

### Git Compliance

- ✅ All changes committed with semantic commit messages
- ✅ Branch: `feature/phase-2-2-service-implementations` (new feature branch)
- ✅ PRs created for code review (recommended)
- ✅ Tags created: v2.2.0 on completion

---

## 6. Rollout Plan

### Phase 2.2.1 Rollout (Parser Implementation)

1. Create branch: `feature/phase-2-2-parser-implementations`
2. Implement CsvParser, OFXParser, ParserFactory (6 hours)
3. Create unit tests (9+7+7 = 23 test cases)
4. Verify ParserFactory tests pass
5. Commit: `feat(parsers): implement parser factory and CSV/OFX parsers`
6. PR review and merge

### Phase 2.2.2 Rollout (Validator & Transformer)

1. Create branch: `feature/phase-2-2-validator-transformer` (or reuse feature branch)
2. Implement StatementValidator, BiStatementTransformer, BiTransactionTransformer (7 hours)
3. Create unit tests (12+14+10 = 36 test cases)
4. Verify all tests pass + 85%+ coverage
5. Commit: `feat(validators): implement statement validation service`
6. Commit: `feat(transformers): implement statement and transaction transformers`

### Phase 2.2.3 Rollout (Orchestrator & Review)

1. Implement ImportOrchestrator, DuplicateMatcher, ReviewStager (8 hours)
2. Create integration tests (16+ test cases)
3. Run Phase2_1_FoundationTest to verify backward compatibility
4. Verify all 99+ tests pass + 85%+ coverage
5. Commit: `feat(orchestrator): implement import pipeline orchestrator`
6. Commit: `feat(duplicates): implement duplicate detection matcher`
7. Commit: `feat(review): implement review staging service`
8. Create tag: `v2.2.0`
9. Merge to `chore/phase-0-shared-kernel` branch

---

## 7. Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|-----------|
| Parser library incompatibility | Medium | High | Evaluate libraries before starting; have backup implementation |
| Test data gaps | Medium | Medium | Create comprehensive test data set early; peer review |
| Exception misuse | Medium | Medium | Strict code review; automated exception type checking |
| Performance issues | Low | Medium | Profile with large file sets; optimize after implementation |
| Database schema mismatch | Medium | High | Verify schema matches entity definitions; migration tests |

---

## 8. Next Phase Dependency

**Phase 2.3**: Handler Implementations
- Depends on Phase 2.2 service implementations
- Will implement: UploadFormHandler, FileImportHandler, ValidationHandler, ReviewHandler, DeliveryHandler, CompletionHandler
- Estimated start: After Phase 2.2.3 completion and v2.2.0 release

---

## 9. Reference Materials

- Phase 2.1 Foundation: `PHASE_2_1_FOUNDATION_FOUNDATION.md`
- Exception Hierarchy: `src/Ksfraser/Exceptions/Utility/` (4 specific parser exception types)
- Interface Contracts: `src/Ksfraser/FaBankImport/Import/Services/*Interface.php`
- Entity Models: `src/Ksfraser/FaBankImport/Shared/Entities/`
- DTO Models: `src/Ksfraser/FaBankImport/Import/DTOs/`

