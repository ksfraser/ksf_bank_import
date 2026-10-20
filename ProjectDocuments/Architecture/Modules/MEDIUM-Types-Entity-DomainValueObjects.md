# Business Requirements Document — Types / Entity / Domain ValueObjects

## Preamble

Coverage for the typed-scalars and immutable domain value objects of the FaBankImport module:

- `src/Ksfraser/FaBankImport/Types/` (3 files)
- `src/Ksfraser/FaBankImport/Entity/` (4 files)
- `src/Ksfraser/FaBankImport/Domain/ValueObjects/` + `ValueObject/` (6 files)
- `src/Ksfraser/FaBankImport/Results/` (`TransactionResult`, `PaginatedTransactionResult`) is covered in the Exceptions/Infrastructure doc.

## Subsystem Overview

| Module | Role | Req refs |
|---|---|---|
| Types/TransactionDC_indicator | `C/D/COR` scalar type (debit/credit flag) | FR-004, DR-001 |
| Types/TransactionType | enumerated transaction type for partner matching | FR-004, DR-001 |
| Types/TransactionTotals+ other | typed totals (paired/unpaired counts) | FR-008, DR-001 |
| Entity/PartnerEntity | partner type-safe entity (BR-001, ARCH-001) | FR-008 |
| Entity/PartnerMatchResult | matched partner result object (confidence clamp / totals) | FR-001 / BR-011 |
| Entity/UploadedFile | uploaded blob metadata for statements | BR-010 / ARCH-003 |
| Domain/ValueObjects/Keyword* | keyword tokens, match confidence, partner data | BR-011, FR-001 |
| ValueObject/{FileInfo, UploadResult, DuplicateResult} | upload + duplicate-mode result types (BR-010 / FR-050) | FR-017 |

## BABOK tagging status

All 13 files (3 Types + 4 Entity + 6 ValueObjects/Domain) carry:
- File-level `@BABOK RTM: FR-004 / DR-001 …`
- Per-method `@BABOK <refs> — method: …`
- Class-level `@BABOK CLASS ROLLUP: …`

## Test Coverage linkage

| Subsystem | Tests |
|---|---|
| Types | `tests/unit/views/*Trans*Test` (types exercised via View tests) — also `tests/unit/TransactionTypeFactoryTest` |
| Entity | `tests/unit/Entity/*Test` (PartnerEntityTest, PartnerMatchResultTest, UploadedFileTest) |
| ValueObject | `tests/unit/ValueObject/*Test` (FileInfoTest, UploadResultTest, DuplicateResultTest) |
| Domain/ValueObjects | `tests/unit/Domain/ValueObjects/{Keyword,KeywordMatch,MatchConfidence,PartnerData}Test` |
