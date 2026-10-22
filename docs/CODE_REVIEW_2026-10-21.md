# Code Review — ksf_bank_import (2026-10-21)

Review of the three targets the module is measured against:

1. **100% of the requirements are covered by code** (and every code unit traces to a requirement).
2. **100% test coverage** of the production tree.
3. **100% of the functions carry a `@BABOK` tag that cross-references the RTM.**

Scope: the PSR-4 tree under `src/` (the migration target). The legacy root tree
(`class.*.php`, `import_statements.php`, `process_statements.php`, `views/`) is the
migration **source**; its existing tests stay, but it is not part of the 100% targets.

---

## 1. Scorecard

| Axis | Before | After | How it is enforced |
|---|---|---|---|
| @BABOK tag on every production unit | 0 / 1777 (0%) | **1803 / 1803 (100%)** | `tests/unit/Quality/TraceabilityAuditTest.php` |
| FR referenced by code | 0 / 51 (0%) | **47 / 51 (92%)** | same test + `tools/ci_gate.php` |
| FR traced in its feature RTM | 49 / 51 (96%) | **51 / 51 (100%)** | same test |
| Line coverage | 31.0% (2711/8743) | **98.8% (7315/7403)** | `tools/ci_gate.php` (clover) |
| Method coverage | 40.5% (598/1477) | **97.5% (1327/1361)** | same |
| Class coverage | 19.6% (63/321) | **91.4% (264/289)** | same |
| Tests / assertions | 1447 / 3674 | **2626 / 6528** | `vendor/bin/phpunit` |
| Skipped tests | 1 | **0** | `phpunit` runs with `failOnSkipped` semantics by review rule |

The 88 uncovered lines (1.2%) and 34 uncovered methods (2.5%) are **provably
unreachable** — see §4.

---

## 2. What was changed

### 2.1 Dead code removed (36 files)

* 7 scratch/backup files inside `src/`: `*.copilot.php`, `import_statements-old.php`,
  `process_statements.copilot_refactored.php`, the stray `,bic2` editor file, `test.php`.
* 8 shadowed duplicate FQCNs (each declared a class the autoloader resolved to a
  different file): `Controller/BiLineItemController.php`,
  `FaBankImport/BankImportController.php`, `FaBankImport/TransactionProcessedEvent.php`,
  `FaBankImport/class.bi_transaction.php`, `FaBankImport/controllers/BiLineItemController.php`,
  `FaBankImport/models/ThirdPartyTransaction.php`, `HTML/Elements/HtmlBold.php`,
  `HTML/Elements/HtmlInternalCSSListList.php`.
* 2 legacy-class copies of the root tree inside `src/`
  (`FaBankImport/class.bank_import_controller.php`, `FaBankImport/class.ViewBiLineItems.php`).
* 4 byte-identical copies of the QFX parser file: `class.AbstractQfxParser.php`,
  `class.CibcQfxParser.php`, `class.ManuQfxParser.php`, `class.PcmcQfxParser.php`,
  `class.QfxParserFactory.php` all declared the same five global-namespace classes.
  One canonical file remains; the other four are one-line compatibility shims.
* 13 developer scratch scripts at the repo root and 4 orphan config files.

### 2.2 Traceability machinery (`tools/`)

| Tool | Purpose |
|---|---|
| `tools/lib/code_units.php` | shared scanner: what counts as a production unit |
| `tools/lib/rtm_index.php` | parses the 7 feature RTMs into `FR => [BR, UAT, title]` |
| `tools/traceability-map.php` | the class/function → FR map (301 entries) |
| `tools/inject_babok_tags.php` | writes `@BABOK FR-x \| BR-x \| UAT-x` into the docblocks |
| `tools/traceability_audit.php` | reports the three traceability percentages |
| `tools/rtm_code_matrix.php` | regenerates the "Code Cross-Reference" table in each RTM |
| `tools/scope_inventory.php`, `tools/reachability.php`, `tools/production_scope.php` | scope / dead-code analysis |
| `tools/coverage_gaps.php` | prioritised uncovered-line report from clover |
| `tools/ci_gate.php` + `tools/coverage-gate.json` | the CI gate (traceability + coverage) |

All 7 feature RTMs now carry a **Code Cross-Reference** table mapping every FR to
the classes that implement it, generated from the `@BABOK` tags, so the RTM and
the code cannot drift apart.

### 2.3 Tests

1,170 new tests across `tests/unit/{Application,Services,Processing,FaBankImport,LineItems,Screens,GapClosure,Http,Repositories,Repository,Quality}/`.

