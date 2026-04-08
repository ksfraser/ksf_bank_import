---
goal: Phase 2.2.2 - Implement Statement Validators and Transaction Transformers
version: 1.0
date_created: 2026-04-04
last_updated: 2026-04-04
owner: KS Fraser
status: 'Planned'
tags: ['phase-2-2-2', 'validators', 'transformers', 'business-logic', 'dto-mapping']
---

# Phase 2.2.2: Statement Validators & Transaction Transformers

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This phase implements business logic validation and DTO-to-entity transformation services. It builds on Phase 2.2.1 (parsers) to provide data quality assurance and normalization before entity persistence.

## 1. Requirements & Constraints

### Functional Requirements
- **REQ-001**: StatementValidator must validate 7+ business rules (date ranges, amounts, duplicates, formats, constraints)
- **REQ-002**: Validators must collect all errors without throwing (return ValidationResult with error array)
- **REQ-003**: BiStatementTransformer must map ParsedStatementDTO → BiStatement entity with enrichment
- **REQ-004**: BiTransactionTransformer must map transaction array → BiTransaction entities with amount/date normalization
- **REQ-005**: EnrichmentService must inject metadata (exchange rates, bank data, categories)
- **REQ-006**: QualityScorer must generate 0-100 confidence score based on missing fields and format compliance
- **REQ-007**: NormalizationRules must standardize text case, date formats, and amount rounding
- **REQ-008**: TransformerFactory must route parser type → correct transformer with configuration injection
- **REQ-009**: All transformers must implement TransformerInterface (transform, supports, getName methods)
- **REQ-010**: All validators must use 4 existing parser exceptions (FileNotFoundException, UnsupportedFileTypeException, ParsingFailedException, EncodingMismatchException)

### Non-Functional Requirements
- **PERF-001**: All validation and transformation operations must complete in <100ms per statement
- **MEM-001**: TransformerFactory must be singleton (stateless, reusable)
- **TEST-001**: Achieve 85%+ code coverage across all validator/transformer services
- **ARCH-001**: All transformers must be dependency-injected (constructor injection only)

### Constraints
- **CON-001**: Cannot modify existing Parser services (Phase 2.2.1)
- **CON-002**: Cannot modify existing DTOs (use as-is from Phase 2.1)
- **CON-003**: Cannot modify existing BiStatement/BiTransaction entities (use existing mappers)
- **CON-004**: Must use existing Exception classes from ksfraser/exceptions package
- **CON-005**: Must maintain 36/36 Phase 2.1 foundation tests passing (no regressions)

### Guidelines
- **GUD-001**: Follow ParserInterface pattern for TransformerInterface (consistent API)
- **GUD-002**: Validators return ValidationResult object (no exceptions for validation failures)
- **GUD-003**: Use fluent API for optional enrichment configuration where applicable
- **GUD-004**: Document all business rules with examples in docblocks

## 2. Implementation Phases

### Phase 2.2.2.1: Core Validator Services (5 hours)

**GOAL**: Implement StatementValidator service with 7+ business rule checks

**Tasks** (can execute in parallel):

| TASK | File | Implementation Details | Output | Est. Time |
|------|------|------------------------|--------|-----------|
| TASK-001 | `src/Ksfraser/FaBankImport/Import/Validators/StatementValidator.php` | Create validator service with constructor dependency injection (optional logger). Implement `validate(ParsedStatementDTO $statement): ValidationResult` method. Add 7 rule check methods: `validateDateRange()`, `validateAmounts()`, `validateMerchantDetails()`, `validateDuplicateDetection()`, `validateCurrencyFormat()`, `validateAccountReference()`, `validateTransactionCount()`. Each rule should append to $errors array; collect all and return ValidationResult with success flag + errors array. | 350 lines class | 2h |
| TASK-002 | `src/Ksfraser/FaBankImport/Import/Results/ValidationResult.php` (if not exists) | Create immutable result DTO with properties: success (bool), errors (array of string), warnings (array of string), validatedAt (DateTime), rulesSummary (array of rule → status). Include methods: isValid(), hasErrors(), hasWarnings(), getErrorCount(), getWarning Count(). | 120 lines class | 1h |
| TASK-003 | `tests/Unit/Validators/StatementValidatorTest.php` | Create comprehensive test file with: valid statement test, invalid date range test, missing amount test, invalid merchant test, duplicate detection test, currency mismatch test, account reference missing test, multiple rule violations test, edge case (empty transaction) test. Minimum 12 test methods + helper methods for building test statements. | 450 lines test file | 1.5h |
| TASK-004 | `docs/STATEMENT_VALIDATOR_RULES.md` | Document all 7 business rules with: rule name, description, acceptance criteria, rejection criteria, example valid input, example invalid input. Include bank-specific constraint matrix. | 300 lines doc | 0.5h |

