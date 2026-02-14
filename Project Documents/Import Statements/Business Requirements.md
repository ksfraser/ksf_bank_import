# Business Requirements — Import Bank Statements

## Background / Problem Statement
Manual entry of bank transactions is error-prone and slow. The module supports importing bank statement files into staging so transactions can be reviewed and processed into FrontAccounting.

The import flow must also cope with real-world conditions:
- Users upload multiple files at once.
- Duplicate files/transactions occur.
- Files can contain bank-account identifiers that don’t match FA’s configured bank account numbers.

## Business Goals
- BR-IMP-001 — Enable users to import one or more bank statement files into staging in a repeatable, auditable way.
- BR-IMP-002 — Prevent accidental re-import (duplicates) while still allowing a controlled override when needed.
- BR-IMP-003 — Preserve traceability from uploaded file → imported statement(s) for audit and troubleshooting.
- BR-IMP-004 — Allow completion of import even when embedded account identifiers don’t match FA bank accounts, via user mapping.

## In Scope
- Upload one or more files and parse into statement objects.
- Duplicate detection and user decision (block vs. force).
- Account resolution (prompt for mapping; optional persistence is handled by that feature).
- Write statements/transactions to module staging tables.
- Link uploaded file records to imported statement records.

## Out of Scope
- Converting staged transactions into FA transactions (see Process Statements feature).
- Changes to FA core schema beyond module-owned storage.

## Success Metrics
- Reduced time to bring bank data into FA.
- Reduced frequency of “duplicate import” errors.
- Improved ability to audit which file produced which statements.

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.

## Assumptions
- Users have the correct permissions and have configured FA bank accounts.
- At least one supported parser exists for the bank’s file format.

## Constraints
- Must work in the deployed FA environment (FA 2.3.22 / PHP 7.3).
- Must not lose uploaded file data before parsing/import completes.