The test doubles for `db_escape()` were corrected to match FrontAccounting's real
contract (FA returns a **quoted** literal; the mock only escaped). Several test
expectations changed with them: they used to assert the SQL shape the inaccurate
mock produced (unquoted strings), and now assert the SQL that production actually
sends to MySQL.

### 2.4 Defects fixed in this pass

| Defect | Fix |
|---|---|
| `?` placeholders reached MySQL literally (`DatabasePartnerDataRepository`) | New `Repository/ParameterBinder`, which delegates quoting to the `FaDbAdapter` of `ksfraser/ksf-common-db` (added as a dependency) |
| String parameters were quoted twice (`TransactionRepository`) | Same binder, replacing five copies of the `"'" . db_escape($p) . "'"` idiom |
| `findById()` never bound its placeholder | Same binder |
| `preg_replace` expanded `$0` / `\1` in values | Same binder (literal substitution, no regex replacement semantics) |
| `isPost()` missing on the generic shell | Added to `Ksfraser\Application\Http\RequestHandler` |
| `getQuery()` missing on both shells | Added to both `RequestHandler` classes |
| Bank-transfer helper path resolved to a non-existent directory | `dirname(__DIR__, 4) . '/../ksf_modules_common/class.fa_bank_transfer.php'` |
| `FaGLWrapper` helper path was missing its directory separator | `__DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php'` |
| `db_escape()` mock did not match FA | Module test doubles corrected; famock still needs it |

### 2.5 CI

`.github/workflows/php.yml` previously **did not run the test suite at all** (the
step was commented out). It now runs the suite and the gate.

---

## 3. Defects found (not fixed — they need a decision or a follow-up change)

### Blocking

| # | Where | Defect |
|---|---|---|
| 1 | `composer.json` | **119 of 377 `src/` classes are not autoloadable.** The PSR-4 map points CamelCase namespaces at lower-case directories (`Ksfraser\Application\Config\Config` → `src/Ksfraser/Application/config/`), which fails on a case-sensitive filesystem. `Ksfraser\FaBankImport\views\*` and `view\*` declare namespace `Ksfraser\FaBankImport` / `…\View` from a sub-directory, so PSR-4 cannot reach them either. |
| 2 | `Repository/DatabasePartnerDataRepository.php` | Uses `?` placeholders with `db_query($sql, $params)`. FA has **no prepared statements** — the second argument is the error message, so the placeholders reached MySQL literally. **Fixed**: the repository now binds through the new `ParameterBinder`, which delegates the quoting to the `FaDbAdapter` of `ksfraser/ksf-common-db`. |
| 3 | `repositories/TransactionRepository.php` | The placeholder substitution was `"'" . db_escape($param) . "'"`. FA's `db_escape()` already returns a **quoted** literal (`includes/db/connect_db_mysqli.inc:135`), so every string parameter was quoted twice (`''value''`) — invalid SQL. The same block also used `preg_replace` with the value as the *replacement* string, so `$0` / `\1` in a value were expanded as backreferences. `findById()` never substituted its placeholder at all. **Fixed**: all six call sites now use the `ParameterBinder`. |
| 4 | `Application/Application.php` | `run()` calls `$request->isPost()`. The **module** shell's `RequestHandler` has it; the **generic** shell's (`Ksfraser\Application\Http\RequestHandler`) did not, so every request through the generic shell fatals — the error is swallowed by `catch (\Throwable)` and shown as a generic error page with HTTP 200. **Fixed**: `isPost()` added to the generic shell. |
| 5 | `controllers/AdminController.php` | Calls `$this->request->getQuery('start_date', $default)`; neither shell's `RequestHandler` had it, so `performanceReport()`, `exportMetrics()` and `anomalyReport()` all fatal. **Fixed**: `getQuery(string $key, $default = null)` added to both shells. |
| 6 | `Container.php` | Imports 9 classes that do not exist in the `Ksfraser\Application` namespace; the real implementations live in `Ksfraser\FaBankImport\*` with different constructors. `controllers/BankImportController.php` also imports the **wrong** container (`Ksfraser\Application\Container`), so its default construction path asks the generic shell for a controller that does not exist. |
| 7 | `Transaction.php:retrieveMatchingExistingJE()` | The old `fa_gl` block (lines 203-245) is a **deliberate** block comment — the developer commented out the pre-migration implementation when moving to `FaGLWrapper` (lines 247-255, which is live and sets `$this->matching_trans`). The comment style is ugly, but nothing is broken by it. |
| 8 | `TransactionFactoryImp.php` | Maps `MA` → `MatchedTransaction` and `ZZ` → `ManualTransaction`. **The MA / ZZ partner types are swapped.** |
| 9 | `Transaction.php::matchedVendor()` | Reads `$this->otherBankAccountt` (typo, never assigned) and calls `$this->matchedSupplierId()`, which the class does not declare — an `Error` that `catch (Exception)` cannot catch. |
| 10 | `handlers/BankTransferTransactionHandler.php` | `getFaBankTransferClassPath()` resolved to `src/Ksfraser/ksf_modules_common/class.fa_bank_transfer.php` (two levels up from the handler plus `../`), which does not exist — `ksf_modules_common` sits *next to* the module, not inside it. The `require_once` was **fatal** — the whole bank-transfer path died. **Fixed**: the path now climbs four levels (`dirname(__DIR__, 4) . '/../ksf_modules_common/…'`), which resolves both in the dev tree and in `<fa_root>/modules/`. |
| 10b | `FaGLWrapper.php` | The helper path was `__DIR__ . '../../../../../ksf_modules_common/class.fa_gl.php'` — **missing the directory separator**, so it resolved to a non-existent path and the wrapper always threw. **Fixed**: `__DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php'`. |
| 11 | `header_table.php:59-79` | `_( "$this->cell1['label']:" )` — PHP does not support array access in simple interpolation, so every label renders as `Array['label']:`. |
| 12 | `import_statements.php:133` | `print_r( $e, tru )` — `tru` is not a constant. On PHP 8 this throws `Error`, which the surrounding `catch (Exception $e)` does **not** catch: the screen dies instead of reporting the failure. |
| 13 | `repositories/TransactionRepository.php:87` | `findById()` never substitutes its `?` placeholder, so the statement reaches MySQL with a literal `?`. |
| 14 | `middleware/AuthMiddleware.php` (module tree) | Calls `$next($request)` **after** `redirect()`. The module's `ResponseHandler::redirect()` does not `exit()` (the generic shell's does), so an unauthenticated request is redirected *and still runs the controller action*. Security-relevant divergence between the two shells. |
| 15 | `HTML/Elements/HtmlStyleList.php`, `HtmlInternalCSSList.php`, `HtmlFormatting.php` (+ its 9 subclasses), `HtmlStyle.php` | **Fatal on declaration**: the signatures drop the parent's `: string` / `: void` return type, so PHP refuses to declare the class. These classes can never be loaded at all. |
| 16 | `views/admin/dashboard.php` | Divides by zero when the oldest day of the trend window has no samples — the admin dashboard 500s on an empty log. |
| 17 | `config/ConfigService.php`, `Results/TransactionResult.php` | Use PHP 7.4 typed properties while `composer.json` declares `"php": ">=7.3"` and pins `config.platform.php = 7.3`. The package would fatal on a 7.3 runtime. |
| 18 | `Model/BiLineItemModel.php:165` | Catches `Exception` **unqualified**, so it resolves to `Ksfraser\FaBankImport\Model\Exception` — a class the module never declares. A global `\Exception` escapes the catch. |

