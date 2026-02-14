# Business Requirements — Bank Account Resolution

## Background / Problem Statement
Some bank statement formats embed an account identifier in the file (e.g., QFX/OFX `ACCTID`). In practice, these identifiers may not match FrontAccounting’s `bank_account_number` values due to formatting, masking, or account-number differences (e.g., credit card identifiers).

Today, this mismatch can block the import flow.

## Business Goals
- BR-ACCT-001 — Allow users to complete statement imports even when the file’s detected account identifier does not directly match an FA bank account number.
- BR-ACCT-002 — Avoid proliferating new FA bank account records just to satisfy import matching.
- BR-ACCT-003 — Reduce repeated manual work by allowing the module to remember mappings.

## In Scope
- Provide a user-facing resolution step that allows mapping detected account identifiers to an existing FA bank account.
- Persist these mappings internally to the module.

## Out of Scope
- Automatically creating new FrontAccounting bank accounts.
- Bulk account creation or editing FA bank account master data.
- Complex reconciliation rules beyond choosing an existing FA bank account.

## Success Metrics
- Import completion rate increases for mismatched-account files.
- Reduced support incidents related to “account not found / mismatch” failures.
- Reduced repeated mapping work for recurring statement sources.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

## Assumptions
- Users can identify which FA bank account corresponds to a detected identifier.
- FA bank accounts already exist for the user’s real accounts.

## Constraints
- Must work in FrontAccounting 2.3.22 / PHP 7.3 environment.
- Mappings must be stored internally to the module (no FA schema changes required beyond module-owned tables).
