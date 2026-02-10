# Requirements Traceability Matrix (RTM) — Process Bank Transactions

This RTM traces Business Requirements (BR) → Functional Requirements (FR) → UAT test cases.

## Traceability Table

| Business Requirement | Functional Requirement(s) | UAT Test Case(s) |
|---|---|---|
| BR-PROC-001 — Process staged transactions into correct FA entries | FR-PROC-001, FR-PROC-002, FR-PROC-003, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-010 | UAT-PROC-001, UAT-PROC-002, UAT-PROC-003, UAT-PROC-006, UAT-PROC-007 |
| BR-PROC-002 — Reduce reconciliation effort via matching/settlement | FR-PROC-008, FR-PROC-009 | UAT-PROC-008, UAT-PROC-009 |
| BR-PROC-003 — Support common entry types | FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007 | UAT-PROC-001, UAT-PROC-002, UAT-PROC-003, UAT-PROC-006, UAT-PROC-007 |
| BR-PROC-004 — Auditability and linkage to FA | FR-PROC-010 | (Covered by all UAT-PROC tests that validate view links) |
| BR-PROC-005 — Correction workflows without DB edits | FR-PROC-011, FR-PROC-012 | UAT-PROC-010, UAT-PROC-011, UAT-PROC-012 |

## Notes
- Handler-specific behavior is implemented via the TransactionProcessor + handler classes.
