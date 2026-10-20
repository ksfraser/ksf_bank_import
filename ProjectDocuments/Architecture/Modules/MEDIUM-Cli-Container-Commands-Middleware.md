# Business Requirements Document — CLI / Container / Commands / Middleware

## Preamble

Defines coverage requirements for the execution-lifecycle modules:

- `src/Ksfraser/FaBankImport/Cli/` (and `Cli/Commands/`) — CLI entry-points (`frankenstein` CK binary, `qfx2st` conversion, Ollama statement processing)
- `src/Ksfraser/FaBankImport/Commands/` — command objects for the command bus (ARCH-004)
- `src/Ksfraser/FaBankImport/Container/` — PSR-11-compatible container (FR-015)
- `src/Ksfraser/FaBankImport/Middleware/` — middleware pipeline (`correlation-id`, auth, CORs, rate limit …) (FR-014/ARCH-004)

## Subsystem Overview

| Module | Role | Requirement refs (RTM) |
|---|---|---|
| Cli/bin/frankenstein | Console runner / admin integration point | FR-049, ARCH-004 |
| Cli/Commands/{ImportCommand, ProcessStatementsCommand, OscCommand} | Headless command support | FR-006, ARCH-007 |
| Commands/* | Command-pattern objects (AddCustomerCommand, AddVendorCommand, ToggleDebitCreditCommand, CommandDispatcher, UnsetTransactionCommand) | FR-014, ARCH-004 |
| Container | Container with classes service (closure-based definitions) | FR-015, ARCH-001 |
| Middleware/* | Lifecycle pipeline (correlation-id, rate-limit, auth, CORs) | FR-014, ARCH-004 |

## Requirements traceability (BABOK)

| Req ID | Name | Modules | Tags |
|---|---|---|---|
| FR-049 | Handler Auto-Discovery | Cli, Commands, Middleware | `@BABOK RTM: FR-049 / ARCH-004` |
| FR-015 | Dependency Injection | Container, Commands | `@BABOK RTM: FR-015 / ARCH-001` |
| FR-014 | Service-Oriented Design | Middlewares, Commands | `@BABOK RTM: FR-014 / ARCH-004` |
| FR-006 | FrontAccounting Integration | Cli/Commands | `@BABOK RTM: FR-006 / ARCH-007` |

## BABOK tagging status

- 3 Cli files, 6 Commands, 1 Container, 6 Middleware files tagged:
  - File-level `@BABOK RTM: ...` rollup, per-method `@BABOK ... — method: ...`, and `@BABOK CLASS ROLLUP` lines.

## Test Coverage linkage

| Subsystem | Tests | note |
|---|---|---|
| Commands/* | `tests/unit/Commands/{AddCustomerCommandTest, AddVendorCommandTest, CommandDispatcherTest, ToggleDebitCreditCommandTest, UnsetTransactionCommandTest}` | PASS |
| Container | `tests/unit/ContainerTest` | PASS |
| Cli/Commands | `tests/unit/Cli/...` (headless console, config, tests, training, script-app) | e2e (CLI) |
| Middleware | `tests/unit/HtmlOBTest`, `tests/Feature/*` (correlation id, CORs) | PASS |
