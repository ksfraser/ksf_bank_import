# Code Review — ksf_bank_import (2026-10-21/22)

Review of the three targets the module is measured against:

1. **100% of the requirements are covered by code** (and every code unit traces to a requirement).
2. **100% test coverage** of the production tree.
3. **100% of the functions carry a `@BABOK` tag that cross-references the RTM.**

Scope: the PSR-4 tree under `src/` (the migration target). The legacy root tree
(`class.*.php`, `import_statements.php`, `process_statements.php`, `views/`) is the
migration **source**; its existing tests stay, but it is not part of the 100% targets.

---

## 1. Scorecard

| Axis | Before | After | Enforced by |
|---|---|---|---|
| @BABOK tag on every production unit | 0 / 1777 (0%) | **1802 / 1802 (100%)** | `tests/unit/Quality/TraceabilityAuditTest.php` |
| FR referenced by production code | 0 / 51 (0%) | **51 / 51 (100%)** | same test + `tools/ci_gate.php` |
| FR traced in its feature RTM | 49 / 51 (96%) | **51 / 51 (100%)** | same test |
| Line coverage | 31.0% (2711/8743) | **97.9% (7220/7373)** | `tools/ci_gate.php` (clover) |
| Method coverage | 40.5% (598/1477) | **95.4% (1297/1359)** | same |
| Class coverage | 19.6% (63/321) | **85.4% (245/287)** | same |
| Tests / assertions | 1447 / 3674 | **2632 / 6531** | `vendor/bin/phpunit` |
| Skipped tests | 1 | **0** | house rule: skipped = failed |
| Classes that can be autoloaded | 259 / 326 | **324 / 327** | `tools/class_load_check.php` |

The three classes that cannot be declared are bound to a live FrontAccounting
installation (they extend `GenericFaInterfaceModel` / `ksf_modules_common`
classes) — see §3.4.

---

## 2. How the findings were verified — and where the first review was wrong

The first pass of this review was written from test-agent reports. Several of
those reports were **wrong**, and two of the "blocking" findings did not survive
verification. This section records the reasoning, so the next reviewer does not
have to repeat the work.

### 2.1 `db_escape()` — the first review was wrong, the real defect is the opposite

**Claim (wrong):** "`db_escape()` is used without quotes; FA's `db_escape()` is
`mysqli_real_escape_string` — it escapes but does not quote, so the generated SQL
is `VALUES (upload.batch_size, 500, NOW(), admin)`."

**How it was checked:** the real FrontAccounting source, not the mock —
`~/Documents/ksf_Infrastructure/FA/2.4.3/includes/db/connect_db_mysqli.inc:135`
and the same file inside the running container (`podman exec ksf-fa`):

```php
function db_escape($value = "", $nullify = false)
{
    $value = @html_entity_decode($value, ENT_QUOTES, ...);
    $value = html_specials_encode($value);
    if ((!isset($value)) || (is_null($value)) || ($value === "")) {
        $value = ($nullify) ? ("NULL") : ("''");
    } else {
        if (is_string($value)) {
            $value = "'" . mysqli_real_escape_string($db, $value) . "'";   // <- quoted
        } else if (!is_numeric($value)) { ... }
    }
    return $value;
}
```

**Conclusion:** FA's `db_escape()` returns a **ready-to-use quoted literal**.
The repositories' `db_escape()` calls were correct. The claim was based on
`ksfraser/famock`, whose stub is `addslashes()` — the mock does not reproduce
FA's contract, and the agent trusted the mock.

**The defect that the verification did find** is the opposite one, in
`repositories/TransactionRepository.php`:

```php
$escapedParam = is_numeric($param) ? $param : "'" . db_escape($param) . "'";
```

`db_escape()` already quotes, so every string became `''value''` — invalid SQL
in production. The same block used `preg_replace()` with the value as the
*replacement* string, so `$0` / `\1` inside a value were expanded as
backreferences, and `findById()` never substituted its placeholder at all.

