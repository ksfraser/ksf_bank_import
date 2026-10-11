# PROD vs Repo Analysis
**Branch:** `StatementReconcilliation`  
**Generated:** 2026-04-28  
**Comparison tool:** `tools/compare_prod_php.php` + manual review

---

## Summary Counts

| Category | Count |
|---|---|
| Files that differ (DIFF) | 388 |
| Files in PROD but not repo (NEW_IN_PROD) | 274 |
| Files in repo but not PROD (MISSING_IN_PROD) | 444 |
| Total PROD files | 999 |
| Total repo files | 1,169 |

Full list: `tools/prod_comparison.txt`

---

## ⚠️ CRITICAL: Items That Could Break PROD on Deployment

### 1. HTML Library Migration (Most Critical)
The repo migrated 200+ HTML element files to the `ksfraser/html` Composer package.  
PROD still has those files directly under `src/Ksfraser/HTML/`.

**Risk:** After deploying the repo, `composer install` **must** be run on the server before the application is used. Without it, all classes like `Ksfraser\HTML\Elements\HtmlA`, `Ksfraser\HTML\Composites\HTML_ROW_LABEL`, etc. will fail to autoload.

**Action required:** Run `composer install` (or `composer install --no-dev`) immediately after file deployment.

---

### 2. Deleted `src/Ksfraser/FaBankImport/Service/` Directory
We removed these files from the repo (they were duplicate/misnamed vs `Services/`):
- `src/Ksfraser/FaBankImport/Service/FileStorageService.php`
- `src/Ksfraser/FaBankImport/Service/FileStorageServiceInterface.php`
- `src/Ksfraser/FaBankImport/Service/FileUploadService.php`
- `src/Ksfraser/FaBankImport/Service/DuplicateDetector.php`
- `src/Ksfraser/FaBankImport/Service/TransactionCounter.php`

PROD still has these. **Risk:** Any `require_once` referencing these paths would break after deployment.

**Action:** Before deploying, grep PROD for `require_once.*Service/` to confirm no production code uses these paths.

---

### 3. Deleted `src/Ksfraser/Application/services/TransactionLogger.php`
We deleted this 683-byte stub from the repo (it duplicated `SimpleCommandBus`).  
PROD still has it.

**Risk:** Low (tiny stub), but any `require_once` of `TransactionLogger.php` in production scripts would fail.

---

## 🆕 NEW_IN_PROD: PROD Has Functionality Not In Repo

### CSV Import Feature (NOT in repo branch)
These files are in PROD but completely absent from the repo:
- `includes/CsvFieldMapper.php` (9,969 bytes) — Intelligent CSV header → field mapping with fuzzy matching
- `includes/CsvMappingTemplate.php` — CSV mapping templates  
- `includes/GenericCsvParser.php` (13,578 bytes) — Generic CSV bank statement parser
- `includes/ro_manulife_csv_parser.php` — Manulife-specific CSV parser
- `includes/csv_mapping_review.php` — Mapping review UI

**Significance:** This is a complete CSV import feature not yet on the `StatementReconcilliation` branch. It was developed and deployed directly to production.

**Action:** These files should be committed to the repo branch. They won't be lost from PROD when deploying the repo (the repo doesn't have files at these paths, so they won't be overwritten), but they should be version-controlled.

---

### `src/Ksfraser/FaBankImport/models/PairedJEs.php` (13,868 bytes)
Model for finding paired bank transfers. In PROD but not in repo.  
However, the current `class.bi_lineitem.php` has `isPaired()` returning `false` in BOTH PROD and repo — so `PairedJEs.php` is currently dormant/unused.

**Action:** No immediate risk. Should be committed to repo for completeness.

---

### Root-Level Duplicate Service Files in PROD
These exist at PROD root (`BankTransferAmountCalculator.php`, `BankTransferFactory.php`, etc.) — they appear to be OLD copies from before the code was moved to `Services/`. They are NOT at repo root.

**Significance:** These are stale copies. The canonical versions are in `Services/`. PROD may have old `require_once('BankTransferFactory.php')` calls at root.

**Action:** Check PROD `process_statements.php` and other entry points for root-level require_once of these files.

---

### `src/Ksfraser/FaBankImport/Views/` (capital V) — 23 PHP files
PROD has a capital-V `Views/` directory AND a lowercase `views/` directory:
- `PROD/src/Ksfraser/FaBankImport/Views/` — 23 PHP files (uppercase)
- `PROD/src/Ksfraser/FaBankImport/views/` — 21 PHP files (lowercase)

The repo only has `src/Ksfraser/FaBankImport/views/` (lowercase, 27 files).

**On Linux:** These are separate directories! PROD's production server may have both. The uppercase `Views/` files include newer SRP view class variants.

**Analysis completed:** All 23 uppercase `Views/` files were compared against repo lowercase `views/`:
- `MatchingGLsView.php` in uppercase `Views/` — contains stale `Comment` class (misnamed remnant). **Skip — do not copy.**
- `ImportUploadForm.php` in uppercase `Views/` — genuine new file with `Parsers` and `ImportUploadForm` classes. **✅ Copied to `views/` in commit d545a23.**
- All remaining 21 uppercase `Views/` files already exist as diff entries in lowercase `views/`.

