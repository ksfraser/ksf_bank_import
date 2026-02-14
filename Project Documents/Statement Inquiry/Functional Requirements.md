# Functional Requirements — View Imported Statements

## Requirements

### FR-INQ-001 — Date range filter
- The system shall allow selecting a From/To date.

### FR-INQ-002 — Query staged statements
- The system shall query staging statements in `bi_statements` within the selected date range.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

### FR-INQ-003 — Display statement headers
- The system shall display bank, statementId, date, account/currency, start balance, end balance, and delta.

## Implementation Anchor
- Screen: [view_statements.php](../../view_statements.php)
