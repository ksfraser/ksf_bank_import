# Functional Requirements — Validate GL Entries

## Requirements

### FR-VAL-001 — Validate linkage exists
- The system shall verify that each staged processed transaction references a valid FA transaction type/number.

### FR-VAL-002 — Validate amounts
- The system shall calculate and report variance between the staged bank amount and the linked GL amount.

### FR-VAL-003 — Report warnings
- The system shall report non-fatal warnings such as date/account discrepancies (where detectable).

### FR-VAL-004 — Suggested matches
- When validation fails, the system shall provide suggested matches when available.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

### FR-VAL-005 — Flag for review
- The system shall allow the user to flag a staged transaction for review.
- The system shall allow the user to clear a flag.

## Implementation Anchor
- Screen: [validate_gl_entries.php](../../validate_gl_entries.php)
