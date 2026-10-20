# Business Requirements Document — Root FaBankImport Module (legacy FA-glue + parsers + processors)

## Preamble

`src/Ksfraser/FaBankImport/*.php` root-level files implement the FA-glue (legacy class names, parsers per-bank, processors, factories) that wires the entire module to the FA lifecycle. This doc defines coverage for those 36 files (parse-broken legacy files are excluded and annotated).

## Subsystem overview

### Legacy class files (`class.*.php`)
- `class.bi_transactions.php` — FA `bi_transactions` DB row + global registered in FA (BR-002 / FR-005 / FR-006)
- `class.bi_transaction.php` — the bi_transaction (CIBC) domain fields + process helpers (BR-002 / FR-005 / FR-006)
- `class.ViewBiLineItems.php` — one bi_lineitem row → html (FR-009, IR-001, ARCH-008)
- `class.transactions_table.php` — shared html table for line-iterations (ARCH-009)
- `class.bank_import_controller.php` — legacy pre-namespacing version of BankImportController (ARCH-004, FR-014)
- `class.AbstractQfxParser.php` + `class.{Cibc,Manu,Pcmc}QfxParser.php` — per-bank QFX parsers of `ofx`/`qfx` files (FR-007, ARCH-010, ARCH-007)
- `class.QfxParserFactory.php` — parser factory (FR-049 / ARCH-004)

### Application bootstrap / processors / factories
- `Application.php`, `Bootstrap.php`, `command_bootstrap.php`, `Container.php` — app entry (FR-015 / ARCH-001) — controller-style entrypoint (ARCH-004, ARCH-007)
- `TransationProcessor.php`, `Transaction.php`, `TransactionProcessedEvent.php`, `TransactionFactory.php`, `TransactionFactoryImp.php`, `FactoryTransactions.php` — transaction ingestion pipeline + factory (BR-002 / FR-005 / FR-006)
- `CustomerTransaction.php`, `TransactionTypeFactory.php` — transaction-type registries and customer transactions (BR-002, FR-004)
- `FaGLWrapper.php` — FA GL lifecycle wrapper (FR-006 IR-002) — creates/updates 111/112/22/etc. GL entries on transfer/insertion into FA

### Controllers/Views glue
- `BankImportController.php` — controller (FR-010 UI operation-types and FR-011 processing action, ARCH-009) — parse-broken legacy file
- `header_table.php`, `view_statements.php` — FA views for page header / statements (IR-001, FR-009)
- `import_statements.php` — procedural import flow (FR-006 / ARCH-007)
- `process_statements_preclean.php` — FA session-based process controller (FR-006, ARCH-007)
- `manage_partners_data.php` — FA page for partner-data management (BR-001, BR-011)
- `hooks.php` — FA plugin hook registration (`install_options`, `process_transaction_hook` etc.) (FR-049, ARCH-004)
- `test.php`, `class.bi_transactionTitle_model.copilot.php`, `class.bi_partners_data.copilot.php`, `import_statements.copilot.php`, `process_statements.copilot_refactored.php` — dev/scratch/copilot drafts (NFR-016 / not in production paths)

### Excluded (parse errors at HEAD)
- `class.bi_transactions.copilot.php`

## BABOK tagging status

All root files excluding the above `copilot`/scratch filed craft:
- File-level `@BABOK RTM: <refs>; file: …; coverage: docs/TestCases/` (rollup)
- Per-method `@BABOK <refs> — method: <name>`
- Class-level `@BABOK CLASS ROLLUP: …`
