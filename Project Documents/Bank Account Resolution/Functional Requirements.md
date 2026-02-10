# Functional Requirements — Bank Account Resolution

## Feature
Resolve detected bank account identifiers from imported files to existing FrontAccounting bank accounts.

## Definitions
- Detected account identifier: account value obtained from parsed statement objects (prefer `statement.acctid`, else `statement.account`).
- FA bank account: an existing FrontAccounting bank account record (selected by user) used for the import.

## Requirements

### FR-001 — Detect unresolved accounts
- After parsing uploaded file(s), the system shall collect unique detected account identifiers across all parsed statements.
- The system shall determine whether each detected identifier is resolvable to an FA bank account number.

### FR-002 — Prompt for resolution
- If any detected identifier is not resolvable, the system shall display an Account Resolution screen before the user can proceed to Import.
- The screen shall list the file(s) associated with each detected identifier and show the detected identifier.

### FR-003 — Select FA bank account
- For each unresolved detected identifier, the system shall provide a dropdown list of FA bank accounts.
- The user shall be required to select a bank account for every unresolved detected identifier.

### FR-004 — Apply mapping for current import
- When the user proceeds, the system shall apply the selected mapping to the parsed statements for the remainder of the current import flow.
- The mapping application shall update the statement’s bank-account number used by downstream processing.

### FR-005 — Persist association (optional)
- For each detected identifier, the screen shall include a “Remember/Associate” checkbox.
- If selected, the system shall persist an internal association so future imports can auto-resolve the detected identifier.

### FR-006 — Auto-apply saved associations
- On subsequent imports, the system shall apply any saved associations automatically and only prompt for remaining unresolved identifiers.

### FR-007 — No FA bank account creation
- The system shall not create, modify, or delete FrontAccounting bank account records as part of this feature.

### FR-008 — Interaction with duplicate review
- If the duplicate review/force upload step occurs, account resolution shall occur after parsing completes, using the final set of parsed statements.

## Non-Functional Notes
- Persisted association keys must be stable and bounded in length for storage.
- The resolution step must be usable for multi-file uploads.
