# UAT Test Plan — Manage Uploaded Bank Files

## Objective
Validate the uploaded-file management screen supports listing, filtering, downloading, and deletion.

## Test Data / Setup
- At least one uploaded file exists (perform an import first).

## Test Cases

### UAT-UPL-001 — List shows uploaded file
Steps:
1. Open Manage Uploaded Files.
Expected:
- File appears with correct metadata.

### UAT-UPL-002 — Filter by date/user/parser
Steps:
1. Apply filters.
Expected:
- List updates to matching files.

### UAT-UPL-003 — Download works
Steps:
1. Click Download for a file.
Expected:
- File downloads successfully and content matches original.

### UAT-UPL-004 — Delete removes file
Steps:
1. Delete a file.
Expected:
- File no longer appears in list.

## Exit Criteria
- All test cases pass.
