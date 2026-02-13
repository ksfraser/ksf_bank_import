# Prefs Module Enhancement Proposal

## Purpose

Enhance the Prefs module so it can act as a reusable configuration platform across modules, with mandatory historical change tracking and centralized key definitions suitable for auto-generated admin UI.

This proposal is intentionally repository-agnostic so it can be used directly in a standalone session for the `ksfraser/prefs` repository.

---

## Goals

1. Provide a clean, stable API for config read/write operations.
2. Support typed values (`string`, `int`, `float`, `bool`, `json`).
3. Support namespaced keys (for example: `bank_import.upload.max_size_mb`).
4. Support scope/tenant awareness (for example: per company).
5. Make history/audit tracking mandatory in the write pipeline.
6. Keep storage backend pluggable with first-class adapters for: `database`, `txt`, `csv`, `json`, `yml`, `ini`, `FA prefs`, `memory`.
7. Maintain backward compatibility where practical.
8. Centralize key definitions (type, description, UI input metadata, constraints) for admin screen generation.

---

## Non-Goals

1. Forcing a single physical table schema for every host application.
2. Embedding module-specific UI rendering logic in core persistence adapters.
3. Embedding UI concerns in core interfaces.

---

## Recommended Architecture

### 1) Core Interfaces

Create minimal contracts:

- `ConfigStoreInterface`
  - `get(string $key, $default = null)`
  - `set(string $key, $value, ?ConfigWriteContext $context = null): bool`
  - `has(string $key): bool`
  - `delete(string $key, ?ConfigWriteContext $context = null): bool`
  - `list(?ConfigQuery $query = null): array`

- `HistoryStoreInterface` (mandatory capability for write operations)
  - `record(ConfigChange $change): void`
  - `query(?HistoryQuery $query = null): array`

- `ConfigDefinitionRegistryInterface`
  - `get(string $key): ?ConfigKeyDefinition`
  - `all(?DefinitionQuery $query = null): array`
  - `register(ConfigKeyDefinition $definition): void`
  - `validate(string $key, $value, ConfigWriteContext $context): void`

- `TypedValueCodecInterface`
  - `encode($value, ?string $declaredType = null): EncodedValue`
  - `decode(string $rawValue, string $type)`

- `ConfigPolicyInterface` (optional extension point)
  - `validateBeforeWrite(string $key, $value, ConfigWriteContext $context): void`

This separates concerns cleanly and avoids monolithic service classes.

### 2) Domain Objects

Add lightweight immutable value objects:

- `ConfigEntry`
  - `key`, `value`, `type`, `scope`, `metadata`, `updatedAt`, `updatedBy`

- `ConfigChange`
  - `key`, `oldValue`, `newValue`, `type`, `scope`, `changedAt`, `changedBy`, `reason`, `source`

- `ConfigWriteContext`
  - `actor`, `reason`, `source`, `scope`, `correlationId`

- `ConfigQuery`
  - key prefix, exact key, scope filter, pagination

- `HistoryQuery`
  - key, key prefix, actor, date range, scope, pagination

- `ConfigKeyDefinition`
  - `key`, `type`, `defaultValue`, `description`, `constraints`, `isSystem`, `scopeRules`, `ui`
  - `ui` includes: `inputType`, `label`, `helpText`, `placeholder`, `optionsProvider`, `group`, `order`, `visibleIf`

### 3) Mandatory History + Capability-Based Composition

Use composition rather than inheritance:

- Base writable store implements `ConfigStoreInterface`.
- `HistoryEnforcedConfigStore` (or equivalent write orchestrator) always writes `ConfigChange` into `HistoryStoreInterface` on successful writes.
- `ValidatedConfigStore` decorator enforces policies.
- `CachedConfigStore` decorator provides in-memory cache with invalidation strategy.

History is always part of write semantics. The selected history adapter determines persistence behavior.

---

## Key Design Decisions

### Namespacing

Use dotted keys and reserve top-level namespace per module:

- `bank_import.upload.check_duplicates`
- `bank_import.pattern_matching.keyword_clustering_factor`
- `inventory.reorder.threshold_days`

### Scope Model

Support explicit scopes so same key can vary by tenant/company/environment:

- Global scope
- Company scope (`companyId`)
- Optional custom dimensions (`branchId`, `region`, etc.)

Implement deterministic fallback resolution:

1. Most specific scope
2. Company scope
3. Global scope
4. Caller default

### Typed Values

Persist a `type` for each entry and use codec for conversion.

Recommended type set:

- `string`
- `integer`
- `float`
- `boolean`
- `json`

Avoid implicit casting in persistence adapters.

### History/Audit Semantics (Mandatory)

History should capture:

- `oldValue` and `newValue`
- actor (`changedBy`)
- reason (`changeReason`)
- source (`ui`, `api`, `migration`, `system`)
- timestamp
- scope

History behavior rules:

