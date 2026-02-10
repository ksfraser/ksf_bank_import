# UAT Test Plan — Import Bank Statements

## Objective
Validate that users can upload, parse, and import one or more bank statement files into staging, with correct handling of duplicates, account resolution, and file traceability.

## Scope
- Upload form (parser selection, parameters, multi-file selection)
- Duplicate detection and override flow
- Account resolution prompt when needed
- Staging insert/update behavior
- Uploaded file → statements linking

## Test Data / Setup
- At least 2 FA bank accounts exist.
- At least 2 statement files:
  - One known-good supported format for a single statement.
  - One file that will be detected as a duplicate (same as prior upload or constructed to match duplicate criteria).
- Optional: one file whose embedded account identifier does not match any FA bank account number.

## Test Cases

### UAT-IMP-001 — Import single file (happy path)
Steps:
1. Go to Import Bank Statements.
2. Select parser/format and required parameters.
3. Upload one supported file.
Expected:
- File is stored and parsed.
- Staging statements and transactions are inserted/updated.
- Import summary is displayed.

### UAT-IMP-002 — Import multiple files in one submission
Steps:
1. Upload 2+ supported files at once.
Expected:
- Each file is stored, parsed, and imported.
- Import summary reflects each statement.

### UAT-IMP-003 — Duplicate detected triggers duplicate review flow
Steps:
1. Upload a file that the system detects as duplicate (without checking “force upload all”).
Expected:
- System shows duplicate review screen.
- User can choose to skip and/or force the duplicate.

### UAT-IMP-004 — Force upload all bypasses duplicate blocking
Steps:
1. Select “Upload anyway (force re-upload)”.
2. Upload files including duplicates.
Expected:
- Duplicate review screen is bypassed.
- Parsing/import proceeds for all selected files.

### UAT-IMP-005 — Account resolution prompts when embedded account is unknown
Steps:
1. Upload a file whose detected account identifier does not match any FA bank account number.
Expected:
- Account Resolution screen appears before import.
- User must select an FA bank account for each unresolved detected identifier.

### UAT-IMP-006 — File-to-statement traceability exists
Steps:
1. Import a file.
2. Open Manage Uploaded Files.
Expected:
- Uploaded file list includes the file.
- “Statements”/linked statement count is non-zero for that file.

### UAT-IMP-007 — Transactions are not duplicated on re-import
Steps:
1. Import a file.
2. Import the same file again.
Expected:
- Staging does not insert duplicate transactions.
- Summary indicates duplicates detected.

## Exit Criteria
- All UAT test cases pass.
- No fatal errors; failures are presented as user-visible errors with recovery path.
