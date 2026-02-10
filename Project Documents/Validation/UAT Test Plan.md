# UAT Test Plan — Validate GL Entries

## Objective
Validate that the module can detect missing GL entries, mismatched amounts, and allow flagging for review.

## Test Data / Setup
- At least one processed staged transaction linked to a real FA entry.
- At least one staged transaction with an intentionally broken linkage (or a known missing type/no).
- Optional: a staged transaction with known variance.

## Test Cases

### UAT-VAL-001 — Validate all returns success when clean
Steps:
1. Open Validate GL Entries.
2. Validate all.
Expected:
- Summary shows 0 issues when system is clean.

### UAT-VAL-002 — Missing GL entry is reported
Steps:
1. Ensure a staged transaction references a missing FA entry.
2. Validate all.
Expected:
- Result shows FAILED with missing GL entry.

### UAT-VAL-003 — Amount mismatch is reported
Steps:
1. Ensure a staged transaction’s linked GL does not match bank amount.
2. Validate all.
Expected:
- Variance displayed and highlighted.

### UAT-VAL-004 — Flag for review
Steps:
1. From validation results, flag a transaction.
Expected:
- Transaction appears in flagged list.

### UAT-VAL-005 — Clear flag
Steps:
1. Clear a flagged transaction.
Expected:
- Flag removed.

## Exit Criteria
- All test cases pass.