1. Config writes MUST pass through history orchestration.
2. Default mode: fail write if history record fails (transactional integrity).
3. Alternative explicit mode: write succeeds but raises warning event on history failure.
4. A `NoneHistoryStoreAdapter` is allowed and intentionally records no history while preserving pipeline compatibility.

---

## Required Storage Adapters

### Config/Prefs storage adapters

1. `DatabaseConfigStoreAdapter`
2. `TextFileConfigStoreAdapter` (txt)
3. `CsvConfigStoreAdapter`
4. `JsonConfigStoreAdapter`
5. `YamlConfigStoreAdapter` (yml)
6. `IniConfigStoreAdapter`
7. `FaCompanyPrefsConfigStoreAdapter`
8. `InMemoryConfigStoreAdapter`

### History storage adapters

History adapters must mirror the same list and additionally include:

9. `NoneHistoryStoreAdapter` (`NONE`/null sink, intentionally does not persist history)

Recommended naming parity:

- `DatabaseHistoryStoreAdapter`
- `TextFileHistoryStoreAdapter`
- `CsvHistoryStoreAdapter`
- `JsonHistoryStoreAdapter`
- `YamlHistoryStoreAdapter`
- `IniHistoryStoreAdapter`
- `FaCompanyPrefsHistoryStoreAdapter`
- `InMemoryHistoryStoreAdapter`
- `NoneHistoryStoreAdapter`

### Adapter format guidance

- txt: one change/event per line with stable delimiter format
- csv: explicit columns for key/value/type/scope/actor/reason/timestamp
- json/yml: append-only event objects for history, key-value document for current prefs
- ini: sectioned keys for prefs; history can be section-per-event or rolling indexed sections

---

## Suggested Database Shape (Generic)

### Config table

Columns (example):

- `id` (PK)
- `config_key` (varchar 190)
- `config_type` (varchar 20)
- `config_value` (longtext)
- `scope_type` (varchar 30, nullable)
- `scope_id` (varchar 60, nullable)
- `metadata_json` (json/text nullable)
- `updated_at` (datetime/timestamp)
- `updated_by` (varchar 80 nullable)

Indexes:

- unique(`config_key`, `scope_type`, `scope_id`)
- index(`scope_type`, `scope_id`)
- index(`updated_at`)

### History table

Columns (example):

- `id` (PK)
- `config_key`
- `old_value`
- `new_value`
- `value_type`
- `scope_type`
- `scope_id`
- `changed_at`
- `changed_by`
- `change_reason`
- `change_source`
- `correlation_id`

Indexes:

- index(`config_key`, `changed_at`)
- index(`changed_by`, `changed_at`)
- index(`scope_type`, `scope_id`, `changed_at`)

---

## Backward Compatibility Strategy

### Phase 1: Introduce New Contracts

- Add interfaces and core DTOs.
- Keep existing APIs operational.
- Add adapter that wraps old implementation.

### Phase 2: Add Decorators

- Introduce `AuditedConfigStore`, `ValidatedConfigStore`, `CachedConfigStore`.
- Keep decorators opt-in and non-breaking.

### Phase 3: Add Migration Utilities

- Tooling for key migration/normalization.
- Optionally mirror writes to old and new stores during rollout.

### Phase 4: Deprecate Legacy APIs

- Mark old direct helpers deprecated with timeline.
- Provide one-command codemod guidance where feasible.

---

## Validation and Guardrails

Add policy hooks for:

- Immutable/system keys
- Numeric bounds (`min`, `max`)
- Enum constraints
- Schema validation for JSON values

Provide a mandatory `ConfigDefinitionRegistry`:

- key
- type
- default
- description
- constraints (min/max/enum/pattern/custom validator)
- isSystem
- visibility tags
- UI metadata:
  - input type (`text`, `int`, `float`, `bool`, `select`, `multiselect`, `textarea`, etc.)
  - label/help/placeholder
  - options source (`static`, callback/provider, FA-derived list)
  - grouping/tab/section and display order
  - conditional visibility rules

This is the central equivalent of the `prefs` array style used in the generic FA interface inheritance tree, but framework-level and reusable.

This enables safer admin UIs and machine-readable docs.

---

## Auto-Generated Admin UI Contract

The registry should be sufficient to generate admin forms without hand-coding fields.

Suggested UI DTO shape:

- `UiFieldDefinition`
  - `key`, `label`, `description`, `inputType`, `dataType`, `default`, `required`, `options`, `constraints`, `group`, `order`, `visibleIf`

The admin UI layer consumes registry entries and renders by `inputType`.
For FrontAccounting integration, `optionsProvider` may reference existing FA lookup sources (accounts, dimensions, users, etc.).

---

## Observability and Operations

Recommended additions:

- Structured logging for writes and policy rejections.
- Metrics:
  - read count
  - write count
  - cache hit ratio
  - history write failures
- Optional event publishing (`ConfigChanged` event).

---

## Test Plan

