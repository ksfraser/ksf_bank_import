# UAT Test Plan — Import Run Audit Log

## Scope
Verify that import runs generate an audit log and that authorized users can view logs via the UI.

## Roles
- Accounting User (can import)
- Admin/Auditor (can view logs)

## Test Cases

### UAT-LOG-001 — Log created on upload/parse
**Preconditions**: user can access Import Statements.
1. Upload a valid statement file.
2. Complete parse stage.
**Expected**:
- A new log file exists under `company/#/bank_imports/logs`.
- The log contains a `run.started` (or equivalent) entry and per-file entries.

### UAT-LOG-002 — Duplicate decision is logged
**Preconditions**: have a file that triggers duplicate detection.
1. Upload a duplicate file.
2. Choose **Ignore** for the duplicate.
3. Repeat and choose **Upload again anyway**.
**Expected**:
- Log records reflect the duplicate review requirement and the ignore/force actions.

### UAT-LOG-003 — Account resolution is logged
**Preconditions**: use a file with a detected account not matching an FA bank account.
1. Upload file.
2. Observe account resolution prompt.
3. Map detected account to an FA bank account; choose remember.
**Expected**:
- Log contains an entry indicating account resolution was required.
- Log contains an entry indicating mappings were applied (and remembered count).

### UAT-LOG-004 — Import results are logged
1. Complete an import run for at least one statement.
**Expected**:
- Log contains import start/completion entries.
- Log contains per-statement transaction summary entries.

### UAT-LOG-005 — Permission-gated viewer
1. Log in as user without log-view permission; attempt to open View Import Logs.
2. Log in as user with log-view permission; open View Import Logs.
**Expected**:
- Unauthorized user is denied.
- Authorized user sees list of logs and can view contents.

### UAT-LOG-006 — Viewer is read-only
1. In View Import Logs, open a log.
**Expected**:
- No UI actions exist to modify/delete log files.
- Contents display is limited to safe rendering.
