# Requirements Traceability Matrix (RTM) — Bank Account Resolution

This RTM traces Business Requirements (BR) → Functional Requirements (FR) → UAT test cases.

## Traceability Table

| Business Requirement | Functional Requirement(s) | UAT Test Case(s) |
|---|---|---|
| BR-ACCT-001 — Complete import despite mismatched detected account IDs | FR-001, FR-002, FR-003, FR-004, FR-008 | UAT-ACCT-001, UAT-ACCT-002, UAT-ACCT-005, UAT-ACCT-006 |
| BR-ACCT-002 — Do not require creating new FA bank accounts | FR-007 | (Coverage via negative expectation in all UAT-ACCT tests) |
| BR-ACCT-003 — Reduce repeated manual mapping work | FR-005, FR-006 | UAT-ACCT-003 |

## Notes
- FR references are defined in [Project Documents/Bank Account Resolution/Functional Requirements.md](Functional%20Requirements.md).
- UAT references are defined in [Project Documents/Bank Account Resolution/UAT Test Plan.md](UAT%20Test%20Plan.md).