**Deployment action for Linux server:**
```bash
# On the Linux production server, after repo deployment:
# Check if uppercase Views/ exists as a separate directory
ls -la /var/www/html/infra/accounting/modules/bank_import/src/Ksfraser/FaBankImport/

# If Views/ and views/ both exist, merge and remove uppercase:
cp -n /var/www/html/.../Views/* /var/www/html/.../views/
rm -rf /var/www/html/.../Views/

# The repo canonical directory is lowercase views/ — all references use that.
```

---

### OFX Parser Classes (root level in PROD)
- `class.AbstractQfxParser.php`
- `class.CibcQfxParser.php`
- `class.ManuQfxParser.php`
- `class.PcmcQfxParser.php`
- `class.QfxParserFactory.php`

These are at PROD root but NOT in the repo. These appear to be bank-specific OFX parsers.

**Action:** Check if anything in PROD requires these from root. They may be used by `qfx_parser.php` or `parsers.inc`.

---

## 🔍 KEY DIFF FILES: Business Logic Differences

### `class.bi_lineitem.php` — DIFF (38,172 PROD vs 37,860 repo)
Repo has more refactoring:
- SRP View classes (`TransDate`, `TransType`, `OurBankAccount`, etc.) replace `label_row()` calls
- `PartnerFormData` replaces direct `$_POST` access
- `HtmlA`, `HtmlHidden` HTML library classes replace raw HTML strings
- `ViewFactory` feature flag for partner type views
- `getHtml()` / `getLeftTd()` / `getRightTd()` methods added

**Both PROD and repo have:** `isPaired()` returning `false`, `findPaired()` with legacy code
→ No paired transfer regression risk here.

---

### `process_statements.php` — DIFF (36,073 PROD vs 37,198 repo)
Repo is larger (more features). Needs detailed review before deployment.

---

### `class.bank_import_controller.php` — DIFF (38,172 PROD vs 37,860 repo bytes)

---

### `composer.json` / `composer.lock` — DIFF
Repo adds `"ksfraser/html": "^2.0"` dependency. PROD has older `composer.json` without it.  
**Action:** Deploy repo composer files AND run `composer install` on server.

---

## ✅ MISSING_IN_PROD: New Features in Repo Not Yet in PROD

### New `StatementReconcile` Feature Module
Entire new `src/Ksfraser/FaBankImport/StatementReconcile/` hierarchy (30+ files):
- Domain entities: `ReconciliationSession`, `StatementOcr`
- Application services, controllers, views
- Infrastructure: migrations, OCR (Ollama integration), persistence
- Matching engine
- 30+ test files in `tests/unit/StatementReconcile/`

**This is the primary feature of the `StatementReconcilliation` branch.**

---

### New View Files
- `views/AddCustomerButtonRow.php`
- `views/AddVendorButtonRow.php`
- `views/ToggleTransactionTypeButton.php`

---

### HTML Library Migration Artifacts
- `src/Ksfraser/HTML/archived/` — archive of original HTML files
- `src/Ksfraser/HTML/Buttons/ActionButton.php` — New override
- `tools/diffs/` and `tools/diffs_canonical/` — HTML migration diff files

---

## 📋 Deployment Checklist

Before deploying `StatementReconcilliation` branch to PROD:

- [ ] **Pre-deployment backup** of current PROD files and database
- [x] **Commit CSV import files** to repo: `includes/CsvFieldMapper.php`, `includes/CsvMappingTemplate.php`, `includes/GenericCsvParser.php`, `includes/ro_manulife_csv_parser.php`, `includes/csv_mapping_review.php` ✅ done in commit d545a23
- [x] **Commit `ImportUploadForm.php`**: `src/Ksfraser/FaBankImport/views/ImportUploadForm.php` ✅ done in commit d545a23
- [ ] **PairedJEs.php**: left as stub — restore when PairedJEs feature is implemented
- [x] **Verify no require_once** of deleted `Service/` files in PROD — ✅ clean (no PROD code references Service/)
- [ ] **Verify no require_once** of root-level service files (`BankTransferAmountCalculator.php`, etc.) in PROD
- [ ] **Run `composer install`** after deploying repo files to production server
- [ ] **Consolidate `Views/` on Linux server**: if uppercase `Views/` directory exists, merge into `views/` and remove it (see Views/ section above for commands)
- [ ] **OFX parser files**: Root-level `class.AbstractQfxParser.php` etc. pre-date custom OFX parser — ignore, not deployed
- [ ] **Database migrations**: Run any new migrations from `StatementReconcile/Infrastructure/Database/Migrations/`
- [ ] **Post-deployment smoke test**: Load process_statements.php, verify line item display works

---

## 📊 Noise/Non-issues in diff

Large numbers of DIFF/NEW/MISSING entries are tooling artifacts:
- `tools/diffs/`, `tools/diffs_canonical/` — Analysis tooling (not deployed)
- `tests/` differences — Test files (not deployed to production)
- `*_latest.txt`, `test_output*.txt` — Local test output files
- `src/Ksfraser/HTML/archived/` — Archives (not deployed)
- `.vscode/`, `.env`, `.git_commit_msg.txt` — Dev tooling
- Markdown `*.md` files — Documentation (not functionality)

After filtering these out, the meaningful deployment differences are much smaller.
