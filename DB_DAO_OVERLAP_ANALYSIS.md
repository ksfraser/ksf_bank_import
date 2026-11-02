# Database / DAO Repo Overlap Analysis

> **Purpose**: Determine if parallel database/DAO repos (ksf_ModulesDAO, ksf_common_db, ksf_modules_common, etc.) should be merged, and if so, how to preserve backward compatibility.
>
> **Status**: Revised 2026-09-26 against measured source. **The 2026-09-20 version of this document reached the opposite conclusion on the central question. That conclusion has been reversed — see §0.**
>
> All class/method claims below were read from `main` of each repo, not from docs or from stale worktrees:
>
> | Repo | Ref measured | Tag |
> |------|--------------|-----|
> | ksf_ModulesDAO | `main` = `d2bcfaf` | `v0.5.2` |
> | ksf_common_db | `main` = `1486557` | `v1.0.1` |

---

## 0. What Changed, and Why the Prior Conclusion Was Reversed

The 2026-09-20 analysis recommended **merging ksf_ModulesDAO INTO ksf_common_db**, on four grounds. Three of them do not survive inspection:

| Prior claim | Measured reality | Effect |
|-------------|------------------|--------|
| "ksf_ModulesDAO has **no** query builder" | `main` ships `Sql/QueryBuilder.php` (**30 public methods**, incl. `insert`/`update`/`delete`/`count`/`exists`/`page`/`offset`/`whereNull`/`orWhere`), plus `BuiltQuery.php` and `LegacyArraySqlBuilder.php` | The claim was true only of the **abandoned `fix/placeholder-prefix-collision` branch**, which had deleted `Sql/`. The released `main` never lost it. This inverted the merge direction. |
| "ksf_common_db's FA adapter is **more complete**; ModulesDAO loses" | ksf_common_db's `FaDbAdapter::quote()` calls **`mysqli_real_escape_string()` directly** and **falls back to `addslashes()`** when `global $db` is unset. ModulesDAO's calls FA-native **`db_escape()`**, fails closed if it's missing, and has zero production `addslashes()`. | Backwards on the single most safety-critical class in either package. See §2A.4. |
| "ksf_common_db is 7.4; must be downgraded to 7.3" | `v1.0.0` was `>=7.4`; **`v1.0.1` and `main` are already `>=7.3`** | Already resolved. §5B is moot. |
| "MysqlDbAdapter is dead code, drop it" | `0` files matching `mysql` on `main` | Already dropped. §5E is moot. |

Additionally, the premise that the two "have very low overlap" is **inverted**: ksf_common_db is 5 classes / 966 LOC, and 4 of those 5 have a direct counterpart in ModulesDAO. Only `TableDefinition` is unique.

And the premise that their "capabilities would likely both be used in the same project" is **falsified by the dependency graph**: 18 projects require ksf_common_db, 19 require ksf_ModulesDAO, and **the intersection is empty — no project requires both.**

---

## 1. Inventory of DB/DAO Repos

| Repo | Type | PHP Min (measured) | Primary Concern | Consumers (measured) |
|------|------|--------------------|-----------------|----------------------|
| ksf_modules_common | Legacy module (procedural, in-FA) | 7.3 | Data dictionary + query builder + CRUD | ksf_ModulesDAO |
| ksf_ModulesDAO | Composer package (`ksfraser/ksf-modules-dao`) | **7.3** | Cross-platform DAO: DB adapters, KV/record stores, factories, **SQL query builder** | **19** |
| ksf_common_db | Composer package (`ksfraser/ksf-common-db`) | **7.3** (was 7.4 in v1.0.0) | Data dictionary + **SELECT-only** query builder + PDO/FA adapters | **18** |
| ksf_FA_Common | Composer package (`ksfraser/ksf-fa-common`) | 7.3 | Platform utilities, workflow hooks, traits | All FA modules |
| ksf_db_security | Composer package (`ksfraser/ksf-db-security`) | 7.3 | Column-level security via views / encrypted fields | Depends on ksf_common_db |
| ksf_DataIO | Composer package (`ksfraser/data-io`) | 8.0 | Import/export (CSV, Excel, JSON, XML) | FA modules |
| Database | Standalone composer package (`ksfraser/database`) | 7.3 | PDO connection manager + DummyPdo fallback | Tests |
| ksf_staging_dto | Composer package (`ksfraser/staging-dto`) | 7.3 | DTOs for staging integration | FA_ImportStaging |

