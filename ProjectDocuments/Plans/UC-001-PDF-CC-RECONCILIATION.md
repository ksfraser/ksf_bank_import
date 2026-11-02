---
goal: PDF CC Statement Reconciliation Feature Implementation Plan
version: 2.0
date_created: 2026-04-20
last_updated: 2026-04-20
owner: Finance Application / Engineering
status: In Progress
tags: [feature, ocr, plan]
---

![Status: In Progress](https://img.shields.io/badge/status-In%20Progress-yellow)

> **Revision 2.0 — 2026-04-20**
> Requirement clarification: the primary reconciliation target is FA's native GL bank
> transactions table (`0_bank_trans`), not the module's staging table (`bi_transactions`).
> Key changes from v1.0:
> - Phase 3 controller queries `0_bank_trans` (not `bi_transactions`).
> - Phase 5 commit updates `0_bank_trans.reconciled` and `0_bank_accounts` (not `bi_transactions.status`).
> - Phase 3 UI adds bank account selector, check/uncheck column, running balance tracking.
> - Phase 3 adds `bi_transactions` secondary cross-reference for unmatched statement lines.
> - Phase 1 adds `bi_statement_upload` migration for storing uploaded PDFs.

# Introduction

This implementation plan describes concrete, deterministic phases and tasks to deliver PDF bank statement reconciliation using an on-prem Ollama OCR model, targeting FA's native bank reconciliation flow (without modifying any FA core file) and persisting statement metadata and OCR JSON as supplementary data.

## 1. Requirements & Constraints

- **REQ-001**: Accept PDF upload; store raw file in `bi_statement_upload`.
- **REQ-002**: OCR via Ollama; parse response into `StatementOcr` with metadata + line items.
- **REQ-003**: Load **FA native bank transactions** from `0_bank_trans` (unreconciled entries) for the selected bank account and statement date range.
- **REQ-004**: Review UI with check/uncheck checkboxes, running cleared balance vs statement closing balance, unmatched items, `bi_transactions` cross-ref links for missing FA entries.
- **REQ-005**: Commit: set `0_bank_trans.reconciled`, update `0_bank_accounts`; also store `bi_reconciliation_session` metadata.
- **CON-001**: No FA core files modified. Replicate FA's SQL update patterns in our own code.
- **CON-002**: Ollama network access and auth required; must be configurable in `config.php`.
- **SEC-001**: Respect FA permissions (`SA_BANKACCOUNT`) for upload, review, and approval.

## 2. Implementation Steps

### Implementation Phase 1
- GOAL-001: Design data model and create DB migrations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create DB migration: `bi_statement_ocr` table (fields below) | ✓ | 2026-04-20 |
| TASK-002 | Create DB migration: `bi_statement_upload` table (store uploaded PDF files) | | |
| TASK-003 | Create DB migration: `bi_reconciliation_session` table | ✓ | 2026-04-20 |
| TASK-004 | Add config entries: `ollama_base_url`, `ollama_api_key`, `ollama_timeout_ms`, `ollama_ocr_model`, `ollama_extraction_model`, `sr_match_threshold`, `sr_bi_tx_date_tolerance_days`, `sr_approve_tolerance`, `sr_dupe_check_mode` | ✓ | 2026-04-20 |
| TASK-004b | `BiLineItemModel::get_transactions()`: replace hardcoded `2` day window with `$config->get('sr_bi_tx_date_tolerance_days', 2)` so both the cross-reference query and the existing JE matching share the same config key | | |
| TASK-004c | Add config key `sr_account_match_min_score` (float 0.0–1.0, default 0.50): minimum score for `BankAccountMatchService` to pre-select a candidate on the confirmation screen | | |

Files:
- **FILE-001**: `src/.../Migrations/CreateStatementOcrTable.php` — ✓ done, add `bank_account_id` and `upload_id` FK columns
- **FILE-001b**: `src/.../Migrations/CreateStatementUploadTable.php` — **NEW**
- **FILE-001c**: `src/.../Migrations/CreateReconciliationSessionTable.php` — ✓ done

### Implementation Phase 2
- GOAL-002: Implement Ollama client & OCR pipeline

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-005 | Implement `OllamaClient` class: `parsePdf(array $pdfBytes): array` with retry, timeout, and validation | ✓ | 2026-04-20 |
| TASK-006 | Implement `StatementTextParser` service that normalizes model response to `StatementOcr` schema | ✓ | 2026-04-20 |

Files:
- **FILE-002**: `src/.../Infrastructure/Ocr/OllamaClient.php` ✓
- **FILE-003**: `src/.../Infrastructure/Ocr/StatementTextParser.php` ✓

Validation:
- Unit tests mocking Ollama responses ✓

### Implementation Phase 3
- GOAL-003: Wire FA bank transaction loading and matching engine

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | `BankTransactionDto`: add `faTransType` (int) and `faTransNo` (int) fields for FA composite key | | |
| TASK-008 | `MatchedPair`: add nullable `faTransType`/`faTransNo` fields that flow through to commit | | |
| TASK-009 | `StatementReconcileController::loadBankTransactions()`: query `0_bank_trans` (not `bi_transactions`); filter unreconciled only | | |
| TASK-009b | `StatementReconcileController::loadBankTransactions()`: remove lower-date cutoff — query must be `trans_date <= statement_end_date` with no `>= statement_start_date` bound (REQ-012) | | |
| TASK-009c | `StatementReconcileController::handleConfirmAccount()` (new action): receive `bank_account_id` POST param after user confirms; validate; store in session; redirect to auto-reconcile step (REQ-003) | | |
| TASK-009d | `StatementReconcileController::handleParse()` update: after OCR, call `BankAccountMatchService::match()` and store ranked results + OCR `account_identifier` in session; redirect to account confirmation screen (not directly to review) | | |
| TASK-010 | `ReconcileView::renderUploadForm()`: PDF-only upload form (no bank account selector — account is determined post-OCR via REQ-018) | | |
| TASK-010b | `ReconcileView::renderAccountConfirmation()` (new method): show OCR `account_identifier`, ranked FA account candidates with scores, confirmation dropdown (pre-selects top candidate if score ≥ `sr_account_match_min_score`), "Confirm & Proceed" button (REQ-003, REQ-018) | | |

Files:
- **FILE-004**: `src/.../Domain/ValueObject/BankTransactionDto.php` — update
- **FILE-005**: `src/.../Domain/ValueObject/MatchedPair.php` — update
- **FILE-006**: `src/.../Application/StatementReconcileController.php` — update `loadBankTransactions()`
- **FILE-007**: `src/.../Application/ReconcileView.php` — update upload form

### Implementation Phase 4
- GOAL-004: Review UI enhancements (check/uncheck, balance tracking, bi_transactions cross-ref, new accounting controls)

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | `ReconcileView::renderReview()`: add check/uncheck checkboxes per FA bank entry | | |
| TASK-012 | `ReconcileView::renderReview()`: running cleared balance column, difference vs closing balance; `sr_approve_tolerance` controls when balance is treated as matched | | |
| TASK-013 | `ReconcileView::renderReview()`: for unmatched statement lines, show `bi_transactions` cross-ref with links to `process_statements.php` or view/edit | | |
| TASK-011b | `ReconcileView::renderReview()`: opening balance continuity warning — compare OCR `opening_balance` to `0_bank_accounts.ending_reconcile_balance`; show non-blocking warning if diff > `sr_approve_tolerance` (REQ-013) | | |
| TASK-011c | `StatementReconcileController::handleParse()`: statement sanity check — compare sum of OCR line amounts to balance delta; pass warning flag to view if diff > `sr_approve_tolerance` (REQ-016) | | |
| TASK-011d | `ReconcileView::renderReview()`: duplicate `0_bank_trans` detection — highlight amber rows sharing same amount+date+bank_act; respect `sr_dupe_check_mode` for alert/warning/confirm behaviour (REQ-017) | | |
| TASK-011e | `ReconcileView::renderReview()`: manual match UI — click-to-select unmatched statement line + FA entry, "Match Selected" button; controller endpoint to create/remove manual pair (REQ-014) | | |
| TASK-011f | `ReconcileView::renderReport()` (new method): printable reconciliation schedule — account, period, cleared items, outstanding items, balances, preparer + timestamp (REQ-015) | | |

Files:
- **FILE-007**: `src/.../Application/ReconcileView.php` — update review rendering

### Implementation Phase 5
- GOAL-005: Commit service targets FA's `0_bank_trans` and `0_bank_accounts`

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | `ReconciliationCommitService::commit()`: set `0_bank_trans.reconciled = statement_end_date` for each checked/matched entry using (fa_trans_type, fa_trans_no) composite key | | |
| TASK-015 | `ReconciliationCommitService::commit()`: update `0_bank_accounts.last_reconciled_date` and `ending_reconcile_balance` | | |
| TASK-016 | `ReconciliationCommitService::commit()`: also save `bi_reconciliation_session` record (keeps our extra metadata) | | |
| TASK-014b | `bi_reconciliation_session` schema + `ReconciliationCommitService`: record `created_by_user_id`, `created_at`, `approved_by_user_id`, `approved_at` to satisfy segregation-of-duties audit trail (SEC-001) | | |

Files:
- **FILE-008**: `src/.../Application/ReconciliationCommitService.php` — rewrite commit

### Implementation Phase 6
- GOAL-006: Tests, QA, deploy

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-017 | Integration tests: upload → auto-reconcile → approve → `0_bank_trans.reconciled` set | | |
| TASK-018 | Unit tests for `bi_transactions` cross-reference logic | | |
| TASK-019 | Security review and performance tests | | |
| TASK-019b | Implement `BankAccountMatchService`: exact-suffix, substring, bank-name-partial, history-bonus scoring; unit tests with mock FA accounts and OCR identifiers (REQ-018, §4.3) | | |
| TASK-020 | Documentation and runbook for Ollama model ops | | |
| TASK-021 | Unit test: opening balance continuity check — warning shown when OCR opening ≠ `ending_reconcile_balance` (REQ-013) | | |
| TASK-022 | Unit test: statement sanity check — warning shown when OCR line sum ≠ balance delta (REQ-016) | | |
| TASK-023 | Unit test: duplicate detection with all three `sr_dupe_check_mode` values; verify amber highlight and confirm-checkbox behaviour (REQ-017) | | |
| TASK-024 | Integration test: manual match flow — select pair, commit, verify FA entry marked reconciled (REQ-014) | | |
| TASK-025 | E2E / render test: reconciliation report output contains all required fields; accessible from review and success screens (REQ-015) | | |

## 3. Alternatives

- **ALT-001**: Use third-party OCR SaaS instead of Ollama. Rejected due to data residency and cost.
- **ALT-002**: Use PDF->raw-text only + regex parsing. Rejected due to fragility across banks.

## 4. Dependencies

- **DEP-001**: Ollama on-prem server and model availability
- **DEP-002**: FA commit APIs for reconciliation persistence
- **DEP-003**: DB migration tooling used by FA

## 5. Files (summary)

- **FILE-001**: migrations/2026_04_20_create_statement_ocr_table.php
- **FILE-002**: lib/OllamaClient.php
- **FILE-003**: lib/StatementParser.php
- **FILE-004**: controllers/StatementReconcileController.php
- **FILE-005**: views/statement_reconcile.php
- **FILE-006**: lib/MatchingEngine.php
- **FILE-007**: services/ReconciliationCommitService.php

## 6. Testing

- **TEST-001**: Unit tests for `StatementParser` using golden responses
- **TEST-002**: Integration tests for end-to-end flow with sample PDFs
- **TEST-003**: E2E UI tests for review/approve flow

## 7. Risks & Assumptions

- **RISK-001**: Ollama model accuracy varies across banks; mitigated with manual review UI.
- **RISK-002**: Network/auth issues to Ollama may block OCR; implement retries and clear error messages.
- **ASSUMPTION-001**: FA commit flow is available as callable API/routine from this codebase.

## 8. Related Specifications / Further Reading

- /spec/spec-pdf-cc-statement-reconciliation.md
- Ollama internal runbook
