# Business Requirements Document — Partner Subsystem (API / Partner / Contracts)

## Preamble

This document defines the partner subsystem requirements coverage for the following modules:

- `src/Ksfraser/FaBankImport/API/` — administrative/automation HTTP API for partner matching
- `src/Ksfraser/FaBankImport/Application/Partner/` — partner matching application orchestration
- `src/Ksfraser/FaBankImport/Contracts/` — narrow interface contracts (DI contracts for partner/data flows)

## Subsystem Overview

| Module | Role | Requirement refs (RTM) |
|---|---|---|
| API/MatchTransactionRequest | Wards the request used by the matching API | FR-001, ARCH-001 |
| API/MatchTransactionResponse | Response envelope for match results and diagnostics | FR-001, ARCH-001 |
| API/PendingTransactionReport | Reports on pending transactions statistics | FR-008, ARCH-001 |
| API/APIErrorResponse | Standard error envelope (FR-016/FR-017 mapping) | FR-016, FR-017, ARCH-003 |
| Application/Partner | Scoring / search / training flow services | BR-011, ARCH-011 |
| Contracts/* | Narrow constructor-injection contracts | FR-015, ARCH-001 |

## Requirements traceability

| Req ID | Name | Modules | File Rollup refs |
|---|---|---|---|
| FR-001 | Automatic Pair Detection | API/MatchTransactionRequest, API/MatchTransactionResponse | `@BABOK RTM: FR-001 / ARCH-001` |
| FR-008 | Transaction Status Management | API/PendingTransactionReport | `@BABOK RTM: FR-008 / ARCH-001` |
| FR-016 | Validation Error Handling | API/APIErrorResponse | `@BABOK RTM: FR-016 / ARCH-003 / FR-050` |
| FR-017 | Exception Management | API/APIErrorResponse | `@BABOK RTM: FR-016 / ARCH-003 / FR-050` |
| BR-011 | Keyword Matching & Clustering Score | Application/Partner/* | `@BABOK RTM: BR-011 / ARCH-011` |
| FR-015 | Dependency Injection | Contracts/* | `@BABOK RTM: FR-015 / ARCH-001` |

## BABOK tagging status

All 6 API files, all 6 Application/Partner files, and all 6 Contracts files carry:
- **File-level rollup** `@BABOK RTM: <refs>; file: ...; coverage: docs/TestCases/ (file rollup)`
- **Per-method** `@BABOK <refs> — method: <name>` (or line inside the class docblock)
- **Class-level rollup** `@BABOK CLASS ROLLUP: <Class> — <N> methods; <Ref> (xCount); ...`

Verified with the 2026-10-20 tag-integrity scan (a 100% pass across 1990 method decls).

## Test Coverage linkage

| Subsystem | Tests | measured suite note |
|---|---|---|
| API/* | `tests/unit/API/APIDTOsTest`, `tests/unit/API/MatchingAPITest` | PASS |
| Application/Partner | `tests/unit/Services/*` (matcher/scoring pipes) | PASS |
| Contracts | implicit (interface contracts satisfied by concrete classes) | PASS |

Coverage %: measured with xdebug coverage on FaBankImport scope. Baseline is checked into `coverage_full.txt` and the `coverage/` HTML report (see `ProjectDocuments/…`).
