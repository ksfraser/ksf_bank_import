# Business Requirements — View Imported Statements

## Background / Problem Statement
Users need a quick inquiry view to confirm statement headers were imported and to review balances and date coverage.

## Business Goals
- BR-INQ-001 — Allow users to view imported statement headers over a date range.
- BR-INQ-002 — Provide a simple audit indicator of statement delta (end-start balance).

## In Scope
- Date-range query of staged statements.
- Read-only display of statement header fields.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

## Out of Scope
- Editing statements.

## Constraints
- Must be read-only.
