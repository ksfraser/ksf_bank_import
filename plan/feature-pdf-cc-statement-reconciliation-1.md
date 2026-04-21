---
goal: PDF CC Statement Reconciliation Feature Implementation Plan
version: 1.0
date_created: 2026-04-20
last_updated: 2026-04-20
owner: Finance Application / Engineering
status: Planned
tags: [feature, ocr, plan]
---

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

# Introduction

This implementation plan describes concrete, deterministic phases and tasks to deliver PDF credit-card statement reconciliation using the on-prem Ollama OCR model, integrating with the existing FA reconciliation flow and persisting statement metadata and OCR JSON.

## 1. Requirements & Constraints

- **REQ-001**: Add configurable Ollama client that accepts PDF bytes and returns parsed JSON.
- **REQ-002**: Introduce new DB table `statement_ocr` to store metadata and `raw_ocr_json`.
- **REQ-003**: Clone FA reconciliation screen to present auto-match results.
- **CON-001**: Ollama network access and auth required; must be configurable in `config.php`.
- **SEC-001**: Respect FA permissions for upload, review, and approval.

## 2. Implementation Steps

### Implementation Phase 1
- GOAL-001: Design data model and create DB migrations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create DB migration: `statement_ocr` table (fields below) | | |
| TASK-002 | Add config entries: `OLLAMA_BASE_URL`, `OLLAMA_API_KEY`, `OLLAMA_TIMEOUT_MS` | | |

Files:
- **FILE-001**: `/migrations/2026_04_20_create_statement_ocr_table.php` - create `statement_ocr` with columns: id, account_identifier, statement_start_date, statement_end_date, opening_balance (decimal), closing_balance (decimal), due_date (date nullable), raw_ocr_json (text long), model_metadata (json), created_at, updated_at

### Implementation Phase 2
- GOAL-002: Implement Ollama client & OCR pipeline

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-003 | Implement `OllamaClient` class: `parsePdf(array $pdfBytes): array` with retry, timeout, and validation | | |
| TASK-004 | Implement `StatementParser` service that normalizes model response to `StatementOcr` schema | | |

Files:
- **FILE-002**: `lib/OllamaClient.php`
- **FILE-003**: `lib/StatementParser.php`

Validation:
- Unit tests mocking Ollama responses

### Implementation Phase 3
- GOAL-003: Clone FA reconciliation UI and wire data

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-005 | Copy FA reconciliation screen code to `statement_reconcile` controller/view and adapt to show statement lines and auto-matches | | |
| TASK-006 | Implement upload endpoint: accepts PDF -> calls OllamaClient -> stores statement_ocr record -> triggers auto-match | | |

Files:
- **FILE-004**: `PROD/class.bi_lineitem.php` (may be referenced for behavior)
- **FILE-005**: `controllers/StatementReconcileController.php`
- **FILE-006**: `views/statement_reconcile.php`

### Implementation Phase 4
- GOAL-004: Implement matching engine and auto-reconcile logic

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Implement `MatchingEngine` service: rules (exact amount+date, fuzzy description, amounts rounding, multi-line matches) | | |
| TASK-008 | Produce `matched_pairs` and `match_confidence` per pair and persist to temp session store | | |

Files:
- **FILE-007**: `lib/MatchingEngine.php`

Acceptance:
- Default match threshold configurable; perf: match run < 1s for 200 lines

### Implementation Phase 5
- GOAL-005: Review UX and approval persistence

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-009 | Implement review UI: allow adjust mappings, split/merge, mark unmatched, add manual link | | |
| TASK-010 | On approval, call FA commit flow to persist reconciliations; additionally write `statement_reconciliation` record linking to `statement_ocr` | | |

Files:
- **FILE-008**: `services/ReconciliationCommitService.php`
- **FILE-009**: `migrations/2026_04_20_create_statement_reconciliation_table.php`

### Implementation Phase 6
- GOAL-006: Tests, QA, deploy

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Integration tests: upload -> auto-reconcile -> approve -> DB persisted | | |
| TASK-012 | Security review and performance tests | | |
| TASK-013 | Documentation and runbook for Ollama model ops | | |

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
