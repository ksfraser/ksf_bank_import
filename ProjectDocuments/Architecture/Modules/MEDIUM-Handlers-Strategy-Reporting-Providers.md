# Business Requirements Document — Handlers / Strategy / Reporting / DataProviders / OperationTypes / cron

## Preamble

Coverage requirements for orchestration, duplicate policy, and reporting modules:

- `src/Ksfraser/FaBankImport/Handlers/` — per transaction-type handlers (auto-discovery via `TransactionHandlerInterface`) implementing FR-049
- `src/Ksfraser/FaBankImport/Strategy/` — duplicate-mode strategies (Allow/Warn/Block) implementing BR-011-ACCESS of FR-048
- `src/Ksfraser/FaBankImport/Reporting/` — reporting services for matched & operation results (FR-012, ARCH-007)
- `src/Ksfraser/FaBankImport/Views/DataProviders/` — partner/quick-entry/vendor data providers (FR-009/IR-001)
- `src/Ksfraser/FaBankImport/OperationTypes/` — operation types registry (FR-013)
- `src/Ksfraser/FaBankImport/cron/monitor_performance.php` — cron-based performance monitor (ARCH-007)

## Subsystem Overview

| Module | Role | Requirement refs |
|---|---|---|
| Handlers/TransactionHandlerInterface | Interface ALL handlers implement (auto-discovery registration) | FR-049, ARCH-004 |
| Handlers/{Abstract,BankTransfer,Customer,Supplier,QuickEntry,ManualSettlement,Matched}TransactionHandler | One class per transaction type | FR-049 / BR-002 |
| Handlers/ErrorHandler + ProcessTransactionCommandHandler | Command-bus support | FR-014, ARCH-004 |
| Strategy/{Allow,Warn,Block}DuplicateStrategy + Factory | Duplicate-mode strategy pattern | FR-050 / ARCH-003 |
| Reporting/{MatchingReport, OperationReport, ReportingService} | Report rendering / service | FR-012, ARCH-007 |
| Views/DataProviders/{Customer,Supplier,QuickEntry,PartnerInterface}DataProvider | Data provider contract | FR-009/IR-001 |
| OperationTypes/OperationTypesRegistry | FR-013 registry | FR-013 |
| cron/monitor_performance.php | Cron-driven performance monitor | FR-012/ARCH-007 |

## BABOK tagging status

- 10 Handlers, 5 Strategy, 3 Reporting, 4 DataProviders, 1 OperationTypes, 1 cron — all tagged:
  - File-level `@BABOK RTM: ...` rollup
  - Per-method `@BABOK <refs> — method: <name>`
  - Class-level `@BABOK CLASS ROLLUP: <Cls> — N methods; <Ref> (xCount)`

## Requirements traceability

| Req ID | Name | Modules | RTM CSV row |
|---|---|---|---|
| FR-049 | Handler Auto-Discovery | Handlers | RTM FR-049 |
| FR-050 | Fine-Grained Exceptions | Strategy, Handlers/ErrorHandler | RTM FR-050 |
| FR-013 | Operation Types Registry | OperationTypes | RTM FR-013 |
| FR-012 | Vendor List Caching (runtime + metrics) | Reporting, cron | RTM FR-012 |
| IR-001 | Web Interface | Views/DataProviders | RTM IR-001 |
