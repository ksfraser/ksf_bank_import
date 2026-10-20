# FaBankImport Module Requirement Docs

One doc per subsystem cluster. Named `MEDIUM-<Cluster>.md` per ProjectDocuments naming convention ("no through ranges", subsystem-scoped requirement docs).

| Doc | Covers |
|---|---|
| MEDIUM-API-Partner-Contracts.md | API/ (6), Application/Partner/ (6), Contracts/ (6) |
| MEDIUM-Cli-Container-Commands-Middleware.md | Cli/ (3), Container/, Commands/ (6), Middleware/ (6) |
| MEDIUM-Handlers-Strategy-Reporting-Providers.md | Handlers/ (10), Strategy/ (5), Reporting/ (3), Views/DataProviders/ (4), OperationTypes/, cron/ |
| MEDIUM-Repository-Models-Database-Migrations.md | Repository/ (8), Repositories/ (2), Models/ (8), Database/ (3), Infrastructure/Database/Migrations |
| MEDIUM-Services-Scoring-Rules.md | Services/ (49), Services/Scoring/ (7), Services/Rules/ (5) |
| MEDIUM-StatementReconcile-Subsystem.md | StatementReconcile/** (30 files: Application, Domain, Infrastructure, Matching) |
| MEDIUM-Types-Entity-DomainValueObjects.md | Types/ (3), Entity/ (4), Domain/ValueObjects + ValueObject/ |
| MEDIUM-Views-Subsystem.md | Views/ (47 incl DataProviders) |
| MEDIUM-Exceptions-Infrastructure-Http-Results-Testing.md | Exceptions/ (3), Exception/ (5), Infrastructure/**, Http/ (2), Results/ (2), Testing/ (3) |
| MEDIUM-FaBankImport-Root-Legacy.md | FaBankImport root (36 files: legacy class.*, bootstrap, processors, controllers, procedural FA pages) |

Total in-scope files = 324 (of 329 src/Ksfraser/FaBankImport .php; excluded: 2 parse-broken BiLineItemController copies + Operation.php + class.bi_transactions.copilot.php + tests/bootstrap.php).

Pairing:
- **Requirement IDs** come from `docs/REQUIREMENTS_TRACEABILITY_MATRIX.csv` (rows BR-*, FR-*, ARCH-*, UC-*, NFR-*, DR-*, IR-*, SR-REQ-*).
- **Tags** live in source: file rollups (`@BABOK RTM: ...` within docblocks above classes), per-function (`@BABOK <refs> — method: <name>`), and class rollups (`@BABOK CLASS ROLLUP: <Class> — N methods; <Ref> (xCount)`).