**Fixed** in `fix(db): bind SQL parameters instead of passing them to db_query`
— the new `Repository/ParameterBinder` resolves `?` and `:name` placeholders to
escaped literals and delegates the quoting to the `FaDbAdapter` of
`ksfraser/ksf-common-db` (the house pattern), while `db_query()` still executes
the statement so FA's error semantics are unchanged.

**Second-order finding:** the module's own test doubles inherited the mock's
inaccuracy, so the tests asserted a SQL shape production never produces. The
doubles now mirror FA (strings quoted and escaped, numbers unquoted). The
`ksfraser/famock` package itself still needs the same correction.

### 2.2 The "stray `/*`" in `Transaction.php` — not stray, deliberate

**Claim (wrong):** "A stray `/*` opens at the `/*****` comment line and only
closes ~40 lines later, so the whole `fa_gl` matching block is commented out."

**How it was checked:** the raw text of
`src/Ksfraser/FaBankImport/Transaction.php:192-258`. The block comment opens at
line 203 (`/***…`) and closes at line 246 (`…***/`). It is one **deliberate**
block comment: the developer commented out the pre-migration `fa_gl` code when
moving to `FaGLWrapper` (lines 247-255, which is live and assigns
`$this->matching_trans`).

**Conclusion:** nothing was broken by it. The comment style is ugly, but the
active code path is `FaGLWrapper`. The report has been corrected.

**What the verification did find** in the same area: `FaGLWrapper`'s helper
path was `__DIR__ . '../../../../../ksf_modules_common/class.fa_gl.php'` —
**no directory separator**, so it never resolved and the wrapper always threw.
Fixed to `__DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php'`, which
resolves both in the dev tree (`~/Documents/ksf_modules_common/`) and in
`<fa_root>/modules/ksf_modules_common/`.

### 2.3 `isPost()` / `getQuery()` — one of the two claims was right

**Claim (partly wrong):** "`run()` calls `$request->isPost()`; `Http\RequestHandler`
has no `isPost()`" and "`getQuery()` does not exist".

**How it was checked:** the two `RequestHandler` classes were read side by side.

* `Ksfraser\FaBankImport\Http\RequestHandler` **has** `isPost()` (line 139) —
  the claim was wrong for the module shell.
* `Ksfraser\Application\Http\RequestHandler` (the generic shell) did **not**
  have it — so `Application::run()` fataled on every request.
* `getQuery(string $key, $default = null)` was missing on **both** shells, and
  `AdminController` calls it in six places.

**Fixed:** `isPost()` added to the generic shell, `getQuery()` added to both,
with tests (`tests/unit/Http/RequestHandlerQueryTest.php`).

### 2.4 The `require_once` of a non-existent file — the path was wrong, not the intent

**Claim (right, but the reasoning was missing):** "the require_once is fatal —
the whole bank-transfer path dies."

**How it was checked:** the resolved path of every helper include in the tree,
not just the ones the tests exercised:

| File | Path used | Resolves to | Correct |
|---|---|---|---|
| `FaGLWrapper` | `__DIR__ . '../../../../../ksf_modules_common/…'` | nothing (missing separator) | `__DIR__ . '/../../../../ksf_modules_common/…'` |
| `BankTransferTransactionHandler` | `dirname(__DIR__, 2) . '/../ksf_modules_common/…'` | `src/Ksfraser/ksf_modules_common/…` | `dirname(__DIR__, 4) . '/../ksf_modules_common/…'` |

`ksf_modules_common` sits **next to** the module (`<fa_root>/modules/`), not
inside it. Both paths are now correct in the dev tree *and* in the deployed
layout — verified with `realpath()` against
`~/Documents/ksf_Infrastructure/fa_modules/ksf_modules_common/`.

### 2.5 The `?` placeholders — the module never used the FaDbAdapter

**Claim (right, and the question behind it was the right one):** "FA has no
prepared statements — the second argument is the error message."

**How it was checked:** `db_query($sql, $error)` in
`includes/db/connect_db_mysqli.inc`; the module called
`db_query($sql, [$a, $b, …])`, so the array landed in the error-message
parameter and the placeholders reached MySQL literally.