**Completion Criteria**:
- StatementValidator created with all 7 rule methods implemented
- ValidationResult DTO created with all required properties and methods
- All 12+ StatementValidatorTest methods passing (0 failures)
- Phase 2.1 foundation tests still 36/36 passing
- No syntax errors detected (php -l passes)

---

### Phase 2.2.2.2: Transformation Services (7 hours)

**GOAL**: Implement BiStatementTransformer and BiTransactionTransformer

**Dependencies**: Requires Phase 2.2.2.1 complete (need ValidationResult class)

**Tasks**:

| TASK | File | Implementation Details | Output | Est. Time |
|------|------|------------------------|--------|-----------|
| TASK-005 | `src/Ksfraser/FaBankImport/Import/Services/Transformers/TransformerInterface.php` | Create interface with methods: `transform($dto)`, `canTransform($dto)`, `getTransformationType()`. Pattern consistent with ParserInterface. | 40 lines interface | 0.5h |
| TASK-006 | `src/Ksfraser/FaBankImport/Import/Services/Transformers/BiStatementTransformer.php` | Create transformer with DI: optional EnrichmentService, optional NormalizationRules, StatementValidator. Implement `transform(ParsedStatementDTO): BiStatement` method. Handle: currency mapping, account reference extraction, statement date normalization, transaction collection mapping. Validate statement before transformation; throw ParsingFailedException if invalid. Enrich with bank metadata if EnrichmentService injected. | 280 lines class | 2.5h |
| TASK-007 | `src/Ksfraser/FaBankImport/Import/Services/Transformers/BiTransactionTransformer.php` | Create transformer with DI: optional NormalizationRules, optional QualityScorer. Implement `transformBatch(array $transactions): array` method. For each transaction: normalize amount (trim decimals, standardize signs), parse date (handle multiple formats), extract reference/memo, calculate confidence score if QualityScorer available. Return array of BiTransaction entities. Handle edge cases: missing amount, invalid date, empty reference. | 320 lines class | 2.5h |
| TASK-008 | `src/Ksfraser/FaBankImport/Import/Services/Transformers/NormalizationRules.php` | Create rules service with static methods: `normalizeAmount($value, $decimals = 2)`, `normalizeDate($value, $format = 'Y-m-d')`, `normalizeText($value)`, `normalizeCurrency($code)`. Handle: currency scaling (1000 → 1.00 if needed), date parsing (M/D/Y, D/M/Y, YYYY-MM-DD formats), text casing (title case for merchants, uppercase for currencies), amount rounding (banker's rounding). | 180 lines class | 1.5h |
| TASK-009 | `tests/Unit/Services/Transformers/BiStatementTransformerTest.php` | Create test file: valid transformation test, with enrichment test, validation failure handling test, currency mapping test, account reference extraction test, transaction collection mapping test, missing optional data test. Minimum 10 test methods. | 380 lines test file | 1.5h |
| TASK-010 | `tests/Unit/Services/Transformers/BiTransactionTransformerTest.php` | Create test file: valid transaction batch test, amount normalization test, date format conversion test, reference extraction test, quality score calculation test, edge case (empty values) test, batch processing test. Minimum 10 test methods. | 350 lines test file | 1.5h |

**Completion Criteria**:
- TransformerInterface created and properly implemented
- BiStatementTransformer converts ParsedStatementDTO → BiStatement correctly
- BiTransactionTransformer normalizes and converts transaction arrays
- NormalizationRules handles all supported formats
- All 20+ transformation tests passing
- Phase 2.1 foundation tests still 36/36 passing

---

### Phase 2.2.2.3: Enrichment & Quality Services (5 hours)

**GOAL**: Implement EnrichmentService, QualityScorer, and routing factory

**Dependencies**: Requires Phase 2.2.2.2 complete (transformers need these services)

**Tasks**:

| TASK | File | Implementation Details | Output | Est. Time |
|------|------|------------------------|--------|-----------|
| TASK-011 | `src/Ksfraser/FaBankImport/Import/Services/Enrichment/EnrichmentService.php` | Create service with DI: optional ExchangeRateProvider, optional BankMetadataProvider. Implement `enrich(BiStatement $statement): BiStatement` method. Support enrichment for: exchange rates (lookup conversion rate for foreign currencies), bank metadata (bank name, SWIFT code, contact info from statement account identifier), merchant category inference (from merchant name patterns). Make each enrichment optional (skip if provider not injected). | 220 lines class | 1.5h |
| TASK-012 | `src/Ksfraser/FaBankImport/Import/Services/Quality/QualityScorer.php` | Create scorer with scoring rules: 100 points base, -5 per missing optional field, -2 per format inconsistency, -1 per truncated value. Implement `scoreStatement(BiStatement): int` method (returns 0-100). Include helper methods: `getFieldCompleteness()`, `getFormatCompliance()`, `getConfidenceRating()` (returns 'high'/'medium'/'low' based on score). | 180 lines class | 1.5h |
| TASK-013 | `src/Ksfraser/FaBankImport/Import/Services/Transformers/TransformerFactory.php` | Create factory service with DI: all transformer instances passed in constructor. Implement `create(string $parserType): TransformerInterface` method. Route by parser type: 'csv' → BiStatementTransformer, 'ofx' → OFXStatementTransformer (if exists), 'qif' → QIFStatementTransformer (if exists). Throw UnsupportedFileTypeException if parser type unsupported. Include `getAvailableTransformers()` method returning list of transformer info. | 150 lines class | 1.5h |
| TASK-014 | `tests/Unit/Services/Enrichment/EnrichmentServiceTest.php` | Create test file: enrich with exchange rate test, enrich with bank metadata test, partial enrichment test (one provider missing), no enrichment test (no providers). Minimum 6 test methods. | 200 lines test file | 1h |
| TASK-015 | `tests/Unit/Services/Quality/QualityScorerTest.php` | Create test file: perfect statement score test (100), missing fields score test, format inconsistency test, confidence rating high/medium/low tests. Minimum 8 test methods. | 220 lines test file | 1h |
| TASK-016 | `tests/Unit/Services/Transformers/TransformerFactoryTest.php` | Create test file: create CSV transformer test, create OFX transformer test (if implemented), unsupported type exception test, get available transformers test. Minimum 6 test methods. | 180 lines test file | 0.75h |

**Completion Criteria**:
- EnrichmentService optionally enriches statements with metadata
- QualityScorer generates 0-100 confidence scores
- TransformerFactory correctly routes by parser type
- All 20+ enrichment/quality/factory tests passing
- Phase 2.1 foundation tests still 36/36 passing

---

### Phase 2.2.2.4: Integration & Verification (4 hours)

**GOAL**: Validate all services working together, achieve 85%+ coverage, prepare for commit

**Tasks**:

| TASK | File | Implementation Details | Output | Est. Time |
|------|------|------------------------|--------|-----------|
| TASK-017 | `tests/Integration/Parsers2TransformersTest.php` | Create integration test file showing flow: ParsedStatementDTO (from parser) → validate (StatementValidator) → transform (BiStatementTransformer) → enrich → score. Include: end-to-end CSV→BiStatement flow, error handling (invalid data), multiple transaction types. Minimum 6 integration tests. | 300 lines test file | 1.5h |
| TASK-018 | Run test suite | Execute `php ./vendor/bin/phpunit tests/Unit/Services/Transformers/ tests/Unit/Validators/ tests/Unit/Services/Enrichment/ tests/Unit/Services/Quality/ --configuration phpunit.xml --coverage`. Verify: all tests passing, 85%+ line coverage per service, 0 Phase 2.1 regressions. | Test Results HTML | 1h |
| TASK-019 | Syntax validation | Run `php -l` on all new service files. Verify: no syntax errors, all classes autoloadable, no missing dependencies. | Verde/No Errors | 0.5h |
| TASK-020 | Documentation updates | Update: service README section, architecture decision record (ADR) for transformer pattern, Phase 2.2.2 completion checklist. | 3 docs | 1h |

**Completion Criteria**:
- All Phase 2.2.2 tests passing (50+ test methods total)
- 85%+ code coverage across all new services
- Phase 2.1 foundation tests still 36/36 passing (zero regressions)
- All new classes have proper docblocks and type hints
- Ready for Phase 2.2.2 commit

---

---

## 3. Phase 2.2.3: Data Integrity Architecture Refactoring (FUTURE - 18 hours)

**Status**: Proposed (after Phase 2.2.2 complete)  
**Note**: Documented here for planning purposes; executed AFTER Phase 2.2.2.2 transformers are committed

**GOAL**: Refactor validators to extract composable `RuleInterface` pattern for reusability across validation contexts (ParsedStatement, BiStatement, BiTransaction, Archive)

**Dependencies**: Requires Phase 2.2.2 complete (StatementValidator exists, ValidationResult enhanced)

**Architecture Decision**: See [ADR-0001: Composable Data Validation Rules](../docs/adr/adr-0001-composable-data-validation-rules.md)

**Tasks**:

| TASK | File | Implementation Details | Output | Est. Time |
|------|------|------------------------|--------|-----------|
| TASK-021 | `src/Ksfraser/FaBankImport/Import/Rules/RuleInterface.php` | Create base interface with methods: `execute($context): RuleResult`, `getName(): string`, `getDescription(): string`. Result object: rule name, passed (bool), error message (if failed), severity (error/warning/info). | 50 lines interface | 0.5h |
| TASK-022 | `src/Ksfraser/FaBankImport/Import/Rules/ParsedStatementRules/` (directory) | Extract 7 rules from StatementValidator into individual classes implementing `ParsedStatementRule` (extends RuleInterface). Each file: ~60-80 lines + docblock. Files: DateRangeRule.php, AmountValidationRule.php, MerchantDetailsRule.php, TransactionCountRule.php, AccountReferenceRule.php, CurrencyFormatRule.php, DuplicateDetectionRule.php. | 7 rule classes | 3.5h |
| TASK-023 | `src/Ksfraser/FaBankImport/Import/Rules/BiStatementRules/` (directory) | Create new rules for database-backed BiStatement validation: BankAccountConsistencyRule.php, TransactionIntegrityRule.php, ImportAuditTrailRule.php, ArchiveConsistencyRule.php. Each validates constraints that don't apply to raw ParsedStatement. | 4 rule classes | 2h |
| TASK-024 | `src/Ksfraser/FaBankImport/Import/Rules/RuleRegistry.php` | Create registry service with methods: `register(string $ruleName, RuleInterface $rule)`, `getRuleByName(string $name): RuleInterface`, `getRulesByContext(string $context): array`, `getPreset(string $profileName): array`. Support presets: 'audit' (all rules), 'strict' (high-priority rules only), 'relaxed' (minimal checks). | 180 lines class | 1.5h |
| TASK-025 | `src/Ksfraser/FaBankImport/Import/Validators/ComposableValidator.php` | Create generic validator using RuleRegistry. Implement: `validate($context, RuleRegistry $registry): ValidationResult`. Accepts rule list (or preset), executes all, collects results. | 150 lines class | 1h |
| TASK-026 | `src/Ksfraser/FaBankImport/Import/Validators/BiStatementIntegrityValidator.php` | Create validator for database-layer (BiStatement) validation using ComposableValidator pattern. Apply BiStatement-specific rules (consistency, audit trail). Used in post-import integrity checks. | 100 lines class | 1h |
| TASK-027 | `tests/Unit/Rules/ParsedStatementRules/DateRangeRuleTest.php` (+ 6 more) | Create individual test files for each extracted rule. Each file: ~80 lines with 2-3 test methods. Total 14-21 test methods across 7 files. | 7 test files | 3.5h |
| TASK-028 | `tests/Unit/Rules/BiStatementRules/BankAccountConsistencyRuleTest.php` (+ 3 more) | Create test files for new BiStatement rules. 4 test files, 8-12 test methods total. | 4 test files | 2h |
| TASK-029 | `tests/Unit/Validators/ComposableValidatorTest.php` | Create test: rule registry injection, preset selection, all rules passed, partial failures, warning vs. error distinction, multiple rule execution. Minimum 10 test methods. | 250 lines test file | 1.5h |
| TASK-030 | `docs/RULE_REGISTRY_CONFIGURATION.md` | Document: rule registry pattern, preset configurations (audit/strict/relaxed), how to add custom rules, examples of rule composition for different use cases. | 300 lines doc | 1.5h |

**Completion Criteria**:
- All 7 parsed statement rules extracted to individual RuleInterface implementations
- 4 new BiStatement rules created (new validation layer)
- RuleRegistry supports ≥10 rules with preset selection
- ComposableValidator can run rules independently or via preset
- All 30+ new rule tests passing
- Phase 2.1 foundation tests still 36/36 passing
- Phase 2.2.2.2 transformers unchanged (backward compatible)

**Timeline**: 18 hours total (approximately 1.5 days after Phase 2.2.2 complete)

---

## 3. Implementation Order & Dependencies

```
Phase 2.2.2.1 (Validators)
  ├─ TASK-001: StatementValidator service
  ├─ TASK-002: ValidationResult DTO
  ├─ TASK-003: StatementValidator tests
  └─ TASK-004: Business rules documentation
        ↓ (dependencies ready)
Phase 2.2.2.2 (Transformers)
  ├─ TASK-005: TransformerInterface
  ├─ TASK-006: BiStatementTransformer
  ├─ TASK-007: BiTransactionTransformer
  ├─ TASK-008: NormalizationRules
  ├─ TASK-009: BiStatementTransformer tests
  └─ TASK-010: BiTransactionTransformer tests
        ↓ (dependencies ready)
Phase 2.2.2.3 (Enrichment & Quality)
  ├─ TASK-011: EnrichmentService (parallel with TASK-012)
  ├─ TASK-012: QualityScorer (parallel with TASK-011)
  ├─ TASK-013: TransformerFactory
  ├─ TASK-014: EnrichmentService tests
  ├─ TASK-015: QualityScorer tests
  └─ TASK-016: TransformerFactory tests
        ↓ (dependencies ready)
Phase 2.2.2.4 (Integration & Verification)
  ├─ TASK-017: Integration tests
  ├─ TASK-018: Test suite execution & coverage
  ├─ TASK-019: Syntax validation
  └─ TASK-020: Documentation updates
        ↓ (Phase 2.2.2 COMMITTED)
Phase 2.2.3 (Data Integrity Refactoring - FUTURE)
  ├─ TASK-021: RuleInterface base
  ├─ TASK-022: Extract 7 ParsedStatement rules
  ├─ TASK-023: Create 4 BiStatement rules (new validation layer)
  ├─ TASK-024: RuleRegistry factory + presets
  ├─ TASK-025: ComposableValidator generic orchestrator
  ├─ TASK-026: BiStatementIntegrityValidator application
  ├─ TASK-027: ParsedStatement rule tests (7 test files)
  ├─ TASK-028: BiStatement rule tests (4 test files)
  ├─ TASK-029: ComposableValidator tests
  └─ TASK-030: Rule registry configuration documentation
        ↓ (Phase 2.2.3 COMMITTED)
```

### Independence Confirmation

**Key Finding**: Validators (Phase 2.2.2.1) and Transformers (Phase 2.2.2.2) are **INDEPENDENT**:

```
Parser Output (ParsedStatementDTO)
  │
  ├─→ [OPTIONAL] StatementValidator.validate() → ValidationResult (informational)
  │                                                   ↓ (used for audit/logging only)
  │
  └─→ BiStatementTransformer.transform() → BiStatement (structural)
      (Transformer does NOT require validation to run)
```

**Consequence**: 
- ✅ Phase 2.2.2.2 transformers can be built independently
- ✅ No blocking dependencies between validators and transformers
- ✅ Phase 2.2.3 refactoring doesn't impact transformers
- ✅ Decision: Build transformers → commit Phase 2.2.2.2 → then refactor to composable rules

---

## 4. Success Metrics

| Metric | Target | Verification |
|--------|--------|--------------|
| Total Test Coverage | 85%+ | `phpunit --coverage-html` report |
| All New Tests Passing | 50+ tests | `phpunit --no-coverage` exit code 0 |
| Phase 2.1 Regressions | 0 | Foundation tests 36/36 passing |
| Code Quality | 0 syntax errors | `php -l` on all files |
| Documentation | Complete | ADR + README + rule docs exist |
| Commit Ready | Yes | All above metrics met |

## 5. Risk Mitigation

| Risk | Mitigation Strategy |
|------|-------------------|
| ValidationResult immutability violations | Use __construct to set properties, no setters |
| Transformation data loss | Create strict unit tests for each field mapping |
| Enrichment service coupling | Make all enrichment services optional via DI |
| Integration test brittleness | Mock external service dependencies |
| Coverage gaps | Use `--coverage-xml` to find untested paths |

## 6. Acceptance Criteria

- ✅ All 50+ new tests passing
- ✅ 85%+ code coverage (all services)
- ✅ Phase 2.1 foundation: 36/36 passing (no regressions)
- ✅ All services follow stated interface contracts
- ✅ Proper exception handling (ParsingFailedException on transform errors)
- ✅ Dependency injection properly configured
- ✅ Documentation complete
- ✅ Ready for phase-2-2-2 semantic commit

## 7. Timeline

| Phase | Tasks | Duration | Start | End |
|-------|-------|----------|-------|-----|
| 2.2.2.1 | Validators (TASK-001 to 004) | 5 hours | Day 1 | Day 1 |
| 2.2.2.2 | Transformers (TASK-005 to 010) | 7 hours | Day 1-2 | Day 2 |
| 2.2.2.3 | Enrichment (TASK-011 to 016) | 5 hours | Day 2 | Day 2-3 |
| 2.2.2.4 | Integration (TASK-017 to 020) | 4 hours | Day 3 | Day 3 |
| **Phase 2.2.2** | **SUBTOTAL** | **21 hours** | | |
| 2.2.3 | Data Integrity Refactoring (TASK-021 to 030) | 18 hours | Day 4+ | Day 5 |
| **Total (All Phases)** | **30 tasks** | **39 hours** | | |

---

## Notes

- All file paths are relative to repository root
- All class namespaces follow `Ksfraser\FaBankImport\Import\{Subnamespace}` pattern
- All new tests go in `tests/Unit/Services/`, `tests/Unit/Validators/`, or `tests/Unit/Rules/`
- Phase 2.1 foundation tests MUST NOT be broken (zero-tolerance regressions)
- **Phase 2.2.2 Commit**: Will use semantic commit `feat(validators,transformers): implement...` (AFTER Phase 2.2.2.4)
- **Phase 2.2.3 Refactoring** (FUTURE): Refactor to composable RuleInterface pattern AFTER Phase 2.2.2.2 transformers are complete
- **Independence**: Transformers and validators are independent data flows - can build in parallel after Phase 2.2.2.1 validators exist
- **Timeline Update**: Phase 2.2.3 planned for future session (after Phase 2.2.2 commit)