---

## 2. Detailed Overlap Map

### 2A. ksf_ModulesDAO <-> ksf_common_db — SIGNIFICANT OVERLAP (still true, but the winner is reversed)

Size: ModulesDAO `main` = **26 files / 2704 LOC**. ksf_common_db = **5 files / 966 LOC**.

| Capability | ksf_ModulesDAO (`main`) | ksf_common_db | Verdict |
|------------|------------------------|--------------|---------|
| DB connection contract | `Db/DbAdapterInterface` — `getDialect`, `getTablePrefix`, `escape`, `query`, `execute`, `lastInsertId` (6) | `Contract/DbConnectionInterface` — `fetchAssoc`, `fetchAll`, `fetchScalar`, `executeUpdate`, `lastInsertId`, `quote`, `beginTransaction`, `commit`, `rollBack` (9) | Overlapping, **not** drop-in. ModulesDAO's is dialect/prefix-aware; common-db's is transaction- and fetch-capable. Needs real reconciliation. |
| FA runtime adapter | `Db/FrontAccountingDbAdapter` — `db_escape()`, fail-closed, type-correct literals, no `addslashes` | `Adapter/FaDbAdapter` — raw `mysqli_real_escape_string`, **`addslashes` fallback** | **ModulesDAO wins.** Hard-rule violation in common-db. |
| PDO adapter | *not on `main`* (only on the abandoned fix branch) | `Adapter/PdoDbAdapter` | **common-db wins** on `main`. Gap, not a conflict. |
| MySQL adapter | absent from `main` | absent | Neither. Already resolved. |
| Data dictionary | **none** | `Dictionary/TableDefinition` — columns, PK, indexes, CREATE/INSERT/UPDATE/DELETE SQL | **common-db wins — uniquely.** |
| Query builder | `Sql/QueryBuilder` — 30 methods, full DML, positional `?` + table prefixing | `Query/QueryBuilder` — 13 methods, **SELECT-only**, `:name` + `?` placeholders | ModulesDAO wins on operations; common-db wins on named placeholders. Neither strictly subsumes the other. |
| Key/value stores | **12** implementations (FA DB, PDO, INI/JSON/XML/CSV/YAML, WordPress, SuiteCRM, Callable) | none | ModulesDAO wins |
| Record stores | `CsvRecordStore`, `XmlRecordStore`, `AbstractFileRecordStore` | none | ModulesDAO wins |
| Factories | `DatabaseAdapterFactory`, `KeyValueStoreFactory` | none | ModulesDAO wins |
| Value codec | `Codec/ValueCodec` | none | ModulesDAO only |
| Legacy FA class | `ksf_ModulesDAO.php` (FA `class.MODEL`-style wrapper) | none | ModulesDAO only — **merge liability**, see §5C |
| Namespace | `Ksfraser\ModulesDAO\` | `ksfraser\CommonDb\` | Different — alias/shim needed either way |

**Core finding**: the two packages are **complementary, not competing, on the query/store side** — ModulesDAO has the broader operational surface, common-db has the one genuinely unique artifact (`TableDefinition`) plus a safer transaction contract. They **do** compete on exactly one axis: the FA connection adapter, where ModulesDAO's is correct and common-db's is not.

#### 2A.4 Live defects in ksf_common_db's `FaDbAdapter` (the reason not to make it canonical)

Found while verifying the prior doc's claims. All three are in the class the prior analysis wanted to make the canonical implementation.

1. **Violates the FA hard rule.** AGENTS.md: *"FA modules MUST use native `db_*` calls at runtime … never a PDO handle, never raw mysqli."* `FaDbAdapter::quote()` uses `mysqli_real_escape_string($db, …)` directly, bypassing `db_escape()` and therefore FA's per-connection charset handling.
2. **`addslashes()` fallback.** `isset($db) ? mysqli_real_escape_string(…) : addslashes(…)`. `addslashes()` is charset-unaware and unsafe under GBK/BIG5-family charsets. This is precisely the defect class just fixed and released in ModulesDAO `v0.5.2` / fa-classes `v1.7.0`.
3. **Silent placeholder-substitution failure.**
   ```php
   if (count($parts) !== count($params) + 1) {
       return $sql;               // returns SQL still containing literal '?'
   }
   ```
   A param-count mismatch yields un-substituted SQL instead of an error. Separately, named params use `str_replace(':' . $name, …)` with **no word boundary**, so binding `:id` also rewrites the `:id` inside `:identity`.

---

### 2B. ksf_ModulesDAO <-> ksf_modules_common — SIGNIFICANT OVERLAP (revised)

Same ancestry: ksf_modules_common is the legacy procedural framework, ksf_ModulesDAO the modernized OOP version.

| Capability | ksf_modules_common (legacy) | ksf_ModulesDAO (`main`) | Verdict |
|------------|----------------------------|--------------------------|---------|
| Data dictionary | `class.MODEL.php`: `fields_array` + `table_details` | **none** (`TableDefinition` lives in ksf_common_db) | Gap — see §3 |
| Clause builders | `buildSelect/From/Where/Join/GroupBy/Having/OrderBy/Limit` | `Sql/QueryBuilder` (30 methods) | ModulesDAO is the modern replacement |
| CRUD operations | `select_row`, `select_table`, `insert_table`, `update_table`, `delete_table`, `ReplaceQuery` | `QueryBuilder` DML + `Contracts/RecordStoreInterface` | ModulesDAO wins |
| FA adapter | `class.fa_MODEL.php` + `class.fa_db.php` (`TB_PREF`, `db_query`) | `FrontAccountingDbAdapter`, `FrontAccountingSysPrefsStore`, `FrontAccountingDbTableStore` | ModulesDAO wins — more granular |
| Observer/event pattern | `class.eventloop.php` — `ObserverRegister`, `ObserverNotify`, `**` wildcard | none | **ModulesDAO still missing** — eventloop is NOT wired into CRUD |
| Platform support | FA only (+ WP/SuiteCRM stubs) | FA, WordPress, SuiteCRM, PDO, file-backed | ModulesDAO wins |
| Table prefix | hardcoded `TB_PREF` | configurable `$tablePrefix` | ModulesDAO better |
| Transactions | none explicit | on ksf_common_db's contract, **not** on ModulesDAO's | ksf_common_db better |
| PHP floor | 7.3 | 7.3 | Tied |

**Core finding**: unchanged in direction, but the replacement target for the *dictionary* half is ksf_common_db's `TableDefinition`, not anything in ModulesDAO.

---

### 2C. ksf_common_db <-> ksf_modules_common — SIGNIFICANT OVERLAP

Inverse of 2B.

| Capability | ksf_modules_common | ksf_common_db | Verdict |
|------------|-------------------|--------------|---------|
| Table definition | `table_details` + `fields_array` | `TableDefinition` (fluent) | common-db superior |
| SQL generation | manual concatenation in `create_table`/`insert_table`/`update_table` | `TableDefinition::createSql()`, `insertSql()`, `updateSql()`, `deleteSql()` | common-db superior (parameterized) |
| SELECT building | `buildSelect()`, `buildFrom()`, `buildWhere()`… | `QueryBuilder` | common-db superior |
| FA integration | `class.fa_MODEL.php` sets `company_prefix = TB_PREF` | `FaDbAdapter` takes `$tablePrefix` | equivalent |
| Data validation | `validate()` in MODEL | none (application-level) | modules_common has extra |
| Table relationships | `class.tables_relationships.php` | none | modules_common has extra |
| CRUD | 6 procedural methods | via QueryBuilder + TableDefinition + adapter | equivalent |

---

### 2D. ksf_FA_Common <-> These — COMPLEMENTARY (no merge, wire together)

ksf_FA_Common's traits are lifecycle hooks, not DAO implementations.

| Component | Purpose | Relationship to DAO repos |
|-----------|---------|---------------------------|
| `WorkflowHooksTrait` | SuiteCRM-style hooks (before_save, after_save, …) | Should fire via DAO CRUD operations |
| `CrudOperationsTrait` | createRecord/deleteRecord with hook ordering | Should delegate DB work to the DAO layer |
| `SchemaMigrationRunner` | Applies `upgrade_*.sql` | Depends on FA `db_*` — **not** a generic DAO tool |
| `RbacGatewayTrait` | RBAC authorization for hooks classes | Uses ksf_common_db's `FaDbAdapter` — inherits §2A.4 |
| `CalendarRegistrationTrait` / `TabRegistrationTrait` / `FlashMessageTrait` | FA shell concerns | Unrelated to DAO |

**Core finding**: correctly positioned as the FA-platform layer above the DAO packages. No merge.

---

### 2E. ksf_db_security <-> These — CONSUMER (no merge)

| Component | Purpose | Relationship |
|-----------|---------|--------------|
| `VirtualView` | DAO wrapper enforcing column visibility in PHP | **Consumes `DbConnectionInterface`** |
| `ViewManager` | Creates MySQL views for column-level security | Consumes `DbConnectionInterface` |
| `ColumnPermissions` | Per-role column visibility config | Configuration class |
| `EncryptedFields` | Transparent encryption/decryption | Consumes `DbConnectionInterface` |

**Core finding**: a consumer, not a competitor. But it is **coupled to ksf_common_db's contract**, so any change to `DbConnectionInterface` ripples here — a real cost input to §5A.

---

### 2F / 2G / 2H. ksf_DataIO, Database, ksf_staging_dto — NO OVERLAP

| Repo | Components | Relationship |
|------|-----------|--------------|
| ksf_DataIO | `DataIOService`, `ImportService`/`ExportService`, `FAModuleImport` | File-format I/O, not DB abstraction. **Its PHP 8.0 floor remains a 7.3-compat hazard.** |
| Database | `DbManager`, `DummyPdo` | Connection management + in-memory test double — a different layer |
| ksf_staging_dto | Staging DTOs | Pure data containers |

---

## 3. Merge Recommendations Summary

### The duplication is real. The remedy is a dependency, not a repo merge.

The duplication that matters is **one class deep**: two independent implementations of "substitute placeholders with escaped literals against an FA connection." That is a genuine liability — it is the exact bug class (`addslashes`, charset-blind escaping, silent substitution failure) that had to be fixed **twice** in the last week, once per package.

It is **not** fixed by merging repos. It is fixed by having **one** implementation and making the other depend on it.

**Recommended: converge ModulesDAO's `Db/` layer onto `ksf_common_db`'s contract, but keep ModulesDAO's FA adapter as the *only* FA implementation and move it into common-db.**

Rationale, in priority order:

1. **Safety.** ModulesDAO's `FrontAccountingDbAdapter` is the correct FA implementation. It should be *moved into* common-db and the broken one deleted — not the reverse. Shipping common-db's `addslashes` fallback into 18 consumers would be a **regression**.
2. **Reach.** 19 vs 18 consumers; the modules-dao side additionally owns the only `RecordStore`/`KeyValueStore` abstraction anyone uses.
3. **Cost.** A `composer require` + a class move is a bounded change. A repo merge republishes **two divergent version lines** (`^1.0` across 18 manifests, `^0.3` across 19) and forces a namespace shim for both — §5F quantifies the sprawl.
4. **No forcing function.** No project requires both packages. There is no build that breaks today. This is a hygiene fix, and hygiene fixes do not justify a 37-manifest migration.

**`TableDefinition` should move into ModulesDAO** (or be duplicated deliberately) if ModulesDAO is to claim the data-dictionary role, since ModulesDAO has no equivalent and §2B needs one to complete the `ksf_modules_common` deprecation.

### DEPRICATE — ksf_modules_common MODEL/fa_MODEL (unchanged)

Keep `class.MODEL.php` / `class.fa_MODEL.php` as deprecated wrappers delegating to `TableDefinition` + `QueryBuilder` + the FA adapter. Wire `class.eventloop.php`'s Observer pattern into `WorkflowHooksTrait` pre/post hooks. `NOTIFY_INIT_TABLES` → `create_table` already exists; pre/post insert/update/delete do not.

### NO MERGE

| Repo | Reason |
|------|--------|
| ksf_FA_Common | Platform layer above the DAO packages — correct position |
| ksf_db_security | Consumer, not a competitor (but contract-coupled) |
| ksf_DataIO | File-format I/O — different domain |
| Database | Connection management — different layer |
| ksf_staging_dto | Data containers — different domain |

---

## 4. Backward Compatibility Plan

Applies if the direction in §3 is executed, or if a full merge is chosen later.

### Namespace shims

```php
namespace Ksfraser\ModulesDAO\Db;