1. Unit tests
   - typed encode/decode behavior
   - scope fallback resolution
   - decorator behavior and ordering
   - policy enforcement and exceptions

2. Integration tests
   - DB adapter CRUD
   - history recording integrity
   - migration compatibility scenarios

3. Contract tests
   - run the same suite against every adapter implementation

---

## Recommended Initial Milestone (MVP)

Deliver in first pass:

1. `ConfigStoreInterface`
2. `HistoryStoreInterface` + enforced write orchestration
3. `ConfigDefinitionRegistryInterface` + `ConfigKeyDefinition`
4. `TypedValueCodecInterface` + default codec
5. `DatabaseConfigStoreAdapter` and `DatabaseHistoryStoreAdapter`
6. `Json/Yaml/Ini/Csv/Text` adapters for config and history
7. `FaCompanyPrefs` and `InMemory` adapters for config and history
8. `NoneHistoryStoreAdapter`
9. minimal migration helper
10. baseline tests for all above

This gives immediate reusable value without over-expanding scope.

---

## Open Questions for Prefs Repo Session

1. Should history be strict (transaction must include history) or best-effort?
2. Should strict mode be globally enforced, with `NONE` only by explicit per-module config?
3. Should scope be strongly typed (`companyId:int`) or generic (`scope_type`, `scope_id` strings)?
4. Should key definitions live in code, database, or both?
5. Should a single shared history table be used across all modules?
6. What is the preferred deprecation window for legacy APIs?

---

## Suggested Work Breakdown (Issue-Friendly)

1. Add contracts and DTOs.
2. Implement `ConfigDefinitionRegistry` and UI metadata schema.
3. Implement default typed codec.
4. Implement DB config adapter.
5. Implement DB history adapter.
6. Implement file-based adapters (txt/csv/json/yml/ini) for config + history.
7. Implement `NoneHistoryStoreAdapter`.
8. Implement enforced history write orchestration.
9. Implement validation policy hook.
10. Add migration helper CLI/service.
11. Add contract test suite for adapters.
12. Add docs and upgrade guide.

---

## Suggested Commit Sequence

1. `feat(core): add config/history contracts and domain value objects`
2. `feat(definitions): add centralized key definition registry and UI metadata`
3. `feat(codec): add typed value codec and tests`
4. `feat(db): add database config/history adapters with scoped keys`
5. `feat(files): add txt/csv/json/yml/ini config/history adapters`
6. `feat(history): add mandatory history orchestration and NONE adapter`
7. `feat(policy): add validation policy extension point`
8. `docs: add migration guide and upgrade notes`

---

## Summary

The Prefs module should evolve into a small, composable configuration platform with mandatory history semantics:

- stable core config API
- typed and scoped values
- mandatory history pipeline (with selectable history adapter, including `NONE`)
- pluggable config/history adapters (`database`, `txt`, `csv`, `json`, `yml`, `ini`)
- centralized key + UI metadata definitions for admin screen autogeneration
- migration-friendly rollout

This keeps module-specific concerns out of core while enabling robust, consistent configuration and UI generation across modules.

---

## Recommended Defaults (to Unblock Implementation)

To accelerate MVP delivery, adopt the following defaults unless the target host app requires an override:

1. **History mode default: strict**
  - Writes fail if history persistence fails.
  - Add explicit `best_effort` mode only via configuration.

2. **`NONE` history adapter usage: explicit only**
  - Disallow silent fallback to `NoneHistoryStoreAdapter`.
  - Require opt-in per module/environment.

3. **Scope representation: generic storage, typed API helpers**
  - Persist as `scope_type` + `scope_id` strings.
  - Provide typed helper factories (for example, `Scope::company(int $id)`).

4. **Key definitions source of truth: code-first with optional DB overlays**
  - Keep core definitions in version-controlled code.
  - Allow DB overrides for UI-only metadata where needed.

5. **History table strategy: single shared table by default**
  - Include `module` or top-level namespace in key conventions.
  - Keep adapter-level option for per-module physical partitioning.

6. **Deprecation window: 2 minor releases (or 6 months)**
  - Mark legacy APIs deprecated immediately.
  - Provide migration tooling before hard removal.

---

## Immediate Next Actions (Execution-Ready)

1. Create core contracts and immutable DTOs.
2. Implement default typed codec (`string`, `integer`, `float`, `boolean`, `json`).
3. Implement DB config/history adapters with strict transactional mode.
4. Add `HistoryEnforcedConfigStore` orchestration.
5. Add `ConfigDefinitionRegistry` with validation and UI metadata schema.
6. Add adapter contract tests and run against DB + memory first.
7. Add file-based adapters (`txt`, `csv`, `json`, `yml`, `ini`) and run the same contract suite.
8. Add migration helper and legacy wrapper adapter.
9. Publish upgrade guide and deprecation timeline.

This sequence preserves backward compatibility while delivering mandatory history guarantees early.