**Why nothing converted them:** the module never depended on
`ksfraser/ksf-common-db` — it called FA's globals directly, so no binding layer
existed. That is now the case: `ksfraser/ksf-common-db` is a dependency and the
new `ParameterBinder` delegates the quoting to its `FaDbAdapter`
(`DbConnectionInterface::quote()`), which uses `mysqli_real_escape_string`
without FA's HTML-encoding quirk.

### 2.6 The `MA` / `ZZ` partner types — verified against the codes, not the names

`TransactionFactoryImp` maps `MA` → `MatchedTransaction` and `ZZ` →
`ManualTransaction`, while `PartnerTypeRegistry` maps `MA` →
`ManualSettlementPartnerType` and `ZZ` → `MatchedPartnerType`. The two
disagree, and the requirement text (`FR-PROC-008` "Manual settlement (MA)",
`FR-PROC-009` "Matched confirmation (ZZ)") agrees with the registry. The
factory is the wrong one — still to be fixed (see §4).

---

## 3. What was changed

### 3.1 Dead code removed (36 files + 5 duplicate QFX files)

Scratch backups (`*.copilot.php`, `*-old.php`, the stray `,bic2` editor file),
files whose class the autoloader resolved elsewhere, copies of the legacy root
classes inside `src/`, developer scratch scripts, orphan config files, and the
four byte-identical copies of the QFX parser file (one canonical definition
remains, the other four are one-line compatibility shims).

### 3.2 Traceability machinery (`tools/`)

| Tool | Purpose |
|---|---|
| `tools/lib/code_units.php` | shared scanner: what counts as a production unit |
| `tools/lib/rtm_index.php` | parses the 7 feature RTMs into `FR => [BR, UAT]` |
| `tools/traceability-map.php` | the class/function → FR map (337 entries) |
| `tools/inject_babok_tags.php` | writes the tags (idempotent, refuses to write a file that no longer parses) |
| `tools/traceability_audit.php` | reports the three traceability percentages |
| `tools/rtm_code_matrix.php` | regenerates the "Code Cross-Reference" table in each RTM |
| `tools/scope_inventory.php`, `tools/reachability.php`, `tools/production_scope.php` | scope / dead-code analysis |
| `tools/coverage_gaps.php` | prioritised uncovered-line report from clover |
| `tools/class_load_check.php` | loads every class in its own process to find the ones PHP refuses to declare |
| `tools/signature_check.php` | compares overridden method signatures with the parent declaration |
| `tools/ci_gate.php` + `tools/coverage-gate.json` | the CI gate (traceability + coverage) |

All 7 feature RTMs carry a **Code Cross-Reference** table mapping every FR to
the classes that implement it, generated from the `@BABOK` tags.

### 3.3 The autoloader (the root cause of most fatals)

* Every directory in `src/` now matches its namespace segment
  (`Application/config` → `Application/Config`, `FaBankImport/views` →
  `FaBankImport/Views`, …).
* Files that declared several classes were split into one class per file
  (`FactoryTransactions.php` → 6 files, `views/DisplaySettledTransactions.php` →
  13 files, `views/MatchingGLS.php` → 2 files, `Controllers/BankImportController.php`
  → 2 files).
* Duplicate copies were consolidated (`Model/BiLineItemModel.php`,
  `View/BiLineItemView.php`).
* `composer.json` now needs one generic prefix (`"Ksfraser\\": "src/Ksfraser/"`)
  plus a classmap for the three PSR-0 classes that have no namespace.
* The 21 classes that PHP refused to declare (signatures incompatible with
  their interface, or a parent class that does not exist) were fixed:
  `HtmlFormatting::addAttribute()`/`setAttributeList()`, the `getHtml()`/
  `toHtml()` signatures of the view classes, `HtmlDd`'s parent import, and the
  missing `use` statements for the `Ksfraser\HTML\*` type hints.

### 3.4 The three classes that still cannot be declared

| Class | Why |
|---|---|
| `Ksfraser\FaBankImport\Model\BiLineItemModel` | extends `GenericFaInterfaceModel` (FrontAccounting) |
| `Ksfraser\FaBankImport\Models\BiLineItemModel` | extends `Ksfraser\common\GenericFaInterface` (ksf_modules_common) |
| `ksf_modules_table_filter_by_date` | requires FrontAccounting's `includes/types.inc` |

