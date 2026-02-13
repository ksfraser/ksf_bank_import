# Use Case: Process Bank Transactions

## 1. Use Case Summary

**Goal**: Review staged bank transactions and either (a) create the correct FA transaction(s) or (b) link the bank transaction to an existing FA entry.

**Primary Actor**: Accountant / Bookkeeper

**Supporting Systems**:
- FrontAccounting (customers, suppliers, bank accounts, GL, payments, deposits, allocations)
- Bank Import module staging + matching + transaction handlers

**Entry Point (UI)**: “Process Bank Statements” menu item

**Implementation Anchors**:
- Page/controller: [process_statements.php](../../process_statements.php)
- Routing/strategy: [src/Ksfraser/FaBankImport/TransactionProcessor.php](../../src/Ksfraser/FaBankImport/TransactionProcessor.php)
- Handlers:
  - Supplier: [src/Ksfraser/FaBankImport/handlers/SupplierTransactionHandler.php](../../src/Ksfraser/FaBankImport/handlers/SupplierTransactionHandler.php)
  - Customer: [src/Ksfraser/FaBankImport/handlers/CustomerTransactionHandler.php](../../src/Ksfraser/FaBankImport/handlers/CustomerTransactionHandler.php)
  - Quick Entry: [src/Ksfraser/FaBankImport/handlers/QuickEntryTransactionHandler.php](../../src/Ksfraser/FaBankImport/handlers/QuickEntryTransactionHandler.php)
  - Bank Transfer: [src/Ksfraser/FaBankImport/handlers/BankTransferTransactionHandler.php](../../src/Ksfraser/FaBankImport/handlers/BankTransferTransactionHandler.php)
  - Manual Settlement: [src/Ksfraser/FaBankImport/handlers/ManualSettlementHandler.php](../../src/Ksfraser/FaBankImport/handlers/ManualSettlementHandler.php)
  - Matched Confirmation: [src/Ksfraser/FaBankImport/handlers/MatchedTransactionHandler.php](../../src/Ksfraser/FaBankImport/handlers/MatchedTransactionHandler.php)

## 2. Scope

### In Scope
- Listing staged transactions (processed/unprocessed)
- Displaying suggested matches and partner forms
- Processing actions for partner types:
  - SP (Supplier)
  - CU (Customer)
  - QE (Quick Entry)
  - BT (Bank Transfer)
  - MA (Manual Settlement)
  - ZZ (Matched)
- Special actions:
  - Process both sides of paired bank transfer
  - Toggle debit/credit indicator
  - Unset/reset a processed transaction
  - Create customer/supplier master data from a transaction

### Out of Scope
- Importing files into staging (see import use case)
- Management/reporting of uploaded files (separate use case)

## 3. Preconditions
- There are transactions in staging (`bi_transactions`) from one or more imported statements.
- FA bank accounts are configured.
- Where relevant, customers, suppliers, quick entry templates, and/or other bank accounts exist.

## 4. Trigger
- User opens the Process Bank Statements screen and selects an action on a transaction.

## 5. Main Success Scenario (Review and process one transaction)
1. User opens the Process Bank Statements screen.
2. User filters the list (date/status) as needed.
3. System displays each staged transaction with:
   - Transaction details (amount, date, memo/title, etc.)
   - A partner-type selector and partner details form
   - Matching suggestions (when available)
4. User selects the appropriate **Partner Type** and required details.
5. User clicks **Process**.
6. System validates:
   - Required form fields are present (depends on partner type).
   - The transaction’s “our account” exists in FA.
7. System delegates processing to the correct handler.
8. Handler either:
   - Writes a new FA transaction; or
   - Links to an existing FA transaction.
9. System updates staging (`bi_transactions`) as “processed” and stores linkage (`fa_trans_type`, `fa_trans_no`, flags such as created/matched/manual).
10. System displays a success message and relevant FA links (e.g., GL view, receipt view).

## 6. Extensions / Alternate Flows (By Partner Type)

### E1. Supplier (SP)
**User intent**: Record a supplier payment/refund.

**Inputs**:
- Supplier ID (`partnerId_*`)

**Behavior**:
- If transaction is **Debit (D)**: creates Supplier Payment (`ST_SUPPAYMENT`).
- If transaction is **Credit (C)**: creates a Bank Deposit (`ST_BANKDEPOSIT`) representing supplier refund, posting to the supplier payable account.
- Updates partner data for the supplier.

**Outputs**:
- New FA transaction created; staging updated to processed.


### E2. Customer (CU)
**User intent**: Record a customer receipt/payment.

**Constraints**:
- Customer processing is **Credit-only**. If not credit, the handler returns an error.

**Inputs**:
- Customer ID (`partnerId_*`)
- Optional branch (`partnerDetailId`)
- Optional invoice number (`invoice`)

**Behavior**:
- Creates Customer Payment (`ST_CUSTPAYMENT`).
- If an invoice is specified, allocates the payment against that invoice.
- Updates partner data (customer/branch).

**Outputs**:
- New FA customer payment; optional allocation performed; staging updated.


### E3. Quick Entry (QE)
**User intent**: Use FA Quick Entries to create a GL bank payment or deposit.

**Inputs**:
- Quick Entry template ID (`partnerId_*`)
- Optional comment

