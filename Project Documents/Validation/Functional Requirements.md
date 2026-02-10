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

### FR-VAL-005 — Flag for review
- The system shall allow the user to flag a staged transaction for review.
- The system shall allow the user to clear a flag.

## Implementation Anchor
- Screen: [validate_gl_entries.php](../../validate_gl_entries.php)
