# Functional Requirements — Manage Uploaded Bank Files

## Requirements

### FR-UPL-001 — List uploaded files
- The system shall display a paginated list of uploaded files with metadata (original filename, upload date, uploader, size, parser type, bank account where available).

### FR-UPL-002 — Filter uploaded files
- The system shall support filtering by uploader, date range, and parser type.

### FR-UPL-003 — Show traceability to statements
- The system shall display how many imported statements are linked to each uploaded file.

### FR-UPL-004 — Download

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.
- The system shall allow downloading a stored uploaded file.

### FR-UPL-005 — Delete
- The system shall allow deleting a stored uploaded file.

## Implementation Anchor
- Screen: [manage_uploaded_files.php](../../manage_uploaded_files.php)
