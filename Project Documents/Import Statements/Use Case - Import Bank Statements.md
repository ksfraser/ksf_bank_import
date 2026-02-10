# Use Case: Import Bank Statements

## 1. Use Case Summary

**Goal**: Import one or more bank statement files into the module’s staging tables so that they can be reviewed and processed into FrontAccounting (FA) transactions.

**Primary Actor**: Accountant / Bookkeeper

**Supporting Systems**:
- FrontAccounting (session, UI widgets, bank accounts, transaction writing functions)
- Bank Import module parsers
- Uploaded file storage subsystem (Mantis #2708)

**Entry Point (UI)**: “Import Bank Statements” menu item

**Implementation Anchor**: [import_statements.php](../../import_statements.php)

## 2. Scope

### In Scope
- Multi-file upload in a single action
- Parser selection and parser-specific parameters (e.g., bank account)
- Duplicate detection and resolution (block vs. force)
- “Bank account resolution” step (map detected account identifiers to FA bank accounts)
- Import of statements and transactions into staging tables
- Link uploaded files to imported statements

### Out of Scope
- Processing staged transactions into FA (see separate use case)
- Long-term reconciliation workflows beyond creating/linking transactions

## 3. Preconditions
- User is authenticated in FA and has permission to import bank statements.
- At least one FA Bank Account exists (used for selection and/or mapping).
- The user has a statement file in a supported format.

## 4. Trigger
- User navigates to the import page and submits the upload form.

## 5. Main Success Scenario (Happy Path)
1. User opens the Import Bank Statements screen.
2. User selects a **Format** (parser).
3. User provides any required parser parameters (e.g., bank account).
4. User selects one or more files and submits **Upload**.
5. System stores the uploaded files (and records metadata).
6. System parses each file into one or more “statement” objects.
7. System validates that statements can be associated with an FA bank account (directly or via mapping).
8. System writes to staging:
   - Each statement is inserted/updated in `bi_statements`.
   - Each statement transaction is inserted into `bi_transactions` unless identified as a duplicate.
9. System links each uploaded file record to its imported statements (uploaded file ↔ statement relationship).
10. System displays an import summary per statement and returns the user to the module.

## 6. Extensions / Alternate Flows

### A1. Duplicate files detected (user decision required)
**Condition**: A file appears to be a duplicate of a previously uploaded file.

1. System blocks importing that file and records it in a “pending duplicates” session state.
2. System presents a Duplicate Review screen where the user can:
   - Skip duplicates; and/or
   - Force upload specific duplicates.
3. System re-runs parsing for the user-approved duplicates.
4. Continue at Step 6 of the Main Success Scenario.

**Notes**:
- There is also a “force upload all” checkbox that bypasses duplicate blocking for all selected files.
- Pending duplicate resolution is session-based.

### A2. Bank account identifiers in the file do not match FA bank accounts (resolve instead of hard fail)
**Condition**: Parsed statements include an account identifier (e.g., `acctid` or `account`) that does not exist in FA.

1. System presents the Account Resolution screen:
   - Shows detected account IDs (by file).
   - For each detected account, user selects an FA bank account to use.
   - User can optionally check “Remember” to persist the association.
2. System applies the mapping to the parsed statements (updates the in-memory `statement->account` field used for import).
3. System continues with import (Main Success Scenario Step 8).

**Implementation anchor**:
- Account mapping logic: [src/Ksfraser/FaBankImport/Service/StatementAccountMappingService.php](../../src/Ksfraser/FaBankImport/Service/StatementAccountMappingService.php)
- Association key generation: [src/Ksfraser/FaBankImport/Service/DetectedAccountAssociationKey.php](../../src/Ksfraser/FaBankImport/Service/DetectedAccountAssociationKey.php)

### A3. User cancels Account Resolution
**Condition**: User chooses “Cancel” on the mapping screen.

1. System clears the account-resolution session state.
2. System returns to the upload/import flow without importing the statements.

### A4. Partial parse failure
**Condition**: One file parses successfully but another fails.

1. System reports parse errors for failed files.
2. System imports statements from successfully parsed files.
3. System provides a summary and allows the user to retry failed files.

(Exact user-visible messaging depends on parser error handling; behavior should avoid fatals.)

## 7. Postconditions
### Success
- Staged statements exist in `bi_statements`.
- Staged transactions exist in `bi_transactions`.
- Uploaded file records exist and are linked to statements.
- Duplicate transactions are not re-inserted.

### Failure
- No staged data is written for the failing file(s).
- Pending duplicate/account resolution states may exist for user follow-up.

## 8. Business Rules / Constraints (Observed)
- A statement may be inserted or updated based on whether its `statementId` already exists.
- A transaction is inserted only if it is not identified as a duplicate by `bi_transactions_model::trans_exists()`.
- Account mapping is required for import to proceed when file account IDs do not correspond to FA bank account numbers.
- “Remember” writes association values to module config storage (internal to the module).

## 9. Data Elements (High-Level)
- Statement identifiers: bank, statementId, dates, balances, currency, account/account id.
- Transaction identifiers: transactionAmount, transactionDC, valueTimestamp, memo/title, transactionCode.
- Upload metadata: original filename, size, upload user/date, parser type, selected bank account.

## 10. Related Use Cases
- Process staged transactions into FA: [Project Documents/Process Statements/Use Case - Process Bank Transactions.md](../Process%20Statements/Use%20Case%20-%20Process%20Bank%20Transactions.md)
- Manage uploaded files: [Project Documents/Uploaded Files/Use Case - Manage Uploaded Bank Files.md](../Uploaded%20Files/Use%20Case%20-%20Manage%20Uploaded%20Bank%20Files.md)
- View imported statements: [Project Documents/Statement Inquiry/Use Case - View Imported Statements.md](../Statement%20Inquiry/Use%20Case%20-%20View%20Imported%20Statements.md)
