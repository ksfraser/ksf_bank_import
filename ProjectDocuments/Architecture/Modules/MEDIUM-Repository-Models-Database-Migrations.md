# Business Requirements Document — Persistence (Repository / Repositories / Models / Database / Migrations)

## Preamble

Coverage requirements for the persistence / data-boundary modules:

- `src/Ksfraser/FaBankImport/Repository/` — modern PSR-4 repositories (transaction upgrade path, `Result/TransactionResult`, `BiTransactions*` repositories)
- `src/Ksfraser/FaBankImport/Repositories/` — bridge repositories wrapping legacy `Model`
- `src/Ksfraser/FaBankImport/Models/` (+ `Model/`) — legacy FaBankImport DB models (BiTransactions, BiPartnersData, BiCounterpartyModel, BiStatementsModel …)
- `src/Ksfraser/FaBankImport/Database/` — low-level DB abstraction (queries, `DbResult`, `UserSession`)
- `src/Ksfraser/FaBankImport/Infrastructure/Database/Migrations` (+ `Migrations/`) — staged migration objects for StatementReconcile tables

## Subsystem Overview

| Module | Role | Requirement refs |
|---|---|---|
| Repository/TransactionRepository | D3/transaction status persistence facade | FR-008, ARCH-001 |
| Repository/TransactionSaver | Persistence of newly matched transactions | FR-008, ARCH-001 |
| Repository/BiLineItemRepository | Legacy row/repository BiLineItem | FR-008, ARCH-010 |
| Repository/BiTransactions…| Legacy bi_transactions wrappers | FR-008, BR-003 |
| Repositories/* | Factory-created repositories from Repository namespace | FR-008, ARCH-001 |
| Models/* | Legacy FaBankImport.D3 model classes | FR-008, BR-003, NFR-012 |
| Database/* | Query builder + transaction result + session | FR-008, DR-001, DR-003 |
| Infrastructure/Database/Migrations | ARCH-010 staged migrations | ARCH-010, FR-018 |

## Requirements traceability (BABOK)

All 23 files across `Repository*`, `Models*`, `Database*` and `Migrations` carry:
- File-level `@BABOK RTM: <refs>` rollup
- Per-method `@BABOK <refs> — method: <name>`
- Class-level `@BABOK CLASS ROLLUP` fan-outs

| Req ID | Name | Modules | RTM CSV row |
|---|---|---|---|
| FR-008 | Transaction Status Management | Repository, Repositories, Models, Database | RTM FR-008 |
| FR-018 | Transaction Audit Trail | Infrastructure/Database/Migrations (ARCH-010) | RTM FR-018 |
| DR-003 | Referential Integrity | Database (query builder) | RTM DR-003 |

## Test Coverage linkage

| Subsystem | Tests |
|---|---|
| Repository/* | `tests/unit/TransactionRepositoryTest`, `TransactionRepositoryRegressionTest`, `BiLineItemMatchingTest` |
| Repository/BiTransactions… | `tests/unit/BiTransactionsModelRegressionTest`, `BiTransactionsPaginationTest` |
| Database/* | `tests/unit/Database/*` (Query builder, feature tests) |
| Infrastructure/Database/Migrations | `tests/unit/StatementReconcile/Infrastructure/Database/Migrations/MigrationsTest` |
