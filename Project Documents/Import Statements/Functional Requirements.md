# Functional Requirements — Import Bank Statements

## Feature
Upload and parse bank statement files and import them into staging tables, with duplicate handling, account resolution, and file-to-statement traceability.

## Definitions
- **Staging tables**: module-owned tables storing imported statements and transactions (e.g., `bi_statements`, `bi_transactions`).
- **Uploaded file record**: module record representing a stored uploaded file and its metadata (Mantis #2708).
- **Duplicate**: a file or transaction already present such that importing it again would create repeated data.

## Requirements

### FR-IMP-001 — Multi-file upload
- The system shall allow the user to select and upload multiple files in a single submission.

### FR-IMP-002 — Parser selection and parameters
- The system shall require the user to select a parser/format.
- The system shall collect any parser-specific parameters required to parse (e.g., FA bank account selection).

### FR-IMP-003 — Store uploaded files and metadata
- The system shall store each uploaded file and record associated metadata (filename, size, upload user/date, parser type, selected bank account where applicable).

### FR-IMP-004 — Duplicate detection and decision
- The system shall detect duplicates and prevent accidental import.
- The system shall provide a user decision step allowing:
  - skipping duplicates; and/or
  - forcing re-upload of selected duplicates.
- The system shall support “force upload all” to bypass duplicate blocking for the entire submission.

### FR-IMP-005 — Parse to statements
- The system shall parse each uploaded file into one or more statement objects.
- The system shall report parse failures without crashing the entire import flow.

### FR-IMP-006 — Account resolution integration
- If parsed statements contain unresolved bank account identifiers, the system shall prompt the user to map them to FA bank accounts before import proceeds.
- Saved associations shall be auto-applied when available.

(See: Bank Account Resolution feature docs.)

### FR-IMP-007 — Import statements into staging
- For each parsed statement, the system shall insert a new staging statement when it does not exist, or update the existing statement when it does.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

### FR-IMP-008 — Import transactions into staging
- For each parsed statement transaction, the system shall insert a new staging transaction when it does not exist.
- The system shall not create duplicate staging transactions for duplicates detected by the transaction model.

### FR-IMP-009 — Link uploaded file to statements
- When a file is imported successfully, the system shall link the uploaded file record to the staging statement record(s) created/updated from that file.

### FR-IMP-010 — User feedback
- The system shall present a per-statement import summary (new/imported vs existing/updated; counts of inserted/duplicate/updated transactions).

## Implementation Anchors
- Import UI / orchestration: [import_statements.php](../../import_statements.php)
- Statement and transaction staging models: [class.bi_statements.php](../../class.bi_statements.php), [class.bi_transactions.php](../../class.bi_transactions.php)
- Uploaded file linking: File upload service usage in [import_statements.php](../../import_statements.php)
- Duplicate resolution and account resolution steps are session-driven within [import_statements.php](../../import_statements.php)
