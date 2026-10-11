---
title: PDF CC Statement Reconciliation Specification
version: 2.0
date_created: 2026-04-20
last_updated: 2026-04-20
owner: Finance Application / Engineering
tags: [feature, reconciliation, ocr, ollama, pdf, data]
---

> **Revision 2.0 — 2026-04-20**
> Revised to clarify the primary reconciliation target: FA's native GL bank transactions
> (`0_bank_trans`), not the module's staging table (`bi_transactions`).
> The `bi_transactions` table is only used as a secondary cross-reference for
> statement lines that have no matching FA bank entry.

# Introduction

This specification defines the requirements, data contracts, interfaces, acceptance criteria, and constraints for adding PDF statement reconciliation as an enhanced overlay on FA's existing bank reconciliation workflow. The feature uses an on-premises Ollama model instance to OCR uploaded PDF bank/CC statements, extracts statement metadata and line-items, and automatically matches them against FA's posted GL bank entries. It does NOT modify FA's core reconciliation code; instead it duplicates and extends the reconciliation flow with additional metadata and OCR-driven automation.

## 1. Purpose & Scope

**Purpose**: Provide an automated pipeline to OCR uploaded PDF statements, check their transactions against FA's reconciliation list (`0_bank_trans`), surface an interactive "auto-reconciled" review screen with check/uncheck capability and balance tracking, allow user adjustments and approval, and persist reconciliation results using FA's existing patterns.

