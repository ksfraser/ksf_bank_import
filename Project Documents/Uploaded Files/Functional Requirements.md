# Functional Requirements — Manage Uploaded Bank Files

## Requirements

### FR-UPL-001 — List uploaded files
- The system shall display a paginated list of uploaded files with metadata (original filename, upload date, uploader, size, parser type, bank account where available).

### FR-UPL-002 — Filter uploaded files
- The system shall support filtering by uploader, date range, and parser type.

### FR-UPL-003 — Show traceability to statements
- The system shall display how many imported statements are linked to each uploaded file.

### FR-UPL-004 — Download
- The system shall allow downloading a stored uploaded file.

### FR-UPL-005 — Delete
- The system shall allow deleting a stored uploaded file.

## Implementation Anchor
- Screen: [manage_uploaded_files.php](../../manage_uploaded_files.php)
