# Functional Requirements — Import Run Audit Log

## Feature
Record a structured audit log for each import run and provide a permission-gated, read-only viewer.

## Definitions
- **Import run**: one end-to-end attempt to upload/parse/import one or more files.
- **JSONL**: JSON Lines; one JSON object per line.

## Requirements

### FR-LOG-001 — Create a run log
- The system shall create a new log file when an import run begins.
- The log file shall be created under a company-scoped directory.

### FR-LOG-002 — Structured log records
- The system shall write log records as JSONL.
- Each log record shall include:
  - `ts` (timestamp)
  - `run_id`
  - `event`
  - `context` (object)

### FR-LOG-003 — Record key import events
- The system shall record key events including (at minimum):
  - run start
  - per-file upload outcomes
  - per-file parse outcomes
  - duplicate decision outcomes
  - account resolution required/applied
  - import start/completion and per-statement summaries

### FR-LOG-004 — Persist log across multi-step flows
- The system shall continue writing to the same run log across multi-step UI flows (e.g., duplicate review and account resolution) for the same run.

### FR-LOG-005 — Non-blocking behavior
- Failures to write to the audit log shall not prevent the import flow from continuing.

### FR-LOG-006 — View logs permission
- The system shall restrict log viewing to users with the appropriate permission.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

### FR-LOG-007 — Read-only log viewer
- The system shall provide a read-only UI to:
  - list available import run logs (newest first)
  - display the contents of a selected log file
- The UI shall validate requested filenames to prevent path traversal.

## Implementation Anchors
- Logger service: [src/Ksfraser/FaBankImport/Service/ImportRunLogger.php](../../src/Ksfraser/FaBankImport/Service/ImportRunLogger.php)
- Producer wiring: [import_statements.php](../../import_statements.php)
- Viewer UI: [view_import_logs.php](../../view_import_logs.php)
- Permission/menu wiring: [hooks.php](../../hooks.php)
