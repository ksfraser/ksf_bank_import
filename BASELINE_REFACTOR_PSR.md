# Refactor Baseline — branch `refactor-psr`

**Date:** 2026-08-22
**Baseline commit:** e992498 (`main`)

## Purpose

TDD-first refactor to SRP/PSR compliance with 100% coverage target.
**Rule: no production code moves/renames until the test suite is green or a
failure is documented here as pre-existing.** This file records the state we
started from so regressions are detectable.

## Suite status at branch creation

After test-infra fixes only (composer PSR-4 case aliases — see below):

```
Tests: 1499, Assertions: 3074, Errors: 181, Failures: 84, Skipped: 51, Incomplete: 15
```

Original state on main (before autoload fixes): 468 errors / 82 failures /
2309 assertions — suite did not even bootstrap (`Handlers` namespace had no
PSR-4 mapping).

## Test-infra fixes applied (no production code touched)

Added case-alias PSR-4 mappings in composer.json (tests use PascalCase
namespaces, source dirs are lowercase):

- `Ksfraser\FaBankImport\{Services,Config,Database,Commands,Http,Exceptions,Repositories,Factories}\`
  -> corresponding lowercase dirs under `src/Ksfraser/FaBankImport/`
- `Ksfraser\Application\Middleware\` -> `src/Ksfraser/Application/middleware/`
- `Ksfraser\FaBankImport\Handlers\` -> `src/Ksfraser/FaBankImport/handlers/`

## Remaining failure categories (root causes, to fix one at a time)

| # | Category | Approx tests | Root cause |
|---|---|---|---|
| 1 | HTML `Elements` vs `Composites` | ~54 | Tests reference `Ksfraser\HTML\Elements\*`; classes declare `Ksfraser\HTML\Composites\*`. Decide canonical namespace (Elements), move + alias |
| 2 | Legacy global classes | ~34 | `bi_lineitem`, `ViewBILineItems` referenced bare by tests; legacy files not loadable outside FA context |
| 3 | `Models\AbstractThirdPartyTransaction` | 20 | Class renamed/missing vs test expectation |
| 4 | Symfony `Response` | 14 | `Http\ResponseHandler` depends on symfony/http-foundation which is not required |
| 5 | `Services\TransactionProcessor` | 12 | Test expects Services ns; class lives in `Ksfraser\FaBankImport` root |
| 6 | `PartnerTypes\UnknownPartnerType` | 8 | Class does not exist yet (planned feature) |
| 7 | DB/Fixture-dependent integration tests | remainder | FA globals/db assumptions |

## Open bugs being tracked

GitHub issues #19-#41 (bugs) and #1-#9, #13, #17, #23, #29, #32-#34, #42
(feature requests) on ksfraser/ksf_bank_import. Bug fixes land on this
branch only after their characterization/regression tests exist.

## Session progress log

### 2026-08-22 (session 1)
- HTML category (1) RESOLVED: Elements/Buttons + Elements/Form/Input canonical
  namespaces with Composites shims; tests/HTML suite wired in (49 green);
  new input types TDD'd (text/radio/checkbox); 2 latent missing-import bugs fixed.
- Category 2 PARTIAL: KSF_TEST_COMPAT layer in tests/bootstrap.php
  (fa_stubs.php + inert generic_fa_interface_model stub + guarded legacy
  requires). bi_lineitem now LOADS and constructs under PHPUnit.
- PRODUCTION BUGS FIXED in class.bi_lineitem.php: two hardcoded
  `__DIR__ . '/Views/'` requires (lines ~116, ~975) that fatal on Linux
  (case-sensitive FS) — now use $viewsDir; displayPartnerType() recomputes
  $viewsDir locally since file-scope vars are not in function scope.
- Suite: 170E -> 143E, assertions 3184 -> 3200. Failures 84 -> 88 (display
  characterization tests now RUN and fail on deeper ksf_modules_common deps).
- Remaining category-2 work IS the decoupling refactor: bi_lineitem call
  chain pulls ksf_modules_common models (BankAccountByNumber etc.). Next:
  extract a BankAccountResolver interface + FA-backed implementation so
  bi_lineitem stops requiring module-common files.

### 2026-08-22 (session 2)
- Decoupling slice: MatchingTransactionsFinderInterface (+Fake +FaMatchingJEsFinder
  guarded wrapper). bi_lineitem::findMatchingExistingJE() no longer hard-requires
  MatchingJEs; compat fallback = empty matches.
- Full-row bi_lineitem::getHtml() now renders under PHPUnit (3 green tests).
- PRODUCTION BUG FIXED: views/CustomerPartnerTypeView.v2.php included
  ksf_modules_common via CWD-relative @include (fragile in prod, fatal chain
  under test) — now explicit file-existence + compat guard; graceful-degradation
  path (skip allocatable invoices) is what runs without the dependency.
- Suite: 138E -> 128E, assertions 3200 -> 3239.
- Next slices: same injection pattern for remaining legacy chains surfaced by
  full render (fa_gl display paths); then categories 3-6.

## Plan

1. Fix categories 1-7 above (test-infra first, then namespace moves WITH
   class_alias shims where production callers exist).
2. Only when green: begin SRP decomposition module-by-module, keeping the
   suite green after every commit.
3. Coverage gate added to phpunit.xml once green (failUnder threshold raised
   incrementally toward 100%).