### Functional

| # | Where | Defect |
|---|---|---|
| 19 | `asgrim/ofxparser` | Single-line OFX/QFX documents cannot be parsed: the parser inserts a newline before every `<`, splitting `<CODE>0</CODE>`. `tests/sample.qfx` and `includes/test.qfx` both fail — which is why `tests/QfxParserTest.php` is in no phpunit suite and never runs. |
| 20 | `handlers/ManualSettlementHandler.php` vs `MatchedTransactionHandler.php` | The two handlers disagree on `get_trans_counterparty()`: one treats the result as a flat row, the other as a list of rows. The manual handler therefore never finds a person. |
| 21 | `AbstractRepository` / `QueryBuilder` | Not stateless: one `QueryBuilder` is created in the constructor and mutated by every method, so WHERE/ORDER/LIMIT clauses **accumulate across calls** (`find()` twice ⇒ `WHERE id=:param0 AND id=:param1`). |
| 22 | `PerformanceMonitoringMiddleware` | `before()` and `after()` each re-evaluate `shouldSample()` with a fresh `mt_rand()`; with a rate < 1 the two disagree and the sample is silently dropped. |
| 23 | `AlertService::checkAndSendAlerts()` | Mixes granularity: the range start is `date('Y-m-d', strtotime('-1 hour'))` (midnight) while the end is a full timestamp. |
| 24 | `AbstractRepository` (module tree) | `QueryBuilder::first()` permanently sets `LIMIT 1`. |
| 25 | `metrics/EventDispatcher` | `dispatch()` calls listeners without try/catch — one failing listener breaks the dispatch. `TransactionLogger` writes with `FILE_APPEND` but no `LOCK_EX`. |
| 26 | `DatabaseFactory` | Builds a DSN with no port, so it can only reach MySQL on 3306 while this deployment publishes MariaDB on 3307. |
| 27 | `includes/pdata.inc` (legacy) | `count( $arr )` on the result of `get_partner_data()`, which is `false` when there is no row → `TypeError` on PHP 8. |
| 28 | `repositories/TransactionRepository.php` | Placeholder substitution uses `preg_replace('/\?/', $escapedParam, $sql, 1)` — the value is the *replacement* string, so `$0` / `\1` in a value are expanded as backreferences. |
| 29 | `DuplicateDetector` / `DetectedAccountAssociationKey` / `PartnerTypeRegistry` | Defensive branches that can never run (the action is normalised before the switch; the clamp arithmetic is always positive; `glob()` on an existing directory never returns `false`). |
| 30 | `vendor/ksfraser/famock` (and the module's own test doubles) | **`db_escape()` is mocked as `addslashes()`, but FA's real `db_escape()` returns a quoted literal.** The mock therefore made the repositories' double-quoted SQL (`''value''`) look correct in the tests while production sent invalid SQL to MySQL. The module's own doubles now mirror FA (`includes/db/connect_db_mysqli.inc:135`): strings come back quoted and escaped, numbers unquoted, empty values as `''`. The famock package itself still needs the same correction. |

### Requirements-process gaps (fixed in the RTMs, still open in the UAT plans)

* `FR-PROC-013` (Add Customer / Add Vendor) and `FR-VAL-004` (suggested matches) had
  **no row in their RTM and no UAT case**. Both RTMs now carry an explicit
  `**GAP — no UAT case exists**` row; the UAT plans still need `UAT-PROC-013` and
  `UAT-VAL-006`.
* `FR-002`, `FR-003`, `FR-007`, `FR-008` (the account-resolution screen) are still
  implemented **only by the legacy root tree**; the PSR-4 tree implements detection
  and mapping (`FR-001`, `FR-004`…`FR-006`) but not the resolution screen. The CI
  gate lists them explicitly (`requirements_implemented_in_legacy_tree`) so the gap
  stays visible until the migration lands.

---

## 4. The remaining 88 uncovered lines — why they are unreachable

| Group | Lines | Proof |
|---|---|---|
| `HtmlStyleList`, `HtmlInternalCSSList`, `HtmlFormatting` (+9 subclasses), `HtmlStyle` | 42 | PHP refuses to declare the class: *"Declaration of …::getHtml() must be compatible with …HtmlElementInterface::getHtml(): string"*. No line of the file can ever execute. |
| `FaGLWrapper.php` | 15 | The wrapper now finds `ksf_modules_common/class.fa_gl.php`, but that file pulls in FrontAccounting's `includes/types.inc`, which is not in this repository, so the helper cannot be instantiated outside a FrontAccounting installation. |
| `views/DisplaySettledTransactions.php` | 11 | `new SettledPaymentTable` / `SettledDepositTable` / `SettledDefault()` are called with no argument although all three constructors require one → `ArgumentCountError` on entry, so the statement never completes. |
| Dead branches (`AbstractTransactionHandler:93-95`, `TransactionProcessor:97/107/150`, `BiLineItemView:174/366/369`, `FileStorageService:77/241`, `DetectedAccountAssociationKey:46`, `DuplicateDetector:111`, `TransType:8`, `PartnerTypeRegistry:122`) | 20 | Each is guarded by a condition that is provably always false (a return type that guarantees the `instanceof`, a `glob()` on an existing directory, an `is_uploaded_file()` that is never true in CLI, arithmetic that is never negative, …). |

`tools/coverage-gate.json` pins these files on an allow-list, so **any new gap fails
the build** while the gate is ratcheted upwards.

---

## 5. Recommended order of work

1. **Fix the autoloader (#1)** — 119 classes that cannot be loaded is the root cause
   behind #4, #5, #6 and #15. Either rename the directories to match the namespaces
   or correct the PSR-4 map.
2. **Fix the SQL generation (#2, #3, #13, #28)** — the repositories cannot write
   correct SQL today.
3. **Fix the fatal paths (#7, #8, #9, #10, #11, #12)** — the partner-type swap and the
   commented-out GL matcher are silent data-corruption risks.
4. **Migrate the account-resolution screen** (FR-002/003/007/008) and close the two
   UAT gaps (UAT-PROC-013, UAT-VAL-006).
5. **Resolve the PHP floor** — either drop the typed properties in #17 or raise
   `composer.json` to `>=7.4` (the container runtime is 7.4).
