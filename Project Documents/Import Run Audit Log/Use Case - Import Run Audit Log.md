# Use Case — Import Run Audit Log

## Use Case ID
UC-LOG-001

## Name
Record and Review Import Run Audit Log

## Primary Actor
- **Admin / Auditor** (review)
- **Accounting User** (initiates import; log is recorded automatically)

## Stakeholders
- Accounting users
- Controller / Auditor
- System administrator

## Preconditions
- User can access Bank Import module.
- For review: user has permission to view logs.

## Trigger
- User uploads bank file(s) for import; or
- Admin/auditor navigates to view import logs.

## Main Success Scenario
1. User uploads one or more bank statement files.
2. System starts a new **import run** and creates a log file for the run.
3. System records key events for the run (upload, duplicate decision, parsing, account resolution, import results).
4. System completes the import run.
5. Admin/auditor opens the **View Import Logs** screen.
6. System lists available import run logs (newest first).
7. Admin/auditor selects a log file and views it in read-only mode.

## Alternate / Exception Flows
- A1: Duplicate files detected
  - System records that duplicates require a user decision.
  - System records whether each duplicate was ignored or force-uploaded.

- A2: Bank account resolution required
  - System records that account resolution was required.
  - System records selected mappings and whether mappings were remembered.

- E1: Logging failure
  - If log directory/file creation fails, system continues import without blocking.
  - (Optional) System can show a warning to admins (implementation-dependent).

## Postconditions
- A run log exists for the import attempt (when logging is available).
- Authorized users can review logs without the ability to modify them.

## Notes
- Logs are intended for audit/troubleshooting, not as the system-of-record (database remains authoritative).
