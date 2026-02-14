# UAT Test Plan — Process Bank Transactions

## Objective
Validate that staged transactions can be processed into the correct FA transactions (or linked to existing ones) using each supported partner type flow, and that correction workflows work.

## Scope
- Partner types: SP, CU, QE, BT, MA, ZZ
- Paired transfer processing
- Unset/reset, toggle debit/credit
- Links to view created/linked transactions

## Test Data / Setup
- Imported staging transactions exist for each scenario below (can be created by importing statement files designed to produce the required patterns).
- FA master data exists:
  - At least one supplier
  - At least one customer + branch
  - At least one Quick Entry template
  - At least two FA bank accounts to transfer between
- Optional: an existing FA entry to link against (for MA/ZZ cases).

## Test Cases

### UAT-PROC-001 — Supplier payment (SP, Debit)
Steps:
1. Select an unprocessed staged transaction with Debit (D).
2. Choose partner type SP and select a supplier.
3. Process.
Expected:
- Supplier payment is created.
- Staging is marked processed with correct FA type/no.
- GL view link works.

### UAT-PROC-002 — Supplier refund (SP, Credit)
Steps:
1. Select a Credit (C) staged transaction.
2. Choose SP and select supplier.
3. Process.
Expected:
- Bank deposit representing refund is created.
- Staging updated; GL link works.

### UAT-PROC-003 — Customer payment (CU, Credit)
Steps:
1. Select a Credit (C) staged transaction.
2. Choose CU and select customer (and branch if prompted).
3. Process.
Expected:
- Customer payment created.
- Receipt link works.

### UAT-PROC-004 — Customer payment allocation to invoice
Steps:
1. Use a CU transaction and provide an invoice number.
2. Process.
Expected:
- Payment is created and allocated.

### UAT-PROC-005 — Customer processing rejects Debit
Steps:
1. Select a Debit (D) staged transaction.
2. Choose CU.
3. Process.
Expected:
- User-visible error; no transaction created; staging remains unprocessed.

### UAT-PROC-006 — Quick Entry creates bank payment/deposit
Steps:
1. Choose QE and select a Quick Entry template.
2. Process Debit and Credit examples.
Expected:
- Correct FA bank transaction created for each.

### UAT-PROC-007 — Bank transfer (BT) outbound/inbound
Steps:
1. Choose BT and select the other bank account.
2. Process one Debit (outbound) and one Credit (inbound) example.
Expected:
- Bank transfer created with correct direction.

### UAT-PROC-008 — Manual settlement (MA) links to existing entry
Steps:
1. Choose MA.
2. Enter Existing Type and Existing Entry.
3. Process.
Expected:
- No new transaction created.
- Staging links to the specified existing entry.

### UAT-PROC-009 — Matched confirmation (ZZ)
Steps:
1. Choose ZZ.
2. Provide matched trans type/no.
3. Process.
Expected:
- Staging links to the matched entry.

### UAT-PROC-010 — Unset/reset clears linkage
Steps:
1. Process any staged transaction.
2. Click Unset/Reset.
Expected:
- Staging returns to unprocessed state.
- FA linkage fields cleared.

### UAT-PROC-011 — Toggle debit/credit
Steps:
1. Choose a staged transaction.
2. Toggle debit/credit.
Expected:
- Transaction DC indicator changes and UI reflects updated processing constraints.

### UAT-PROC-012 — Process both sides (paired transfer)
Steps:
1. Identify a paired transfer candidate.
2. Click Process both sides.
Expected:
- Both sides are recorded consistently.

### UAT-PROC-013 — Inter-company routing examples documented
Steps:
1. Open [Intercompany Routing Examples.md](Intercompany%20Routing%20Examples.md).
2. Verify examples include the legacy operational intents:
  - Square to CIBC likely FHS.
  - FHS expense QE is FHS.
  - Creative Memories payments route to CM books.
3. Confirm examples are specific enough for a bookkeeper to choose SP/CU/QE/BT/MA/ZZ routing.
Expected:
- Operational examples are present, readable, and maintained in docs (not only code comments).
- Team can classify at least one transaction per listed pattern without consulting source code TODOs.

### UAT-PROC-014 — Paired dual-side action extraction verified
Steps:
1. Confirm [process_statements.php](../../process_statements.php) contains a one-line marker comment where the inline paired-transfer chunk previously lived.
2. Open [src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php](../../src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php).
3. Verify unit tests cover supports/id extraction/action extraction behavior.
Expected:
- Controller remains baseline-compatible.
- Paired dual-side action logic is isolated in an SRP class and test-covered.

### UAT-PROC-015 — Legacy inline handlers extraction markers verified
Steps:
1. Confirm [process_statements.php](../../process_statements.php) includes commented one-line marker calls for extracted actions near `UnsetTrans`, `AddCustomer`, `AddVendor`, and `ToggleTransaction` inline blocks.
2. Verify corresponding classes exist in [src/Ksfraser/FaBankImport/Actions](../../src/Ksfraser/FaBankImport/Actions).
3. Verify unit tests cover supports/execute behavior.
Expected:
- Inline behavior remains intact for baseline compatibility.
- Equivalent SRP action classes exist and are test-covered for future activation.

### UAT-PROC-016 — Four-column transaction row layout
Steps:
1. Open Process Statements with unprocessed rows.
2. Verify each unprocessed row presents four logical sections in order: Details, Operation, Partner/Actions, Matching GLs.
3. Verify the table header reflects the same four-column structure.
Expected:
- Four-column layout is consistently rendered.
- Operator can locate controls and matching information without horizontal ambiguity.

### UAT-PROC-017 — Legacy left/right compatibility paths remain callable
Steps:
1. Execute existing workflow paths that rely on legacy row rendering wrappers (`display_left`, `display_right`, related `getLeft*`/`getRight*` methods).
2. Confirm no fatal errors/warnings are emitted during normal rendering.
Expected:
- Legacy compatibility methods remain available and functional while migration is in progress.

### UAT-PROC-018 — Render-fragment delegation remains behaviorally equivalent
Steps:
1. For a representative sample (SP, CU, QE, BT, MA, ZZ), compare rendered controls/links before and after delegation changes.
2. Verify presence of action controls, hidden fields, and expected labels per partner type.
Expected:
- Delegation to fragment/render methods does not change user-visible business behavior.

### UAT-PROC-019 — Date-range search responsiveness diagnostics available
Steps:
1. Perform a date-range search expected to return a larger dataset.
2. Verify the page responds and transaction list refreshes.
3. Confirm server logs include fetch/render timing markers for the request.
Expected:
- Search request completes without ambiguous stale-cache behavior.
- Timing diagnostics are available for troubleshooting slow cases.

## Exit Criteria
- All UAT test cases pass.
- No workflow requires DB edits to recover from common errors.
