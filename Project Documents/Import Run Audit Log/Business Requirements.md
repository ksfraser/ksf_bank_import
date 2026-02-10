# Business Requirements — Import Run Audit Log

## Background / Problem Statement
Bank imports can fail or behave unexpectedly due to duplicates, format issues, or mismatched bank account identifiers. Without a run-level audit trail, troubleshooting is slow and relies on user recollection.

## Business Goals
- BR-LOG-001 — Provide a per-import-run audit trail for troubleshooting and compliance.
- BR-LOG-002 — Allow authorized users to review import run logs in a read-only UI.
- BR-LOG-003 — Store logs in a company-scoped location to support multi-company FrontAccounting setups.

## In Scope
- Start and record an audit log for each import attempt.
- Store logs under the company’s data directory.
- Read-only inquiry screen to list/view logs.
- Permission gating for log viewing.

## Out of Scope
- Editing/deleting logs via UI.
- Centralized aggregation across companies.
- Automatic log retention policies (can be added later).

## Success Metrics
- Faster resolution of import issues.
- Reduced need for direct server access to inspect logs.

## Assumptions
- Server filesystem is available for company-scoped persistent storage.

## Constraints
- Must not block the import flow if logging fails.
- Must be compatible with deployed FA/PHP environment.
