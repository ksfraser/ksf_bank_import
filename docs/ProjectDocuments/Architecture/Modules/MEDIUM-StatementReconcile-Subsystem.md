# Business Requirements Document — StatementReconcile (Full Subsystem)

## Preamble

The `src/Ksfraser/FaBankImport/StatementReconcile/**` subsystem (30 files) implements the bank-statement reconciliation pipeline in a DDD-style layout (Application / Domain / Infrastructure / Matching). All requirements here are mapped to the SR-REQ-001..SR-REQ-018 rows in `docs/REQUIREMENTS_TRACEABILITY_MATRIX.csv` plus ARCH-010.

## Layer map

| Layer | Dirs | Role |
|---|---|---|
| Application | `StatementReconcile/Application` (6 files) | Use-case orchestration (upload → reconcile view → commit) |
| Domain | `StatementReconcile/Domain/{Entity,ValueObject,Service,Repository,Exception,Factory}` (12 files) | Pure domain model — no FA dependencies |
| Infrastructure | `StatementReconcile/Infrastructure/{Database,Migrations,Ocr,Persistence}` (11 files) | PDO repositories, OCR clients (Ollama), text parser |
| Matching | `StatementReconcile/Matching` (1 file) | MatchingEngine contract |

## Subsystem Overview

| Module | Role | Req refs |
|---|---|---|
| Application/StatementReconcileController | Primary application containing reconcile flow (SR-REQ-008..011, SR-REQ-014, 016, 017) | FR-007, BR-004 |
| Application/ReconciliationCommitService | Commits matched pairs, DB transactions | FR-007, BR-004 |
| Application/PendingSessionStore / PhpSessionPendingSessionStore | Session state between upload and review | BR-003 |
| Application/DrValidator (bank account match) + BankAccountMatchService | Structural validation of imported bank accounts | FR-007, ARCH-010 |
| Application/ReconcileView (++ Csrf) | Render review-page UI (SR-REQ-011..015, 018) | FR-009, IR-001 |
| Domain/Entity/StatementOcr, ReconciliationSession | Pure domain entities | DR-001, FR-007 |
| Domain/ValueObject/{StatementLine, StatementMetadata, MatchedPair, BankTransactionDto, RawOcrResult} | Immutable domain values | DR-001, FR-002 |
| Domain/Exception/* | Fine-grained domain exceptions | FR-050, FR-017 |
| Domain/Repository/* | Domain-side repository contracts | FR-008, ARCH-010 |
| Infrastructure/Ocr/{OllamaClient, PdfTextExtractor, StatementTextParser} | PDF→Ollama OCR→text-lines parser | FR-007, ARCH-007 |
| Infrastructure/Persistence/PdoStoredReconciliation + ReposDoctrine | PDO persistence | FR-008, ARCH-010 |
| Infrastructure/Database/Migrations/* | Staged migrations (1-pdo, 2-domain, 3-migrations) | FR-018, ARCH-010 |
| Matching/SimpleMatchingEngine | Concrete matcher binding DR validation | FR-007 |

## BABOK tagging status

All 30 files carry file-level RTM rollups, per-method tags (66 tags in this subtree), and class rollups.

## Test Coverage linkage

| Subsystem | Tests |
|---|---|
| Application/* | `tests/unit/StatementReconcile/Application/*` (ReconcileView, Csrf, BankAccountMatchService, ReconciliationCommitService, StatementReconcileController, PhpSessionPendingSessionStore) |
| Domain/* | `tests/unit/StatementReconcile/Domain/{Entity,ValueObject,Exception}/*` |
| Infrastructure/* | `tests/unit/StatementReconcile/Infrastructure/*` (Ocr clients, PDO repos, migrations) |
| Matching | `tests/unit/StatementReconcile/Matching/SimpleMatchingEngineTest` |

## BABOK Coverage notes

- `StatementReconcile/Application/*` ↔ **SR-REQ-001..018** rows in RTM (status = IMPLEMENTED, PASS per `docs/RTM ST-001` from UAT-011 pass)
- `StatementReconcile/Domain/*` ↔ **DB-driven domain objects** on `docs/ProjectDocuments/Architecture/ARCH-010-*`
- `StatementReconcile/Infrastructure/*` ↔ **ARCH-010 staged** per `docs/ProjectDocuments/Architecture/ARCH-010-*`
- `StatementReconcile/Matching/*` ↔ **FR-007** on `docs/ProjectDocuments/Architecture/ARCH-010-*`
