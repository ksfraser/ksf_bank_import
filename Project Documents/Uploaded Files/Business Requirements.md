# Business Requirements — Manage Uploaded Bank Files

## Background / Problem Statement
When users import bank statement files, auditors and support often need to know exactly which file was uploaded, when, by whom, and what statements it produced. Users also need simple lifecycle management (download, delete).

## Business Goals
- BR-UPL-001 — Provide an audit trail of uploaded bank files and their metadata.
- BR-UPL-002 — Allow users to retrieve (download) a previously uploaded file for review.
- BR-UPL-003 — Allow users to remove uploaded files when appropriate (retention/cleanup), with appropriate permissions.

## In Scope
- List uploaded files with filters.
- Download file content.
- Delete file and its stored content.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

## Out of Scope
- Changing the imported statement/transaction data itself.

## Constraints
- Must respect FA permissions and module security areas.