**Behavior**:
- If transaction is **Debit (D)**: creates Bank Payment (`ST_BANKPAYMENT`).
- If transaction is **Credit (C)**: creates Bank Deposit (`ST_BANKDEPOSIT`).
- Loads the Quick Entry template into an items cart (`qe_to_cart`).
- Optionally logs the bank transaction reference via 0.01 offset entries when enabled by config.
- Applies bank charges to the configured bank charge account when charge is non-zero.

**Outputs**:
- New FA GL/bank transaction; staging updated.


### E4. Bank Transfer (BT)
**User intent**: Record an internal transfer between bank accounts.

**Inputs**:
- Other bank account ID (`partnerId_*`) representing the counter-account.
- Optional comment

**Behavior**:
- Determines direction by Debit/Credit:
  - **Credit (C/B)**: money comes into our account (From partner → To our account).
  - **Debit (D)**: money leaves our account (From our account → To partner).
- Creates FA bank transfer (`ST_BANKTRANSFER`).
- Computes `target_amount` via bank transfer amount calculator (supports different currencies).
- Updates staging and bank partner data.

**Outputs**:
- New FA bank transfer; staging updated.


### E5. Manual Settlement (MA)
**User intent**: Link the staged bank transaction to an existing FA entry.

**Inputs**:
- Existing FA entry type (`Existing_Type`)
- Existing FA entry number (`Existing_Entry`)

**Behavior**:
- Looks up the counterparty from the existing FA entry.
- Updates staging to point to the existing FA transaction (does not create a new entry).
- Optionally updates partner data with memo.

**Outputs**:
- Staging updated; GL view link provided.


### E6. Matched Confirmation (ZZ)
**User intent**: Confirm an automatically matched transaction.

**Inputs**:
- Matched FA transaction number (`transNo`)
- Matched FA transaction type (`transType`)
- Optional partnerId for tracking

**Behavior**:
- Confirms linkage to the matched FA entry (does not create a new transaction).
- Updates partner data based on counterparty.

**Outputs**:
- Staging updated; GL view link provided.


## 7. Special Actions

### S1. Process both sides of a paired transfer
**User intent**: When a bank transfer appears as two related staged transactions, process them together.

**Behavior**:
- Uses PairedTransferProcessor service to create both sides consistently.
- Displays a GL entry link on success.

**Implementation anchor**: invoked from [process_statements.php](../../process_statements.php) via `ProcessBothSides`.

### S2. Unset (reset) a processed transaction
**User intent**: Remove the association between the staged transaction and the FA transaction.

**Behavior**:
- Resets status and clears linkage fields.

**Implementation anchor**: `UnsetTrans` handling in [process_statements.php](../../process_statements.php) (or command pattern when enabled).

### S3. Toggle debit/credit indicator
**User intent**: Correct a bank’s malformed DC indicator.

**Behavior**:
- Flips the transaction’s debit/credit indicator.

### S4. Add Customer / Add Vendor
**User intent**: Create missing master data directly from a transaction.

**Behavior**:
- Creates a customer or supplier record (implementation depends on controller/command path).

## 8. Postconditions
### Success
- The staged transaction is marked processed.
- `fa_trans_type` and `fa_trans_no` are set when linked/created.
- Created/matched/manual flags are updated.

### Failure
- Staged transaction remains unprocessed.
- User sees a handler validation error (missing required fields, invalid DC type, missing bank account).

## 9. Key Business Rules / Constraints (Observed)
- Staged transaction must reference an FA bank account (`our_account`) that exists.
- Customer (CU) processing only supports Credit transactions.
- Manual settlement requires both existing type and entry number.
- Bank transfer direction is derived from Debit/Credit.

## 10. Related Use Cases
- Import statements into staging: [Project Documents/Import Statements/Use Case - Import Bank Statements.md](../Import%20Statements/Use%20Case%20-%20Import%20Bank%20Statements.md)
- Validate created/linkage integrity: [Project Documents/Validation/Use Case - Validate GL Entries.md](../Validation/Use%20Case%20-%20Validate%20GL%20Entries.md)

## 11. Architecture / Implementation Notes (Current)
- Menu view include in [process_statements.php](../../process_statements.php) uses a cross-platform file-path strategy:
  - prefer `views/module_menu_view.php`
  - fallback to `Views/module_menu_view.php`
- Partner type options are initialized from legacy hardcoded defaults for baseline compatibility, then may be overridden by discovered types via `PartnerTypeRegistry` when available.
- Operational routing examples previously captured as inline TODO narrative are maintained in [Intercompany Routing Examples.md](Intercompany%20Routing%20Examples.md).
- Paired dual-side transfer POST handling is extracted to [src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php](../../src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php), including payload parsing, processor invocation, and legacy UI notification dispatch helper.
- During baseline-compat mode, [process_statements.php](../../process_statements.php) retains a one-line marker comment in the former inline paired-transfer location instead of restoring the full block.
- Additional legacy inline handlers are also represented by SRP action classes in [src/Ksfraser/FaBankImport/Actions](../../src/Ksfraser/FaBankImport/Actions): `UnsetTransactionAction`, `AddCustomerAction`, `AddVendorAction`, and `ToggleTransactionAction`.
- For baseline compatibility, one-line invocation markers are kept commented in [process_statements.php](../../process_statements.php) adjacent to each corresponding inline block.
