# Requirements Traceability Matrix (RTM) — Import Bank Statements

This RTM traces Business Requirements (BR) → Functional Requirements (FR) → UAT test cases.

## Traceability Table

| Business Requirement | Functional Requirement(s) | UAT Test Case(s) |
|---|---|---|
| BR-IMP-001 — Import files into staging | FR-IMP-001, FR-IMP-002, FR-IMP-005, FR-IMP-007, FR-IMP-008, FR-IMP-010 | UAT-IMP-001, UAT-IMP-002 |
| BR-IMP-002 — Prevent accidental duplicate imports with controlled override | FR-IMP-004, FR-IMP-008 | UAT-IMP-003, UAT-IMP-004, UAT-IMP-007 |
| BR-IMP-003 — Trace uploaded file → statements | FR-IMP-003, FR-IMP-009 | UAT-IMP-006 |
| BR-IMP-004 — Complete import despite embedded account mismatch | FR-IMP-006 | UAT-IMP-005 |

## Notes
- Account resolution is specified in the Bank Account Resolution feature docs and is referenced here as a dependency.
