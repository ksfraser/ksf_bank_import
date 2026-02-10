# UAT Test Plan — View Imported Statements

## Objective
Validate that users can query and view imported statement headers over a date range.

## Test Data / Setup
- At least one statement imported into staging.

## Test Cases

### UAT-INQ-001 — Search returns statements in range
Steps:
1. Open View Bank Statements.
2. Select a date range that includes imported statements.
3. Search.
Expected:
- Statements appear in results.

### UAT-INQ-002 — Delta displayed
Steps:
1. View results.
Expected:
- Delta column equals end balance minus start balance.

## Exit Criteria
- All test cases pass.
