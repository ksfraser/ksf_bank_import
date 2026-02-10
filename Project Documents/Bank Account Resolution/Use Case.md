# Use Case — Resolve Detected Bank Account

## Use Case ID
UC-BI-ACCT-RES-001

## Name
Resolve detected bank account for imported statement(s)

## Primary Actor
Accounting User (FrontAccounting user importing bank statements)

## Stakeholders and Interests
- Accounting User: wants imports to succeed without creating many new FA bank accounts.
- Finance/Admin: wants consistent mapping and minimal admin overhead.
- Audit/Compliance: wants predictable behavior and traceability of mappings.

## Preconditions
- Bank Import module is installed and user can access the import page.
- User uploads one or more statement files (e.g., QFX/OFX) for parsing.

## Trigger
After parsing, the module detects at least one account identifier from the file that does not match any existing FrontAccounting bank account number.

## Main Success Scenario
1. User uploads file(s) and parsing completes.
2. System detects one or more account identifiers (e.g., ACCTID) that cannot be matched to an FA bank account.
3. System displays a resolution screen listing the file(s) and detected account identifier(s).
4. User selects the correct FA bank account for each detected identifier.
5. User optionally selects “Remember/Associate” for one or more detected identifiers.
6. System applies the selected mapping to the parsed statements for the remainder of the current import flow.
7. System persists any selected “Remember/Associate” mapping(s) internally to the module.
8. User proceeds to Import.

## Extensions / Alternate Flows
A1. User cancels resolution
- System discards the resolution session and returns to the upload form.

A2. Previously saved mapping exists
- System applies saved association(s) automatically and only prompts for any remaining unresolved detected identifiers.

A3. Duplicate upload warning flow occurs
- System completes duplicate review/force upload step first.
- After parsing is completed, system runs this use case if unresolved detected identifiers remain.

## Postconditions
- Success:
  - Import can proceed using a valid FA bank account number for each statement.
  - Optional: saved mappings exist for future imports.
- Failure:
  - User cannot proceed to Import until all required mappings are selected.

## Business Rules
- The module must not create new FrontAccounting bank accounts as part of this use case.
- Saved associations are internal to the module.
