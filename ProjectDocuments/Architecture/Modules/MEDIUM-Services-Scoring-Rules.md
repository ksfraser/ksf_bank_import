# Business Requirements Document — Services / Scoring / Rules Subsystems

## Preamble

The `src/Ksfraser/FaBankImport/Services/**` subtree (49 files, + 5 Scoring + 5 Rules) hosts the service layer (ARCH-001) implementing pair/matching flows, keyword scoring (BR-011 / ARCH-011), duplicate handling, file storage, error/eventing, metrics, and configuration lookups per FR-001..FR-018.

## Subsystem overview

### Matching & scoring (ARCH-011 / BR-011)
- `KeywordExtractorService`, `KeywordMatchingService` — keyword extraction and fuzzy matching
- `Scoring/*` — `ScoringRuleEngine`, `ScoringRule`, `ScoringEngineFactory`, `SupplierCandidate`, `RecencyRule`, `TypeConsistencyRule`, `AmountRangeRule` — modular scoring engine (composable candidates and rules) implementing BR-011-002/003/004
- `Rules/*` — `BankAccountMatchRule` (BR-001, ARCH-001), `InvoiceDetectionRule` (ARCH-001), `RefundDetectionRule`, `VendorNameMatchRule` — declarative rules for pairs and direction classification (BR-002 × FR-004)
- `TransactionMatchingService`, `TransactionMatcherIntegration`, `TransactionMatchResult`, `SupplierMatchResult`, `VendorCandidate` — matching oracles
- `SupplierMatcher`, `PartnerMatcher`, `TransactionPartnerMatcher`, `PartnerMatcherFactory` — matching dispatch across partner types (ARCH-001)
- `SupplierMatchingRules`, `SupplierMatchingConfiguration` — supplier-focused parameterised matching (BR-001)
- `CustomerMatchingConfiguration`, `CustomerScoringEngineFactory`, `SupplierScoringEngineFactory`, `ScoringEngineFactory` — matching-side factories
- `KeywordExtractorService`, `KeywordMatchingService`, `ConfidenceEnhancer`, `PartnerMatchingConfiguration`, `KeywordExtractorService` together implement BR-011 keyword-clustering pipeline (CLUSTERING FACTOR = 0.2)
- `PartnerDataService` — reusable partner data flows (FR-008, ARCH-001)

### Pair processing (ARCH-001/ARCH-008)
- `TransactionService` — pair processing (FR-001, FR-002, FR-003, FR-004, FR-005, FR-008)
- `TransactionValidator`, `TransactionGLValidator` — FR-007 validation (ARCH-003)
- `TransferDirectionAnalyzer` — FR-004 DC direction analysis / FR-005 transfer construction
- `BankTransferFactory` — FR-006 create() of transfer into FA
- `PairedTransferProcessor` — FR-001, FR-002, FR-003, FR-006 (primary auto pair detection pipeline)
- `TransactionUpdater` — FR-008 status updates (ARCH-008 audit path)
- `TransactionLogger` — transaction audit logging (FR-018, ARCH-008)
- `DuplicateDetector` — duplicate transaction detection (FR-008, ARCH-001)

### Files / staging (ARCH-003)
- `FileUploadService`, `FileStorageService`, `FileStorageServiceInterface`, `UploadedFileManager` — uploaded-file staging (BR-010 / ARCH-003, FR-016/FR-017)

### Cross-cutting (FR-012 / ARCH-007)
- `PerformanceMonitor`, `MetricsAggregator`, `TransactionCounter`, `AlertService` — performance metrics/log analysis, alert dispatch (BR-012 / FR-012, singleton)
- `SimpleCommandBus`, `EventDispatcher` — service-architecture primitives for commands/events (FR-014, ARCH-004)
- `ReferenceNumberService` — FR-048 reference number generation with `getUniqueReference()`
- `Config` — centralised config lookup (FR-002, FR-051)

## BABOK tagging status

All 49 Services files + Scoring + Rules carry:
- File-level `@BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001` etc. (per-file from batch specs)
- Per-method `@BABOK <refs> — method: …`
- Class-level `@BABOK CLASS ROLLUP: …`

96 `@BABOK CLASS ROLLUP` lines confirmed in the Services tree as of the 2026-10-20 audit.
