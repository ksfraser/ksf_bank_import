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

## Exit Criteria
- All UAT test cases pass.
- No workflow requires DB edits to recover from common errors.