They are bound to a live FrontAccounting installation, which is expected for
FA adapter classes; the test suite provides stand-ins for them.

### 3.5 Tests

1,175 new tests across `tests/unit/{Application,Services,Processing,FaBankImport,
LineItems,Screens,GapClosure,Http,Repositories,Repository,Quality}/`. The last
skipped test was replaced by a real assertion.

### 3.6 CI

`.github/workflows/php.yml` previously **did not run the test suite** (the step
was commented out). It now runs the suite and `tools/ci_gate.php`, which fails
the build when a production unit has no `@BABOK` reference, when a requirement
is not traced, when coverage drops below the gate, or when a file with uncovered
lines is not on the allow-list of the files whose remaining lines are proven
unreachable.

---

## 4. Defects still open (not fixed — they need a decision)

### Blocking

| # | Where | Defect |
|---|---|---|
| 1 | `TransactionFactoryImp` | Maps `MA` → `MatchedTransaction` and `ZZ` → `ManualTransaction`; `PartnerTypeRegistry` and the requirements say the opposite. The factory is wrong. |
| 2 | `models/`, `views/`, root tree | Two parallel implementations of every feature (the legacy FA tree and the PSR-4 tree). The RTM still points at the legacy files in its "Implementation Anchors". |
| 3 | `src/Ksfraser/FaBankImport/Models/BiLineItemModel.php` | Extends `Ksfraser\common\GenericFaInterface` — a class that does not exist in this repository. The class can never be loaded in production. |
| 4 | `header_table.php:59-79` | `_( "$this->cell1['label']:" )` — PHP does not support array access in simple interpolation, so every label renders as `Array['label']:`. |
| 5 | `import_statements.php:133` | `print_r( $e, tru )` — `tru` is not a constant. On PHP 8 this throws an `Error`, which the surrounding `catch (Exception $e)` does **not** catch: the screen dies instead of reporting the failure. |
| 6 | `ConfigService`, `Results/TransactionResult` | Use PHP 7.4 typed properties while `composer.json` declares `"php": ">=7.3"` and pins `config.platform.php = 7.3` — the package would fatal on a 7.3 runtime. |
| 7 | `Transaction::matchedVendor()` | Reads `$this->otherBankAccountt` (typo, never assigned) and calls `$this->matchedSupplierId()`, which the class does not declare — an `Error` that `catch (Exception)` cannot catch. |
| 8 | `handlers/ManualSettlementHandler.php` vs `MatchedTransactionHandler.php` | The two handlers disagree on `get_trans_counterparty()`: one treats the result as a flat row, the other as a list of rows. The manual handler never finds a person. |
| 9 | `middleware/AuthMiddleware.php` (module tree) | Calls `$next($request)` **after** `redirect()`. The module's `ResponseHandler::redirect()` does not `exit()` (the generic shell's does), so an unauthenticated request is redirected *and still runs the controller action*. |
| 10 | `AbstractRepository` / `QueryBuilder` | Not stateless: one `QueryBuilder` is created in the constructor and mutated by every method, so WHERE/ORDER/LIMIT clauses **accumulate across calls**. |
| 11 | `PerformanceMonitoringMiddleware` | `before()` and `after()` each re-evaluate `shouldSample()` with a fresh `mt_rand()`; with a rate < 1 the two disagree and the sample is silently dropped. |
| 12 | `Transaction.php` (root tree) | `Transaction::__construct` overwrites `$_POST['partnerType'][$id]` with `null` (it assigns `$this->partnerType`, which is never set). |
| 13 | `asgrim/ofxparser` | Single-line OFX/QFX documents cannot be parsed: the parser inserts a newline before every `<`, splitting `<CODE>0</CODE>`. `tests/sample.qfx` and `includes/test.qfx` both fail — which is why `tests/QfxParserTest.php` is in no phpunit suite and never runs. |
| 14 | `includes/pdata.inc` (legacy) | `count( $arr )` on the result of `get_partner_data()`, which is `false` when there is no row → `TypeError` on PHP 8. |

