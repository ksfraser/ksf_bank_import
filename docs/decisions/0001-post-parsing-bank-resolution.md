# ADR 0001: Post-Parsing Bank Account Resolution

## Status
Accepted (2026-02-17)

## Context
In the KSF Bank Import module, some file formats (like QFX/OFX) contain bank account information within the file content. Users traditionally upload these files without pre-selecting a FrontAccounting bank account.

With the introduction of persistent file metadata tracking (Mantis #2708), the system attempts to save a record for the uploaded file *during* the initial upload/parsing phase. At this point, the destination bank account might not yet be known (either because it wasn't selected in the UI or because the file hasn't been parsed yet).

## Decision
We will allow file metadata records in `bi_uploaded_files` to be created with a `NULL` `bank_account_id`. This ensures that the upload and duplicate detection logic can run even if the target account is unknown.

Once the bank account is resolved (via auto-matching from the file content or user selection during the resolution phase), the system will "back-fill" the `bank_account_id` in the `bi_uploaded_files` table.

## Consequences

### Positive
- **Unblocks Upload**: Users can continue their existing workflow of uploading files without pre-selections.
- **Improved Tracking**: Every upload is tracked immediately, even if incomplete.
- **Accurate Metadata**: The metadata record is updated as soon as the account is identified.

### Negative
- **Temporary State**: Records in the database will temporarily have `NULL` values until the import session is completed.
- **Added Complexity**: Requires an additional "update" step in the account resolution logic.

## Strategy
1. Modify `DatabaseUploadedFileRepository` to handle `NULL` for `bank_account_id`.
2. Add `updateBankAccountId()` method to both Repository and Service.
3. Update `import_statements.php` to invoke the update after the user confirms account mapping.
