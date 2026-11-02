# Business Requirements Document — Exceptions / Infrastructure (cross-cutting)

## Preamble

Coverage for error type hierarchy (`Exception/`, `Exceptions/`) and cross-cutting infrastructure (`Infrastructure/` subdirs: Config, Database, Error, Factory, Logger) plus `Http/RequestHandler` & `ResponseHandler` and `Results/*` value objects. These implement FR-017 / FR-050 / ARCH-003 (fine-grained error handling), FR-015 (DI wiring via factory/infrastructure classes), FR-018 / ARCH-008 (audit via logger), FR-006 (FA integration via Infrastructure glue), and ARCH-007 (performance monitoring via alert- and metrics-aware error paths).

## Exception subsystem (FR-050 / FR-017 / ARCH-003)

| Class | Role |
|---|---|
| Exception/PartnerException | Base partner-domain exception |
| Exception/PartnerNotFoundException | Missing partner at match/training time |
| Exception/PartnerPersistenceException | DB error while writing partner data |
| Exception/PartnerValidationException | Input validation failure |
| Exception/TrainingException | Keyword training / training pipeline error |
| Exceptions/HandlerDiscoveryException | Handler auto-discovery pipeline error (ARCH-004 / FR-049) |
| Exceptions/TransactionValidationException | Transaction-level validation error (BR-002 / FR-007) |
| Exceptions/UnauthorizedException | Authorization failure for API / agent (NFR-009 / FR-050) |

## Infrastructure (ARCH-001 / FR-015)

| Module | Role |
|---|---|
| Infrastructure/Config/{LoggingConfig(?)} | Logging and metrics config merge |
| Infrastructure/Database/… | PDO-style adapters, transaction persistence bridges |
| Infrastructure/Error/{AlertHandler(?)} | Captures errors and reports (FR-016) |
| Infrastructure/Factory/* | Factory wiring of FA context into services (FR-015) |
| Infrastructure/Logger/{LogService(?)} | Audit trail persistence (FR-018, ARCH-008) |

## Http + Results

| Module | Role |
|---|---|
| Http/RequestHandler, ResponseHandler | HTTP lifecycle pre/post handling (IR-001, FR-016) |
| Results/{TransactionResult, PaginatedTransactionResult} | Aggregate result types for transactions (FR-008, ARCH-001) |

## Testing (helper)

| Module | Role |
|---|---|
| Testing/Seeder, SeederFactory, Seeder/* | Fixture seeding (NFR-016) |

## BABOK tagging status

All files in these six dirs carry file-level rollups, per-method tags, and class rollups per the 2026-10-20 audit (0 missing; excluding parse-broken & test bootstrap).
