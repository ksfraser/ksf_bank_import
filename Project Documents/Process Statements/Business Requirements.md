# Business Requirements — Process Bank Transactions

## Background / Problem Statement
After statements are imported into staging, users must convert each staged bank transaction into correct accounting entries in FrontAccounting, or link it to an existing entry when it already exists.

This step must support multiple real-world processing patterns:
- Supplier payments and refunds
- Customer receipts, including invoice allocation
- Quick Entry-based GL posting
- Bank transfers (including multi-currency target amount)
- Manual settlement and confirmation of automatic matches

## Business Goals
- BR-PROC-001 — Enable users to reliably process each staged bank transaction into the correct FA transaction type or link it to an existing entry.
- BR-PROC-002 — Reduce reconciliation effort by supporting automatic matching confirmation and manual settlement.
- BR-PROC-003 — Support common accounting entry types (supplier/customer/quick entry/transfers) directly from the processing screen.
- BR-PROC-004 — Provide auditability by retaining linkage from staged transaction → FA transaction type/number and exposing view links.
- BR-PROC-005 — Allow correction workflows (unset/reset, toggle debit/credit) without requiring direct DB edits.
- BR-PROC-006 — Preserve and formalize cross-book/inter-company routing examples in documentation so operational knowledge is not tied to inline TODO comments in controller code.

## In Scope
- Processing actions for partner types: SP, CU, QE, BT, MA, ZZ.
- Paired transfer processing (process both sides).
- Master data assist: add customer / add vendor.
- Reset/correction actions.

## Out of Scope
- Improving the matching algorithm itself (unless required as defect fix).
- Full bank reconciliation module within FA.

## Success Metrics
- Reduced time from import → posted ledger.
- Lower error rate in transaction classification.
- Fewer manual DB corrections.

## Constraints
- Must use FA’s transaction-writing functions where available.
- Must operate under FA permissions model.

## Documentation Baseline
- Legacy inline TODO examples in [process_statements.php](../../process_statements.php) are treated as non-authoritative reminders.
- Authoritative routing examples and intent now live in [Intercompany Routing Examples.md](Intercompany%20Routing%20Examples.md).