### Requirements-process gaps (recorded, still open)

* `FR-PROC-013` (Add Customer / Add Vendor) and `FR-VAL-004` (suggested matches)
  had **no row in their RTM and no UAT case**. Both RTMs now carry an explicit
  `**GAP — no UAT case exists**` row; the UAT plans still need `UAT-PROC-013`
  and `UAT-VAL-006`.
* `FR-002`, `FR-003`, `FR-007`, `FR-008` (the account-resolution screen) are
  now implemented in the PSR-4 tree as well:
  `Service/AccountResolutionService` (the unresolved identifiers and the
  mapping), `Contracts/BankAccountDirectoryInterface` (the read-only list of
  the existing bank accounts — the step cannot create one, FR-007) and
  `Service/ImportFlow` (the order of the steps: the resolution runs after the
  duplicate review, FR-008). The CI gate's list of requirements that are still
  only in the legacy tree is empty.

---

## 5. The remaining 153 uncovered lines — why they are unreachable

| Group | Lines | Proof |
|---|---|---|
| `HtmlStyleList`, `HtmlInternalCSSList`, `HtmlFormatting` (+9 subclasses) | 42 | PHP refuses to declare the class: *"Declaration of …::getHtml() must be compatible with …HtmlElementInterface::getHtml(): string"*. |
| `FaGLWrapper.php` | 15 | The wrapper finds `ksf_modules_common/class.fa_gl.php`, but that file pulls in FrontAccounting's `includes/types.inc`, which is not in this repository. |
| `views/DisplaySettledTransactions.php` | 17 | `new SettledPaymentTable` / `SettledDepositTable` / `SettledDefault()` are called with no argument although all three constructors require one → `ArgumentCountError` on entry. |
| `View/BiLineItemView.php` | 15 | The view calls the FrontAccounting UI helpers (`label_row()`, `array_selector()`), which only exist inside a FrontAccounting installation. |
| Dead branches (`AbstractTransactionHandler:93-95`, `TransactionProcessor:97/107/150`, `FileStorageService:77/241`, `DetectedAccountAssociationKey:46`, `DuplicateDetector:111`, `PartnerTypeRegistry:122`, …) | 20 | Each is guarded by a condition that is provably always false (a return type that guarantees the `instanceof`, a `glob()` on an existing directory, an `is_uploaded_file()` that is never true in CLI, arithmetic that is never negative, …). |
| The split view classes (`Views/*.php`) | ~39 | Their constructors require arguments the legacy view passed to them; the rows are built directly in the scenarios. |

`tools/coverage-gate.json` pins these files on an allow-list, so **any new gap
fails the build** while the gate is ratcheted upwards.

> **Environment note (2026-10-22):** the root filesystem reached 100% during
> this review. The cause is not the module: `/home/kevin/backups/state/` holds
> ten daily database snapshots of ~5 GB each (48 GB, the newest from 2026-10-11),
> and the backup job has not written since. The journal was vacuumed to free
> 2.1 GB; the snapshots need a retention decision.

---

## 6. Recommended order of work

1. **Fix the MA / ZZ mapping** (#1) — a wrong partner type creates the wrong
   FA transaction.
2. **Finish the autoloader work**: the two line-item models that extend classes
   this repository does not have (#3), and the "Implementation Anchors" of the
   RTMs, which still point at the legacy files.
3. **Fix the fatal paths** (#4, #5, #7) — the interpolation typo, the `tru`
   typo, and the typo in `matchedVendor()`.
4. **Fix the stateful repositories** (#10) — the accumulating WHERE clauses
   produce wrong results as soon as a repository is used twice.
5. **Migrate the account-resolution screen** (FR-002/003/007/008) — the
   service side is done; the screen still has to use it — and close the two
   UAT gaps (UAT-PROC-013, UAT-VAL-006).
6. **Resolve the PHP floor** (#6) — either drop the typed properties or raise
   `composer.json` to `>=7.4` (the container runtime is 7.4).