class FrontAccountingDbAdapter extends \ksfraser\CommonDb\Adapter\FaDbAdapter {}
```

> Caveat: a shim only works if the **signatures match**. `DbAdapterInterface::query()` vs `DbConnectionInterface::fetchAll()` do not (§5A). Shimming the FA adapter is safe once both extend one base; shimming the *interfaces* requires §5A to be resolved first.

### Deprecation strategy

1. `@deprecated` annotations pointing at the new equivalent, with the replacement named.
2. Keep old constructor params and return types identical.
3. Old classes extend or delegate; never fork behaviour.
4. Remove only after a full major cycle.

### Consumer migration

```
ksf_ModulesDAO  -> require ksf_common_db; delete Db/; keep Stores/, Codec/, Factory/
ksf_modules_common -> deprecated wrappers delegate to TableDefinition + QueryBuilder + FaDbAdapter
ksf_db_security -> unchanged (already on DbConnectionInterface)
ksf_FA_Common   -> unchanged (RbacGatewayTrait already on common-db)
```

---

## 5. Design Issues to Resolve

### 5A. Interface divergence — the real blocker

| | ksf_ModulesDAO `DbAdapterInterface` | ksf_common_db `DbConnectionInterface` |
|---|---|---|
| Methods | 6 | 9 |
| Uniquely its own | `getDialect()`, `getTablePrefix()`, `escape()` | `fetchScalar()`, `beginTransaction()`, `commit()`, `rollBack()` |
| Shared intent | `query()`/`execute()`/`lastInsertId()` | `fetchAll()`/`executeUpdate()`/`lastInsertId()`/`quote()` |

**Proposed resolution**: adopt `DbConnectionInterface` as canonical. Fold `escape()` into `quote()`. Keep `getDialect()`/`getTablePrefix()` — they are **not** optional decoration; the `Stores/` layer depends on prefixing. Add transactions.

**Unresolved**: `query()` returns rows *and* is used by stores that expect FA-prefixed SQL; `fetchAll()` returns rows too, so the mapping is mechanical — but the 19 modules-dao consumers must be swept to confirm none depend on `query()`'s prefix side-effect. **Do this sweep before touching the interface.**

### 5B. PHP version floor — RESOLVED

`ksf_ModulesDAO` 7.3 | `ksf_common_db` **7.3** (was 7.4 in v1.0.0; fixed in v1.0.1) | `ksf_modules_common` 7.3 | `ksf_db_security` 7.3.

No action. Note `ksf_DataIO` remains at 8.0 and `ksfraser/traits` / `ksfraser/exceptions` declare `>=7.3` while shipping 7.4+ code (`protected array …`, `fn() =>`) — a separate live defect, see §6.

### 5C. The legacy FA class is merge liability

`ksf_ModulesDAO.php` is a procedural FA-era wrapper in the same package as the modern OOP layer. If ModulesDAO is absorbed, this file must **not** be carried into a package whose stated purpose is transport-agnosticism. Either leave it behind or split it into a thin deprecated shim.

### 5D. ValueCodec

Handles encoding/decoding for KV stores; unique to ModulesDAO. If it moves, it enables the transparent-encryption pattern `ksf_db_security` wants. Keep it adjacent to `Stores/`.

### 5E. MysqlDbAdapter — ALREADY GONE

`mysql_*` was removed in PHP 7.0; `0` such files remain on `main`. Nothing to do.

### 5F. Constraint sprawl is the real migration cost

Measured across modules-dao consumers:

| Constraint | Consumers |
|-----------|-----------|
| `^0.3` | 19 |
| `*` | 7 |
| `^0.5` | 4 |
| `0.5.2` | 2 |
| `dev-main` | 1 |
| `^0.1` | 1 |

Six coexisting constraints across 19 manifests, including a floating `dev-main` and seven unpinned `*`. **This sprawl — not package topology — is what will make any merge expensive**, and it should be normalised first. It is also an unpinned-dependency supply-chain exposure independent of any merge.

---

## 6. Findings That Are Not About Merging

Recorded here because they surfaced during verification and are live:

- **`ksfraser/traits` and `ksfraser/exceptions` misdeclare their floor.** Both declare `"php": ">=7.3"` but ship 7.4+ syntax (`protected array $validationErrors`, `fn($cb) => …`) — 38 files that fatal on 7.3. Direct deps of `ksf_FA_InventoryCount` only. Separate repos; needs its own fix.
- **`ksf_DataIO` requires PHP 8.0** against a 7.3 floor.
- **`fa-classes/legacy/`** — 34 FA core reference dumps that parse on no PHP version. `legacy/` is in the autoload classmap (minus `archive/`), so they are loadable-but-broken. Deliberately excluded from `tools/lint-php73.sh`.
- **The abandoned `fix/placeholder-prefix-collision` branch** (`80aa280`) has a divergent `src/` layout and **deleted `Sql/`**. It is not merged and should not be. Measuring against it produces wrong answers about what `main` contains — which is exactly how the prior version of this doc went wrong.

---

## 7. Summary: What to Do, What NOT to Do

### DO
- Fix ksf_common_db's `FaDbAdapter` (hard-rule violation, `addslashes` fallback, silent substitution failure) **before** it is adopted by anything.
- Make ModulesDAO depend on ksf_common_db for the contract; keep exactly one FA adapter implementation, and make it ModulesDAO's.
- Move `TableDefinition` into whichever package ends up owning the data-dictionary role.
- Normalise the six coexisting modules-dao constraints (§5F) before attempting any migration.
- Deprecate ksf_modules_common MODEL/fa_MODEL as wrappers.
- Keep ksf_FA_Common as the platform layer above the DAO packages.
- Wire `WorkflowHooksTrait`/`CrudOperationsTrait` into the DAO lifecycle.
- Keep the PHP 7.3 floor.

### DO NOT
- Do NOT make ksf_common_db's FA adapter canonical in its current state — that ships an `addslashes` fallback to 18 consumers.
- Do NOT treat a repo merge as the fix for the duplication. The duplication is one class deep; a dependency is the fix.
- Do NOT start the migration before the §5A interface sweep — 19 consumers, no test net.
- Do NOT merge ksf_DataIO, Database, or ksf_staging_dto.
- Do NOT delete ksf_modules_common or ksf_ModulesDAO code — deprecate first.
- Do NOT break the FA adapter rule: the FA adapter MUST use native `db_*` at runtime, never PDO, never raw mysqli, never `addslashes`.
- Do NOT add a third parallel DB-class abstraction.
- Do NOT measure `main` from the abandoned fix-branch worktree.

---

*Revised against measured source. No code changes made.*
