# Functional Requirements — Process Bank Transactions

## Feature
Review and process staged bank transactions into FA transactions, or link them to existing FA entries.

## Definitions
- **Staged transaction**: a row in `bi_transactions` representing a bank statement line.
- **Processed**: staging status indicates the staged transaction has been linked/created in FA (e.g., status=1).
- **Partner Type**: operation type selection controlling which handler/flow is used (SP/CU/QE/BT/MA/ZZ).

## Requirements

### FR-PROC-001 — Display and filter staged transactions
- The system shall display staged bank transactions with key details.
- The system shall allow filtering by processing status.

### FR-PROC-002 — Partner type selection
- For each unprocessed staged transaction, the system shall allow selecting a partner type from the supported set:
  - SP, CU, QE, BT, MA, ZZ.

### FR-PROC-003 — Validate “our bank account” exists
- Before processing, the system shall validate that the staged transaction’s `our_account` corresponds to an FA bank account.
- If missing, processing shall be blocked with a user-visible error.

### FR-PROC-004 — Supplier processing (SP)
- When partner type is SP:
  - For Debit (D) the system shall create an FA Supplier Payment.
  - For Credit (C) the system shall create an FA Bank Deposit representing a supplier refund.
- The system shall update staging with the created FA transaction type/number.

### FR-PROC-005 — Customer processing (CU)
- When partner type is CU:
  - The system shall only allow Credit (C) transactions.
  - The system shall create an FA Customer Payment.
  - If an invoice number is supplied, the system shall allocate the payment to the invoice.
- The system shall update staging with the created FA transaction type/number.

### FR-PROC-006 — Quick Entry processing (QE)
- When partner type is QE:
  - The system shall create an FA Bank Payment for Debit (D) or Bank Deposit for Credit (C).
  - The system shall load and apply the selected Quick Entry template.
  - The system shall apply bank charges when present.
- The system shall update staging with the created FA transaction type/number.

### FR-PROC-007 — Bank transfer processing (BT)
- When partner type is BT:
  - The system shall create an FA Bank Transfer.
  - Direction shall be derived from debit/credit (inbound vs outbound).
  - The system shall calculate target amount when accounts have different currencies.
- The system shall update staging with the created FA transaction type/number.

### FR-PROC-008 — Manual settlement (MA)
- When partner type is MA:
  - The system shall require existing FA entry type and number.
  - The system shall link the staged transaction to that existing FA entry.
  - The system shall not create a new FA transaction.

### FR-PROC-009 — Matched confirmation (ZZ)
- When partner type is ZZ:
  - The system shall require matched FA transaction type and number.
  - The system shall link the staged transaction to that entry.
  - The system shall not create a new FA transaction.

### FR-PROC-010 — Provide view links
- After a successful create/link action, the system shall display a link to view the associated FA transaction (GL view).
- For customer payments, the system shall additionally provide the receipt/invoice view link.

### FR-PROC-011 — Correction actions
- The system shall support:
  - Unset/reset a processed transaction (clear FA linkage and mark unprocessed).
  - Toggle the debit/credit indicator for a staged transaction.

### FR-PROC-012 — Paired transfer processing
- The system shall support a “Process both sides” action to process paired bank transfer entries together.

### FR-PROC-013 — Master data assist
- The system shall support “Add Customer” and “Add Vendor” actions when needed.

## Implementation Anchors
- Page/controller: [process_statements.php](../../process_statements.php)
- Handler routing: [src/Ksfraser/FaBankImport/TransactionProcessor.php](../../src/Ksfraser/FaBankImport/TransactionProcessor.php)
- Handlers: [src/Ksfraser/FaBankImport/handlers](../../src/Ksfraser/FaBankImport/handlers)