**Scope**:
- Accept PDF bank/CC statement uploads; store the file and metadata in `bi_statement_upload`.
- OCR PDF statements using Ollama API (on-prem server).
- Extract statement-level metadata: opening balance, closing balance, statement date range, due date (if present), account identifier, and parsed line-items (date, amount, description, fitid-like identifier if present).
- Load FA's native bank transactions (`0_bank_trans`) for the **automatically detected or user-confirmed** bank account and statement date range (unreconciled entries only).
- Auto-detect the bank account from the OCR'd statement `account_identifier` and present a confirmation/override screen before loading `0_bank_trans`.
- Attempt automated matching of statement lines to `0_bank_trans` entries.
- Present an interactive review UI that mirrors FA's reconciliation screen: check/uncheck individual bank entries, display running cleared balance vs statement closing balance, and list unmatched items.
- Cross-reference unmatched statement lines against `bi_transactions` (the module's staging table) and surface actionable links:
  - If `bi_transactions.status = 0` (unprocessed): link to `process_statements.php` with pre-filled date/bank-account filters.
  - If `bi_transactions.status = 1` (processed into FA): link to view/edit the FA transaction.
- On user approval, persist reconciliation exactly as FA does: set `0_bank_trans.reconciled` to the statement end date for each checked/matched entry, and update `0_bank_accounts.last_reconciled_date` / `ending_reconcile_balance`. Additionally store our session metadata in `bi_reconciliation_session`.
- Provide reports of statement-only transactions not present in `0_bank_trans`.

**Out of scope for Phase 1**:
- Full multi-currency remapping (unless present in statement and bank data); currency support will be documented as constraint.
- Integrations other than configured Ollama server.
- Modifying any native FA file (no clobbering of FA core code).
- One-to-many / many-to-one transaction matching — Phase 1 is one-to-one only (see CON-004).
- Reconciler memo / notes per individual line item — deferred to Phase 2 (see CON-004).

**Out of scope (FA platform responsibility — not this module)**:
- **Period / fiscal-year locking** — FA's native functionality; this module does not lock periods (see CON-004).
- **Undo / re-open an approved reconciliation** — once `0_bank_trans.reconciled` is set it follows FA's own corrections flow (see CON-004).
- **Two-sided reconciliation formula** (book balance + deposits in transit − outstanding cheques) — FA's native capability; this module provides the statement-driven single-sided view consistent with FA's existing reconciliation screen.

## 2. Definitions

- Ollama: in-house models server used to run an OCR/parsing model.
- FA: the FrontAccounting finance application with existing bank reconciliation screen and persistence patterns.
- `0_bank_trans`: FA's native bank transactions table. Each posted journal entry that touches a bank account is recorded here. Has a `reconciled` DATE column (value `0000-00-00` means unreconciled). This is the **primary reconciliation target**.
- `bi_transactions`: this module's staging table for transactions imported from CSV/OFX/MT940 files but not yet posted to FA. Used only as a **secondary cross-reference** for unmatched statement lines.
- OCR JSON: the structured JSON returned from model parsing that includes raw text and extracted fields.
- Statement Metadata: opening_balance, closing_balance, statement_start_date, statement_end_date, due_date (optional), account_id, raw_ocr_json.
- Reconciliation Result: mapping from statement line(s) to `0_bank_trans` row(s) (identified by `type` + `trans_no`) plus match confidence.
- Reconciliation Period: one reconciliation run covers one bank account for one statement period, analogous to creating a new reconciliation statement in FA's native screen.

## 3. Requirements, Constraints & Guidelines

- **REQ-001**: The system shall accept a PDF file upload and store the raw file in `bi_statement_upload` before OCR processing.
- **REQ-002**: The system shall call the configured Ollama OCR model endpoint with the PDF bytes and parse the response into a well-defined JSON schema (see § 4). Extracted metadata must include opening_balance, closing_balance, statement_start_date, statement_end_date, and at least one line item.
- **REQ-003**: After OCR completes, the system shall attempt to **auto-detect the FA bank account** using the extracted `account_identifier` (see REQ-018 and §4.3). The result is presented to the user as a **confirmation screen** — not a blind selection — showing:
  a. The OCR-extracted account identifier (e.g. last 4 digits, partial account number, or account label).
  b. The best-matched FA bank account (name, number, bank name) with a match-confidence indicator.
  c. A dropdown override allowing the user to select a different account if the auto-match is wrong or not found.
  d. A "Confirm & Proceed" button. No `0_bank_trans` entries are loaded until the user explicitly confirms the account.
  Rationale: eliminates fat-finger risk of selecting the wrong account from a long dropdown before the statement has been read; the user validates against visible statement data rather than picking blindly.
- **REQ-004**: The system shall present an interactive reconciliation review UI that:
  a. Displays a summary bar with statement opening balance, closing balance, and due date.
  b. Shows a progress bar for match percentage.
  c. Lists each `0_bank_trans` row with a **check/uncheck checkbox** (auto-checked if the matching engine matched it to a statement line), amount, date, description, and running cleared balance.
  d. Lists unmatched statement lines with cross-references to `bi_transactions` (see REQ-009).
  e. Shows the difference between the running cleared balance and the statement closing balance.
  f. Provides an Approve button (requires check that cleared balance equals closing balance) and a Cancel link.
- **REQ-005**: Upon user approval, the system shall persist reconciliation exactly as FA's commit flow does, without modifying any FA core file:
  a. For each checked `0_bank_trans` row: `UPDATE 0_bank_trans SET reconciled = '<statement_end_date>' WHERE type = X AND trans_no = Y`.
  b. `UPDATE 0_bank_accounts SET last_reconciled_date = '<statement_end_date>', ending_reconcile_balance = <closing_balance> WHERE id = <bank_account_id>`.
  c. Save a `bi_reconciliation_session` record linking to the `bi_statement_ocr` record (extra metadata beyond native FA).
- **REQ-006**: The system shall store statement metadata and the raw OCR JSON in `bi_statement_ocr` (linked to `bi_statement_upload`).
- **REQ-007**: The system shall flag and list any OCR'd statement lines that have no matching `0_bank_trans` entry after the auto-matcher runs.
- **REQ-008**: The uploaded PDF shall be stored (name, path/reference, upload timestamp, file size) in a `bi_statement_upload` table analogous to how `import_statements` stores imported files.
- **REQ-009**: For each unmatched statement line (no corresponding `0_bank_trans` entry), the system shall cross-reference `bi_transactions` by amount and date within ± `sr_bi_tx_date_tolerance_days` calendar days (config key, integer ≥ 0, default 2). This uses the **same tolerance window** already applied by the existing JE transaction matching in `BiLineItemModel::get_transactions()` — currently hardcoded to 2 days there; making it a shared config key ensures both paths stay in sync. Show:
  - If found with `status = 0` (unprocessed): a link to `process_statements.php` pre-filled with the statement's date range and bank account filter.
  - If found with `status = 1` (processed): a link to view/edit the FA transaction.
  - If not found: display only the statement line data with an "entry missing from FA" indicator.
- **REQ-010**: The system shall support creating a new reconciliation period or re-opening (loading) an existing `bi_reconciliation_session` for a bank account, similar to FA's "New Reconciliation" / "Open Existing" flow.
- **REQ-011**: The system shall provide an LLM / Ollama configuration admin screen (accessible to admin users). The screen must expose:
  a. **Ollama base URL** (required; validated as a valid HTTP/HTTPS URL before save).
  b. **API key / Bearer token** (optional; stored in module config; never echoed back in plaintext after save — display as `***` or empty password field).
  c. **OCR model** selector — the model used for PDF text-extraction (e.g. `glm-ocr`). Populated by querying `GET {OLLAMA_BASE_URL}/api/tags` server-side (see CON-003); falls back to free-text input if unreachable.
  d. **Extraction / processing model** selector — the model used for structured data parsing, matching assistance, and any LLM-powered cross-reference logic (e.g. `gemma4`). Populated the same way as (c); the two model selectors may choose the same model or different models.
  e. **Date tolerance** — `sr_bi_tx_date_tolerance_days` (integer ≥ 0, default 2). Controls the ± day window for cross-referencing unmatched statement lines against `bi_transactions` (REQ-009).
  f. **Match confidence threshold** — `sr_match_threshold` (decimal 0.0–1.0, default 0.70). The minimum confidence for the auto-matcher to accept a pair without flagging it for manual review.
  g. A **"Test Connection / Refresh Models"** button that calls `GET /api/tags` server-side, displays the Ollama server version and number of installed models, updates the model dropdowns, and shows a clear success or failure notice.
  h. **Approve tolerance** — `sr_approve_tolerance` (decimal ≥ 0.00, default 0.01). Maximum absolute difference (in account currency) between the running cleared balance and the statement closing balance at which the Approve button is enabled without a balance-mismatch warning. Set to `0.00` to require exact match.
  i. **Duplicate check mode** — `sr_dupe_check_mode` (enum: `alert` | `warning` | `confirm`, default `alert`). Controls how detected duplicate `0_bank_trans` entries (same amount, date, bank account) are surfaced on the review screen. See REQ-017.
- **REQ-012**: Once the bank account is confirmed (REQ-003), the system shall load **all unreconciled `0_bank_trans` entries** for the selected bank account with `trans_date <= statement_end_date`. There is no lower-date cutoff; prior-period outstanding items must always appear in the list until they are cleared. The controller query must not apply a `trans_date >= ...` lower bound. Rationale: a cheque issued in February that has not cleared by April must still appear on the April reconciliation.
- **REQ-013**: Before displaying the review screen, the system shall compare the OCR-extracted `opening_balance` to `0_bank_accounts.ending_reconcile_balance` (the ending balance recorded on the account's last approved reconciliation). If the two values differ by more than `sr_approve_tolerance`, a **non-blocking warning** shall be displayed prominently on the review screen and remain visible until the session is cancelled or committed. The reconciliation may proceed regardless; the user is not blocked. Rationale: a mismatch typically indicates a prior-period error or a manually adjusted FA balance and is an auditable condition.
- **REQ-014**: The review UI shall provide a **manual match** facility. The reconciler shall be able to select one unmatched statement line and one unmatched FA bank entry and click "Match Selected" to create an explicit pair. Manual matches shall be displayed with a "Manual" badge and treated identically to auto-matches for commit purposes (the FA entry is checked and will be marked reconciled on approval). Removing a manual match returns both items to their unmatched lists.
- **REQ-015**: The system shall produce a **printable reconciliation schedule** accessible via a "Print / Export" button on both the review and success screens. The schedule shall include: bank account name and number, statement period (start/end dates), opening balance, itemised list of cleared transactions (date, description, amount), total cleared, itemised list of outstanding FA entries (uncleared), statement closing balance, adjusted balance, and the preparer's user name and approval timestamp. Implemented as print-friendly HTML.
- **REQ-016**: Before displaying the review screen, the system shall compute the sum of absolute line-item amounts extracted by OCR and compare it to `abs(closing_balance − opening_balance)`. If the difference exceeds `sr_approve_tolerance`, a **non-blocking warning** shall be shown (e.g., "Statement lines may be incomplete — OCR might have missed one or more transactions"). The auto-match and review flow continues normally.
- **REQ-017**: The system shall detect `0_bank_trans` entries in the loaded set that share the same `amount`, `trans_date`, and `bank_act`. Detected duplicates are highlighted in amber. The handling is controlled by `sr_dupe_check_mode`:
  - `alert` (default): informational highlight and count notice only; no workflow impact. This is the correct default because legitimate duplicates are common (e.g., repeated grocery purchases, multiple transit ticket transactions moments apart, return trips within the same day).
  - `warning`: amber banner on the review screen; Approve button is not disabled.
  - `confirm`: reconciler must check an explicit "I confirm these duplicates are legitimate" checkbox before the Approve button becomes active.
- **REQ-018**: **Bank account auto-detection.** After OCR, the system shall query all active FA bank accounts (`0_bank_accounts WHERE inactive = 0`) and score each one against the OCR `account_identifier` using the following rules in priority order:
  1. **Exact suffix match** (highest confidence — 1.0): the last N digits of `bank_account_number` (with spaces/dashes stripped) exactly match the last N digits of `account_identifier` (stripped). N = length of `account_identifier`.
  2. **Substring match** (0.85): `account_identifier` (stripped) appears anywhere within `bank_account_number` (stripped).
  3. **Bank name partial match** (bonus +0.10): OCR-extracted bank name (from statement header) matches `bank_name` in FA (case-insensitive, partial).
  4. **History match** (bonus +0.15): a previous approved `bi_reconciliation_session` linked this `account_identifier` to this `bank_account_id` (most reliable signal for repeat statements).
  The highest-scoring account is pre-selected on the confirmation screen. If no account scores above 0.50, the confirmation screen shows no pre-selection and prompts the user to manually choose. The existing `BankAccountByNumber` class and `getByBankAccountNumber()` lookup in `fa_bank_accounts` provide the data layer for this lookup.
- **SEC-001**: PDF uploads and OCR JSON storage shall follow existing FA data protection policies; personally-identifiable data must be handled consistently with current rules. The `bi_reconciliation_session` record shall capture `created_by_user_id`, `created_at`, `approved_by_user_id`, and `approved_at` timestamps to satisfy segregation-of-duties and audit trail requirements.
- **CON-001**: Ollama endpoint is on-prem and may require mutual-TLS or internal network access; all connection settings (URL, API token, model selections, date tolerance, match threshold) must be configurable through a dedicated LLM configuration admin screen (see REQ-011) and stored in module config without requiring code changes.
- **CON-002**: No FA core files shall be modified.
- **CON-003**: The Ollama model-list endpoint (`GET {OLLAMA_BASE_URL}/api/tags`) must be called **server-side only**; Ollama credentials must never be sent to or exposed in the browser. Model-list results may be cached in the PHP session for up to 5 minutes. If the endpoint is unreachable, the selector degrades to a free-text input with a visibility warning.
- **CON-004**: The following capabilities are **explicitly out of scope for this module** — they are FA platform responsibilities:
  - **Period / fiscal-year locking** — managed by FA's native period-lock controls; this module shall not attempt to lock periods.
  - **Undo / re-open an approved reconciliation** — once `0_bank_trans.reconciled` is set, correction follows FA's standard corrections flow.
  - **Reconciler memo / notes per line item** — deferred to Phase 2.
  - **One-to-many / many-to-one transaction matching** — Phase 1 matches exactly one statement line to one FA entry.
- **GUD-001**: Use existing FA UI components (FA helper functions: `start_table()`, `table_header()`, `label_row()`, `amount_cell()`, etc.) and FA's DB helper functions (`db_query()`, `db_escape()`, `db_fetch()`) wherever possible to minimize divergence.

## 4. Interfaces & Data Contracts

### 4.1 Ollama OCR Request

- Endpoint: configurable `OLLAMA_BASE_URL` + `/v1/ocr` (example path; configurable)
- Auth: as configured (API key, mTLS, or internal network)
- Request: multipart/form-data or application/pdf binary body depending on Ollama model API

Example (high level):
- POST {OLLAMA_BASE_URL}/api/parse-pdf
  - Headers: Authorization: Bearer <OLLAMA_KEY>
  - Body: PDF bytes

### 4.2 Ollama OCR Response (normalized into `StatementOcr`)

Schema (example):
- statement_id: string (optional generated)
- account_identifier: string | null
- statement_start_date: YYYY-MM-DD
- statement_end_date: YYYY-MM-DD
- opening_balance: decimal
- closing_balance: decimal
- due_date: YYYY-MM-DD | null
- lines: [
  { line_id: string, date: YYYY-MM-DD, description: string, amount: decimal, type: credit|debit, raw_text: string }
]
- raw_text: string
- raw_ocr_json: object (full model response)
- model_metadata: { model: string, version: string, confidence_overview: { ... } }

### 4.3 Bank Account Match Service (`BankAccountMatchService`)

Responsible for scoring FA bank accounts against an OCR-extracted `account_identifier`.

- Input: `string $accountIdentifier` (from `StatementMetadata`), `string|null $ocrBankName` (from statement header if extracted)
- Returns: `BankAccountMatchResult[]` sorted descending by score:
  - `bankAccountId: int`
  - `bankAccountName: string`
  - `bankAccountNumber: string`
  - `bankName: string`
  - `score: float` (0.0–1.0+; history bonus may push above 1.0 before normalization)
  - `matchMethod: string` (e.g. `'exact_suffix'`, `'substring'`, `'history'`, `'none'`)
- Delegates account number lookup to the existing `BankAccountByNumber` / `fa_bank_accounts::getByBankAccountNumber()` infrastructure.
- Queries `bi_reconciliation_session` history for prior `account_identifier → bank_account_id` mappings as a high-confidence bonus signal.
- File: `src/.../StatementReconcile/Application/BankAccountMatchService.php`

### 4.4 Ollama Tags API (model list)

- Endpoint: `GET {OLLAMA_BASE_URL}/api/tags`
- Auth: same Bearer token as all other Ollama calls (`ollama_api_key` config key).
- Called **server-side only** (see CON-003) — from the LLM config screen (REQ-011) and optionally refreshed on the upload form.
- Response schema (Ollama v0.3+):

```json
{
  "models": [
    {
      "name": "gemma4:latest",
      "model": "gemma4:latest",
      "size": 4000000000,
      "digest": "sha256:...",
      "details": { "families": ["gemma"], "parameter_size": "4B", "quantization_level": "Q4_K_M" },
      "modified_at": "2026-04-01T12:00:00Z"
    }
  ]
}
```

- The `name` field is the value to store in `ollama_ocr_model` / `ollama_extraction_model` config keys and to pass as the `model` parameter in all API calls.
- The system shall display the `details.parameter_size` and `details.quantization_level` alongside each model name in the selector for operator guidance.

### 4.4 FA Bank Transaction DTO (`BankTransactionDto`)

Fields loaded from `0_bank_trans`:
- id: int (synthetic sequence, session-local only)
- fa_trans_type: int (FA transaction type code, e.g. 41=bank payment, 42=bank deposit)
- fa_trans_no: int (FA transaction number)
- date: YYYY-MM-DD
- amount: decimal string (positive, direction in `type`)
- description: string (from `ref` field)
- type: 'credit' | 'debit'

The composite `(fa_trans_type, fa_trans_no)` is the FA-level primary key and is required for both the commit step and view/edit links.

### 4.5 Internal Reconciliation Result

- reconciliation_session_id: integer (PK)
- statement_source_id: integer (FK → bi_statement_ocr.id)
- bank_account_id: integer (FK → FA 0_bank_accounts.id)
- matched_pairs: [ { statement_line_id, bank_transaction_id (sequence), fa_trans_type, fa_trans_no, match_confidence, rules_matched } ]
- unmatched_statement_lines: [statement_line_id]
- unmatched_bank_trans: [ {fa_trans_type, fa_trans_no} ]
- persisted_by_user_id: integer (on approval)
- persisted_at: timestamp

### 4.6 Uploaded Statement Record (`bi_statement_upload`)

- id: int PK
- original_filename: varchar(255)
- stored_path: varchar(500) (server-side path or reference key)
- file_size_bytes: int
- bank_account_id: int (nullable, set after account selection)
- statement_ocr_id: int (nullable, FK → bi_statement_ocr.id, set after OCR)
- uploaded_by_user_id: int
- uploaded_at: timestamp

## 5. Acceptance Criteria

- **AC-001**: Given a valid PDF statement and a selected bank account, when uploaded, then the system returns a parsed `StatementOcr` with statement metadata and at least one line item, and stores the file in `bi_statement_upload`.
- **AC-002**: Given parsed statement data and unreconciled `0_bank_trans` rows, when auto-reconcile runs, then ≥75% of statement line items with exact amount/dates present in `0_bank_trans` should be auto-matched (configurable threshold).
- **AC-003**: The review UI shall present: matched rows with checkboxes checked (auto), unmatched statement lines with `bi_transactions` cross-reference links, unmatched `0_bank_trans` rows, a running cleared balance column, and the difference vs statement closing balance.
- **AC-004**: After user approval, for each checked `0_bank_trans` row: `reconciled` is set to the statement end date. `0_bank_accounts.last_reconciled_date` and `ending_reconcile_balance` are updated. A `bi_reconciliation_session` row is created linking to `bi_statement_ocr`.
- **AC-005**: Unmatched statement lines that have a corresponding `bi_transactions` row (status=0) shall show a link to `process_statements.php` with date and bank account pre-filled.
- **AC-006**: Unmatched statement lines that have a corresponding `bi_transactions` row (status=1) shall show a view/edit link.
- **AC-007**: Security: PDF uploads and stored OCR JSON are accessible only to authorized finance users matching FA `SA_BANKACCOUNT` permissions.
- **AC-008**: The system loads all unreconciled `0_bank_trans` entries with `trans_date <= statement_end_date` for the **confirmed** bank account; pre-existing outstanding items from prior periods appear in the list.
- **AC-009**: If the OCR opening balance differs from `0_bank_accounts.ending_reconcile_balance` by more than `sr_approve_tolerance`, a visible non-blocking warning appears on the review screen; the reconciliation can still be approved.
- **AC-010**: The reconciler can manually link an unmatched statement line to an unmatched FA entry; the resulting manual pair is committed and marked reconciled on approval, identical to an auto-matched pair.
- **AC-011**: A printable reconciliation schedule is accessible from the review and success screens and contains: account, period, cleared items, outstanding items, balances, preparer name, and approval timestamp.
- **AC-012**: If the sum of OCR line-item amounts does not match the balance delta within `sr_approve_tolerance`, a non-blocking warning is shown on the review screen before the transaction table.
- **AC-013**: Detected duplicate `0_bank_trans` entries (same amount + date + bank account) are highlighted amber; behaviour matches the configured `sr_dupe_check_mode` value.
- **AC-014**: After OCR, the system presents the best-matched FA bank account with a confidence indicator. If history bonus applies (prior reconciliation for same `account_identifier`), that account is pre-selected. If no account scores above 0.50, no pre-selection is made and the user must manually choose. The user must explicitly confirm before `0_bank_trans` is loaded.

## 6. Test Automation Strategy

- Unit tests for Ollama client with mocked responses
- Integration tests for parsing pipeline using sample PDFs (golden files)
- UI end-to-end tests for upload → auto-reconcile → review → approve flow
- Migration tests for DB schema changes
- Performance test for OCR throughput (single-file latency target < 5s for typical statements)

## 7. Rationale & Context

Automating CC statement reconciliation reduces manual review time and improves detection of missing transactions. Using an on-prem Ollama instance keeps sensitive financial PDFs within infrastructure boundaries.

## 8. Dependencies & External Integrations

- **EXT-001**: Ollama on-prem server (OCR/parsing model)
- **DEP-001**: FA existing reconciliation APIs and DB schema for persisting results
- **INF-001**: Network route and credentials to access Ollama

## 9. Examples & Edge Cases

- Example: Statement line with amount 123.45 and date 2026-03-15 maps to bank transaction with same date and amount but description mismatch; match_confidence=0.95 if amount/date match.
- Edge: Statement uses different timezone or date formatting — parser must normalize.
- Edge: Statement includes card refunds labeled as negative amounts — must map to credit transactions.

## 10. Validation Criteria

- Automated test coverage for parser and matching logic
- Manual QA sign-off on sample statements from 3 major banks

## 11. Related Specifications / Further Reading

- FA reconciliation UI docs (existing internal doc)
- Ollama model operational runbook
