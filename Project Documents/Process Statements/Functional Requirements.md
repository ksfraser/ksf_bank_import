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

### FR-PROC-014 — Inter-company routing examples maintained outside controller
- The project shall maintain a canonical set of practical routing examples (including cross-book and organization-specific mappings) in project documentation.
- The examples shall include at minimum the known legacy scenarios (Square→CIBC likely FHS, FHS expense QE→FHS, Creative Memories payments→CM books).
- The canonical location for these examples shall be [Intercompany Routing Examples.md](Intercompany%20Routing%20Examples.md), not inline TODOs in [process_statements.php](../../process_statements.php).

### FR-PROC-015 — Paired dual-side action extracted to SRP class

## 2026-02-14 Update
- Transaction and link URL generation is centralized into single-responsibility builders.
- Environment-safe URL handling removes hardcoded host and application path dependencies.
- Matched, manual, BT, QE, customer, and supplier flow link rendering is aligned to shared notification/link helpers.
- Test expectations for UAT readiness are updated: any skipped test outside the baseline is treated as a failure.
- Parsing/validation of dual-side transfer POST payload shall be implemented in a dedicated SRP class.
- Controller file [process_statements.php](../../process_statements.php) shall keep only a one-line marker comment at the former inline block location while baseline compatibility mode is active.
- The extracted class shall be: [src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php](../../src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php).

### FR-PROC-016 — Legacy inline POST handlers extracted to SRP action classes
- Legacy inline handlers for `UnsetTrans`, `AddCustomer`, `AddVendor`, and `ToggleTransaction` shall each have a dedicated SRP action class.
- During baseline-compat mode, [process_statements.php](../../process_statements.php) shall retain current inline behavior and include commented one-line invocation markers for the extracted classes.
- Extracted classes:
  - [src/Ksfraser/FaBankImport/Actions/UnsetTransactionAction.php](../../src/Ksfraser/FaBankImport/Actions/UnsetTransactionAction.php)
  - [src/Ksfraser/FaBankImport/Actions/AddCustomerAction.php](../../src/Ksfraser/FaBankImport/Actions/AddCustomerAction.php)
  - [src/Ksfraser/FaBankImport/Actions/AddVendorAction.php](../../src/Ksfraser/FaBankImport/Actions/AddVendorAction.php)
  - [src/Ksfraser/FaBankImport/Actions/ToggleTransactionAction.php](../../src/Ksfraser/FaBankImport/Actions/ToggleTransactionAction.php)

### FR-PROC-017 — Four-column transaction row layout
- The transaction review row shall render in four columns for unprocessed transactions:
  - Details,
  - Operation,
  - Partner / Actions,
  - Matching GLs.
- Column order and header labels shall remain stable to support operator workflow and UAT reproducibility.

### FR-PROC-018 — Backward compatibility for legacy left/right APIs
- Legacy methods `getLeftTd`, `getLeftHtml`, `display_left`, `getRightTd`, `getRightHtml`, and `display_right` shall remain available during migration.
- New four-column methods may be introduced, but they shall not remove or rename legacy methods while baseline compatibility mode is active.

### FR-PROC-019 — Render-fragment migration policy
- Rendering internals shall progressively move from output side effects toward `HtmlElement`/`HtmlFragment` return values.
- Legacy `display*`/`toHtml` entry points shall remain, delegating to `render*`/fragment-producing methods where available.
- Remaining `HtmlOB` usage is accepted as transitional technical debt and shall be tracked until fully removed.

### FR-PROC-020 — Process statements date-range responsiveness instrumentation
- The process statements page shall include server-side timing instrumentation for transaction fetch and render phases to support diagnosis of date-range timeout issues.
- The controller shall send no-cache response headers for this page to reduce stale-response ambiguity during troubleshooting.

## Known Technical Backlog (Tracked)
- Remove residual `HtmlOB` capture points in [class.bi_lineitem.php](../../class.bi_lineitem.php) once equivalent fragment-returning render methods are complete.
- Complete TODO for paired-transfer normalization comment in [class.bi_lineitem.php](../../class.bi_lineitem.php).
- Refactor `our_bank_accounts` array dependency to class-based bank account model in [class.bi_lineitem.php](../../class.bi_lineitem.php).
- Review/remove debug label output in operation rendering path after acceptance verification.

## Implementation Anchors
- Page/controller: [process_statements.php](../../process_statements.php)
- Handler routing: [src/Ksfraser/FaBankImport/TransactionProcessor.php](../../src/Ksfraser/FaBankImport/TransactionProcessor.php)
- Handlers: [src/Ksfraser/FaBankImport/handlers](../../src/Ksfraser/FaBankImport/handlers)
- Inter-company examples: [Intercompany Routing Examples.md](Intercompany%20Routing%20Examples.md)
- Paired action SRP class: [src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php](../../src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php)
- Legacy inline action SRP classes: [src/Ksfraser/FaBankImport/Actions](../../src/Ksfraser/FaBankImport/Actions)
