# Use Case: Manage Uploaded Bank Files

## 1. Use Case Summary

**Goal**: Allow users to review, download, and delete uploaded bank statement files stored by the module.

**Primary Actor**: Accountant / Bookkeeper

**Entry Point (UI)**: “Manage Uploaded Files” menu item

**Implementation Anchor**: [manage_uploaded_files.php](../../manage_uploaded_files.php)

## 2. Preconditions
- User has permission to view bank files.
- At least zero files exist (empty state is allowed).

## 3. Trigger
- User navigates to the Manage Uploaded Bank Files screen.

## 4. Main Success Scenario
1. User opens the Manage Uploaded Bank Files screen.
2. System displays storage statistics (total files, total storage, latest/first upload).
3. User optionally applies filters:
   - Upload user
   - Date range
   - Parser type
4. System lists uploaded files with metadata (filename, upload date/user, size, parser type, bank account, linked statement count).
5. User performs one of the supported actions:
   - Download the file
   - View file details
   - Delete the file

## 5. Extensions

### E1. Download an uploaded file
1. User clicks Download for a file.
2. System streams the stored file to the browser.

### E2. Delete an uploaded file
1. User clicks Delete for a file.
2. System deletes the stored file and its metadata record.
3. System confirms deletion.

### E3. Filter results
1. User selects filter values and clicks Filter.
2. System refreshes list and pagination based on filters.

## 6. Postconditions
- Downloads do not modify state.
- Deletes remove stored file content and associated metadata.

## 7. Notes
- Uploaded file storage and linking was introduced under Mantis #2708.
- Import flow links uploaded files to statements during import (see import use case).

## 8. Related Use Cases
- Import statements into staging (creates uploaded file records): [Project Documents/Import Statements/Use Case - Import Bank Statements.md](../Import%20Statements/Use%20Case%20-%20Import%20Bank%20Statements.md)
