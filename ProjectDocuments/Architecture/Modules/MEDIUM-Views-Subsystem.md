# Business Requirements Document — Views Subsystem (47 files)

## Preamble

The `src/Ksfraser/FaBankImport/Views/**` directory renders the entire bank-import UI inside FrontAccounting (FA) session pages, enabling the use-case set UC-001..008, and implements FR-009 / FR-010 / FR-011 for visual indicators and operation selection, IR-001 web-interface, ARCH-009 UI architecture, and ARCH-008 for audit trail colour-coding.

## Subsystem overview

### Component buttons & rows
- `AddCustomerButton`/`AddVendorButton` (+`…Row`) — post-action buttons for partner creation (UC-002)
- `ProcessTransactionButtonRow` + `UnsetTransButtonRow` — process / unset one or many pairs (UC-001, FR-011)
- `SubmitButton(Row)` — generic submit with `onclick` preview support (FR-011, IR-001)
- `AddNoButton` — add-no (skip) transaction processing

### Labels / data fields
- `Comment`, `TransDate`, `TransTitle`, `TransType`, `TransactionCustomerDetails`, `OtherBankAccount`, `OurBankAccount`, `MatchingGLS`, `AmountCharges` — one class per label/value display pair (FR-009/FR-010)
- `LabelRowBase` — shared label-row styling contract (ARCH-009)
- `HTML_ROW`, `HTML_ROW_LABELDecorator` — FA table row primitive + decorator (ARCH-009)
- `HtmlTransactionView` — HTML-view for one bi-lineitem (FR-008 + ARCH-008 audit trail)

### Views
- `PartnerType`, `PartnerTypeDisplayStrategy`, `PartnerTypeViewInterface` — polymorphic partner-type display strategy (UC-002, ARCH-001)
- `CustomerPartnerTypeView`, `SupplierPartnerTypeView`, `QuickEntryPartnerTypeView`, `BankTransferPartnerTypeView` — concrete partner type views (UC-002..006)
- `PartnerSubSelect` — quick-entry sub-select dropdown (FR-010)
- `AllocatableInvoicesTable` — allocation detail rows (UC-007)
- `DisplaySettledTransactions` — settled transaction results display (FR-008)
- `ImportUploadForm` — imported-file upload form (UC-004, ARCH-008)
- `ProcessStatementsView` — statement processing input modals (UC-008)
- `ViewFactory` — member-instance-based factory of rows/views (ARCH-001)
- `TransactionTypeLabel`, `TransactionCustomerDetails` — labels for pair processing (BR-002)
- `BiLineItemView` — abstract wrapper around bi-lineitem data representing partner selection per transaction (FR-009)
- `Operation` (parse-broken legacy — excluded from tagging but referenced for RTM)

### DataProviders sub-dir
- `CustomerDataProvider`, `SupplierDataProvider`, `QuickEntryDataProvider`, `PartnerDataProviderInterface` — context provider contracts (IR-001/BR-011-consistent data flow) serving the partner-matching views

## BABOK tagging status

All 47 view files (46-tagged, Operation.php excluded as pre-parse-broken). Tagging pattern per file:
- File-level rollup `@BABOK RTM: FR-009 / IR-001 / ARCH-008 …`
- Per-method `@BABOK <refs> — method: …` tags (or line inside legacy FA docblocks)
- Class-level `@BABOK CLASS ROLLUP: <Class> — <N> methods; <Ref> (xCount)`
