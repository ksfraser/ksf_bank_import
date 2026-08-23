# Refactor Plan — `refactor-psr` branch

**Tracking:** this document + BASELINE_REFACTOR_PSR.md (results log)
**Branch:** refactor-psr
**Rule:** TDD / characterization-first. No production change without a test
pinning current behaviour; suite must not regress vs baseline.

## Goal

1. SRP/PSR-compliant module structure under `ksfraser\FaBankImport\`
2. Green suite as the safety net, then **100% coverage** (gate raised incrementally)
3. Bug fixes (#19–#45) land only on top of green tests

## Completed phases

| Phase | Result |
|---|---|
| Test infra | PSR-4 case-alias map completed; bootstrap compat layer (fa_stubs, inert legacy base, KSF constants); orphaned suites wired in |
| HTML package | PEAR-style `Elements\Buttons`, `Elements\Form\Input` (+ new text/radio/checkbox types), canonical `HtmlLabelRow`, alias shims |
| Legacy decoupling slice 1 | `BankAccountDetailsProviderInterface` — bi_lineitem no longer requires ksf_modules_common for bank details |
| Legacy decoupling slice 2 | `MatchingTransactionsFinderInterface` — full-row getHtml() renders in tests |
| Repository contract | Interface rewritten to real contract; Container TypeError fixed; TransactionService aligned |
| Domain semantics | MA=Manual Settlement, ZZ=Matched locked; UNKNOWN ('UN') classification-only via isDispatchable() |

## Production bugs fixed en route (see #43–#45)

- `/Views/` casing requires ×2 (Linux-fatal)
- `$viewsDir` out of function scope
- Off-by-one module-common require path
- Missing HtmlAttribute imports ×2
- CWD-relative includes ×2 (v2 view + ViewBiLineItems)
- Handler discovery dir/case wrong
- basename()-over-backslash platform bug
- **#43 PartnerFormData flat POST key** (dispatch-invisible writes)
- Container TypeError injecting unimplemented interface

## Remaining work (ordered)

### P1 — Suite green (current ~74E/89F)
1. Stale `Controllers\BankImportControllerTest` — rewrite to canonical
   `Ksfraser\FaBankImport\Controllers\BankImportController` API (index/process)
2. `MiddlewarePipeline` mapping (4) — verify namespace/dir, add alias if needed
3. `InvalidConstantHandler` fixture (2) — test fixture class missing
4. symfony decision (#44) → fix ResponseHandler tests (14)
5. Null-collaborator chains in bi_lineitem views (~10 remaining)
6. Residual fixture/assertion drift to zero

### P2 — Coverage gate
7. Add `failUnder` to phpunit.xml starting at current %; raise in +5% steps
8. Cover parsers (mt940/qfx/ro_*) with fixture-driven tests (sample files exist)

### P3 — SRP decomposition (post-green)
9. bi_lineitem: extract partner-type display strategies fully (drop
   generic_fa_interface inheritance; keep class_alias shim for FA install)
10. Views: finish ViewFactory pattern; delete .copilot/.bak/dead variants
11. Root-level legacy classes → src/ PSR-4 (class.bi_*.php → src/Ksfraser/FaBankImport/...)

### P4 — Bug fixes on top
12. GitHub #19–#42: each gets a failing regression test first

## Working agreement

- Every commit: suite green or failures documented in BASELINE doc
- Platform-sensitive patterns banned (see #45 audit greps)
- No skipped tests without a documented reason string
