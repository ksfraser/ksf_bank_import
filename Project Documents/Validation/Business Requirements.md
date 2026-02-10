# Business Requirements — Validate GL Entries

## Background / Problem Statement
After processing, staged bank transactions are linked to FA transactions. Users need a way to verify the linkage is correct (exists, amounts align) and to surface problems early.

## Business Goals
- BR-VAL-001 — Detect missing or invalid FA linkages from staged transactions.
- BR-VAL-002 — Detect material mismatches (amount variance) between bank transactions and linked GL entries.
- BR-VAL-003 — Provide a workflow to flag issues for follow-up.

## In Scope
- Validate all processed transactions (optionally scoped to a statement).
- Report errors/warnings and suggested matches.
- Flag transactions for review.

## Out of Scope
- Automatically repairing mismatches.

## Constraints
- Must not modify FA transactions during validation.
