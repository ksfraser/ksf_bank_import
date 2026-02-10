# UAT Test Plan — Bank Account Resolution

## Objective
Validate that bank statement imports do not fail when the imported file’s detected account identifier does not match any FA bank account number, by prompting for a mapping and optionally persisting it.

## Scope
- Import flow: Upload → (Duplicate review if needed) → Parse → Account Resolution (if needed) → Import.
- Multi-file uploads.

## Test Data
- At least 2 FA bank accounts exist in the target company.
- One QFX/OFX file containing a detected account identifier that does not match any FA `bank_account_number`.
- One QFX/OFX file containing a detected account identifier that already matches.
- Optional: duplicate file scenario to ensure flow ordering.

## Test Cases

### UAT-ACCT-001 — Unresolved detected account prompts resolution
Steps:
1. Upload a file with a detected account identifier not present in FA.
2. Complete parsing.
Expected:
- Account Resolution screen is shown.
- Detected identifier is displayed.
- User cannot proceed to Import without selecting an FA bank account.

### UAT-ACCT-002 — Mapping applies to current import
Steps:
1. On the resolution screen, select an FA bank account.
2. Proceed and then Import.
Expected:
- Import proceeds without account mismatch failure.

### UAT-ACCT-003 — Remember association persists and auto-resolves
Steps:
1. On the resolution screen, select an FA bank account.
2. Leave “Remember/Associate” checked.
3. Proceed and complete Import.
4. Upload the same (or similar) file again.
Expected:
- No Account Resolution screen (or fewer rows) if mapping covers the detected identifier.

### UAT-ACCT-004 — Cancel returns to upload
Steps:
1. Trigger account resolution.
2. Click Cancel.
Expected:
- User returns to the upload form.
- No import is performed.

### UAT-ACCT-005 — Multi-file upload with mixed accounts
Steps:
1. Upload multiple files where one contains resolvable detected account and another contains unresolvable.
Expected:
- Resolution screen shows only the unresolvable detected identifier(s).

### UAT-ACCT-006 — Duplicate review occurs before account resolution
Steps:
1. Upload multiple files including at least one duplicate (warn mode).
2. Choose actions on duplicate review screen and proceed.
Expected:
- Duplicate review screen appears first.
- After parsing completes, account resolution screen appears if needed.

## Exit Criteria
- All test cases pass.
- No new hard-fail blocks the import flow for account mismatch.
