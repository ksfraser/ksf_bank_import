# Functional Requirements — View Imported Statements

## Requirements

### FR-INQ-001 — Date range filter
- The system shall allow selecting a From/To date.

### FR-INQ-002 — Query staged statements
- The system shall query staging statements in `bi_statements` within the selected date range.

### FR-INQ-003 — Display statement headers
- The system shall display bank, statementId, date, account/currency, start balance, end balance, and delta.

## Implementation Anchor
- Screen: [view_statements.php](../../view_statements.php)
