# FR-003: Amount Matching Tolerance

Bank transfer amounts MUST match within `MATCH_AMOUNT_TOLERANCE` (configured value, default 0.05).

- Requirement: automatic amount-based pair detection tolerance for paired transfers (FR-001 group)
- Design: `AmountMatchRule` + `PairedTransferProcessor` (see ARCH-001)
- Tests: TC-003-A to TC-003-D
- UAT: UAT-003
