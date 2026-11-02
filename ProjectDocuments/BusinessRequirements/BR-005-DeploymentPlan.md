# Incremental PROD Deployment Plan
**Generated from:** `tools/prod_comparison.txt` (2026-04-29 05:02:36)  
**Branch:** `StatementReconcilliation`  
**Method:** Fresh `php tools/compare_prod_php.php` run — 401 DIFF, 248 NEW_IN_PROD, 442 MISSING_IN_PROD

> **Note on PROD snapshot:** The `PROD/` directory copy predates the user's manual Views/ cleanup on the real server. Differences involving `views/` vs `Views/` casing reflect that cleanup.

---

## STEP 1: Pre-Deployment (Critical)

### 1a. Backup PROD database + files
```bash
mysqldump -u<user> -p<pass> <db> > backup_$(date +%Y%m%d).sql
rsync -av /var/www/html/infra/accounting/modules/bank_import/ backup_bank_import/
```

### 1b. Confirm stale directories on real server (if not done)
```bash
# Should NOT exist — delete if present:
ls /path/to/bank_import/src/Ksfraser/FaBankImport/views/     # lowercase (should be gone)
ls /path/to/bank_import/src/Ksfraser/FaBankImport/Views/     # uppercase (correct one)
```

---

## STEP 2: Deploy Updated Core Files (DIFF — files in both but differ)

### 2a. Root-level entry points (HIGH PRIORITY)
These directly affect the running application:
```
app.php
import_statements.php
process_statements.php
hooks.php
qfx_parser.php
parsers.inc
pdata.inc
includes.inc
validate_gl_entries.php
html_migration_map.php
build_partner_keyword_data.php
VendorListManager.php
```

### 2b. Core legacy class files (HIGH PRIORITY)
These are the most-changed files — repo has SRP views, PartnerFormData, HtmlA/HtmlHidden:
```
class.bi_lineitem.php
class.bi_statements.php
class.bi_transaction.php
class.bi_transactions.php
class.bank_import_controller.php
class.transactions_table.php
class.ViewBiLineItems.php
class.bi_transactions_refactored_example.php
```

### 2c. Services/ directory (6 files)
```
Services/BankTransferAmountCalculator.php
Services/BankTransferFactoryInterface.php
Services/ExchangeRateService.php
Services/TransactionFilterService.php
Services/TransactionUpdater.php
Services/TransferDirectionAnalyzer.php
```

### 2d. OperationTypes/ directory  
```
OperationTypes/OperationTypeInterface.php
OperationTypes/OperationTypesRegistry.php
```

### 2e. Root-level views/ (view PHP files that differ)
```
views/AddCustomerButton.php
views/AddVendorButton.php
views/AllocatableInvoicesTable.php
views/AmountCharges.php
views/BankTransferPartnerTypeView.php
views/BankTransferPartnerTypeView.v2.php
views/CustomerPartnerTypeView.v2.php
views/DataProviders/CustomerDataProvider.php
views/DataProviders/PartnerDataProviderInterface.php
views/DataProviders/QuickEntryDataProvider.php
views/DataProviders/SupplierDataProvider.php
views/HTML_ROW.php
views/HTML_ROW_LABELDecorator.php
views/LabelRowBase.php
views/LineitemDisplayLeft.php
views/MatchingGLS.php
views/PartnerTypeDisplayStrategy.php
views/ProcessTransactionButtonRow.php
views/QuickEntryPartnerTypeView.php
views/QuickEntryPartnerTypeView.v2.php
views/SubmitButton.php
views/SubmitButtonRow.php
views/SupplierPartnerTypeView.php
views/SupplierPartnerTypeView.v2.php
views/ToggleTransactionRow.php
views/TransDate.php
views/TransTitle.php
views/TransactionTypeLabel.php
views/UnsetTransButtonRow.php
views/ViewFactory.php
```
> Delete from views/: `views/_K1XBQ~9`, `views/_n` (temp files in PROD snapshot)

### 2f. includes/ directory updates
```
includes/parser.php
includes/qfx_parser.php
includes/banking.php
includes/parsers.inc
includes/includes.inc
includes/Parser23.php
includes/example.ofx.php
includes/test_import.php
includes/test_parser.php
```
> Note: `includes/CsvFieldMapper.php`, `includes/CsvMappingTemplate.php`, `includes/GenericCsvParser.php`, `includes/ro_manulife_csv_parser.php`, `includes/csv_mapping_review.php` — likely ONLY differ due to Windows CRLF vs Linux LF line endings (they were copied FROM PROD verbatim in commit d545a23). Verify before overwriting.

### 2g. src/Ksfraser/ application layer (partial — key ones)
```
# src/Ksfraser/ root level:
src/Ksfraser/BankAccountDataProvider.php
src/Ksfraser/CustomerDataProvider.php
src/Ksfraser/FormFieldNameGenerator.php
src/Ksfraser/MatchingTransactionsList.php
src/Ksfraser/PartnerFormData.php
src/Ksfraser/PartnerFormFactory.php
src/Ksfraser/PartnerSelectionPanel.php
src/Ksfraser/PartnerTypeConstants.php
src/Ksfraser/QuickEntryDataProvider.php
src/Ksfraser/SettledTransactionDisplay.php
src/Ksfraser/SupplierDataProvider.php
src/Ksfraser/UrlBuilder.php

# src/Ksfraser/Controller/:
src/Ksfraser/Controller/BiLineItemController.php

# src/Ksfraser/View/ (old location — note: file still exists in both):
src/Ksfraser/View/BiLineItemView.php

# src/Ksfraser/Views/ (capital V):
src/Ksfraser/Views/CommentSubmitView.php
src/Ksfraser/Views/PartnerTypeSelectorView.php

# src/Ksfraser/Model/:
src/Ksfraser/Model/BiLineItemModel.php

# src/Ksfraser/PartnerTypes/:
src/Ksfraser/PartnerTypes/AbstractPartnerType.php
src/Ksfraser/PartnerTypes/BankTransferPartnerType.php
src/Ksfraser/PartnerTypes/CustomerPartnerType.php
src/Ksfraser/PartnerTypes/ManualSettlementPartnerType.php
src/Ksfraser/PartnerTypes/MatchedPartnerType.php
src/Ksfraser/PartnerTypes/PartnerTypeInterface.php
src/Ksfraser/PartnerTypes/PartnerTypeRegistry.php
src/Ksfraser/PartnerTypes/QuickEntryPartnerType.php
src/Ksfraser/PartnerTypes/SupplierPartnerType.php

# src/Ksfraser/FrontAccounting/:
src/Ksfraser/FrontAccounting/TransactionTypes/TransactionTypeInterface.php
src/Ksfraser/FrontAccounting/TransactionTypes/TransactionTypesRegistry.php
```

### 2h. src/Ksfraser/FaBankImport/ core (non-StatementReconcile)
```
# FaBankImport root:
src/Ksfraser/FaBankImport/class.ViewBiLineItems.php
src/Ksfraser/FaBankImport/class.transactions_table.php
src/Ksfraser/FaBankImport/manage_partners_data.php
src/Ksfraser/FaBankImport/view_statements.php
src/Ksfraser/FaBankImport/process_statements_preclean.php
src/Ksfraser/FaBankImport/test.php

# Application/Partner/:
src/Ksfraser/FaBankImport/Application/Partner/KeywordExtractor.php
src/Ksfraser/FaBankImport/Application/Partner/PartnerDataService.php
src/Ksfraser/FaBankImport/Application/Partner/PartnerDataServiceInterface.php
src/Ksfraser/FaBankImport/Application/Partner/PartnerSearchService.php
src/Ksfraser/FaBankImport/Application/Partner/ScoringEngine.php
src/Ksfraser/FaBankImport/Application/Partner/TrainingService.php

# API/:
src/Ksfraser/FaBankImport/API/APIErrorResponse.php
src/Ksfraser/FaBankImport/API/DTOs.php
src/Ksfraser/FaBankImport/API/MatchTransactionRequest.php
src/Ksfraser/FaBankImport/API/MatchTransactionResponse.php
src/Ksfraser/FaBankImport/API/MatchingAPI.php
src/Ksfraser/FaBankImport/API/PartnerStatsResponse.php
src/Ksfraser/FaBankImport/API/ReportSummaryResponse.php

# Infrastructure/:
src/Ksfraser/FaBankImport/Infrastructure/Config/ConfigException.php
src/Ksfraser/FaBankImport/Infrastructure/Config/EnvironmentConfig.php
src/Ksfraser/FaBankImport/Infrastructure/Database/Migration.php
src/Ksfraser/FaBankImport/Infrastructure/Database/MigrationException.php
src/Ksfraser/FaBankImport/Infrastructure/Database/MigrationRunner.php
src/Ksfraser/FaBankImport/Infrastructure/Database/Migrations/CreatePartnerTables.php
src/Ksfraser/FaBankImport/Infrastructure/Database/Migrations/Migration002AddLastMatchedTs.php
src/Ksfraser/FaBankImport/Infrastructure/Database/PartnerRepositoryPdoImpl.php
src/Ksfraser/FaBankImport/Infrastructure/Error/ErrorHandler.php
src/Ksfraser/FaBankImport/Infrastructure/Factory/PartnerServiceFactory.php
src/Ksfraser/FaBankImport/Infrastructure/Logger/FileLogger.php

# Services/ (uppercase):
src/Ksfraser/FaBankImport/Services/ConfidenceEnhancer.php
src/Ksfraser/FaBankImport/Services/CustomerMatchingConfiguration.php
src/Ksfraser/FaBankImport/Services/CustomerScoringEngineFactory.php
src/Ksfraser/FaBankImport/Services/PartnerMatcherFactory.php
src/Ksfraser/FaBankImport/Services/Rules/AmountMatchRule.php
src/Ksfraser/FaBankImport/Services/Rules/BankAccountMatchRule.php
src/Ksfraser/FaBankImport/Services/Rules/InvoiceDetectionRule.php
src/Ksfraser/FaBankImport/Services/Rules/RefundDetectionRule.php
src/Ksfraser/FaBankImport/Services/Rules/VendorNameMatchRule.php
src/Ksfraser/FaBankImport/Services/Scoring/AmountRangeRule.php
src/Ksfraser/FaBankImport/Services/Scoring/RecencyRule.php
src/Ksfraser/FaBankImport/Services/Scoring/ScoringEngineFactory.php
src/Ksfraser/FaBankImport/Services/Scoring/ScoringRule.php
src/Ksfraser/FaBankImport/Services/Scoring/ScoringRuleEngine.php
src/Ksfraser/FaBankImport/Services/Scoring/SupplierCandidate.php
src/Ksfraser/FaBankImport/Services/Scoring/TypeConsistencyRule.php
src/Ksfraser/FaBankImport/Services/SupplierMatchResult.php
src/Ksfraser/FaBankImport/Services/SupplierMatcher.php
src/Ksfraser/FaBankImport/Services/SupplierMatchingConfiguration.php
src/Ksfraser/FaBankImport/Services/SupplierMatchingRules.php
src/Ksfraser/FaBankImport/Services/SupplierScoringEngineFactory.php
src/Ksfraser/FaBankImport/Services/SupplierTransactionMatcher.php
src/Ksfraser/FaBankImport/Services/TransactionCounter.php
src/Ksfraser/FaBankImport/Services/TransactionMatchResult.php
src/Ksfraser/FaBankImport/Services/TransactionMatcherIntegration.php
src/Ksfraser/FaBankImport/Services/TransactionMatchingService.php
src/Ksfraser/FaBankImport/Services/TransactionPartnerMatcher.php
src/Ksfraser/FaBankImport/Services/VendorCandidate.php

# FaBankImport Views/ (uppercase — canonical):
src/Ksfraser/FaBankImport/views/AddCustomerButton.php
src/Ksfraser/FaBankImport/views/AddNoButton.php
src/Ksfraser/FaBankImport/views/AddVendorButton.php
src/Ksfraser/FaBankImport/views/Comment.php
src/Ksfraser/FaBankImport/views/DisplaySettledTransactions.php
src/Ksfraser/FaBankImport/views/Operation.php
src/Ksfraser/FaBankImport/views/PartnerSubSelect.php
src/Ksfraser/FaBankImport/views/PartnerType.php
src/Ksfraser/FaBankImport/views/ProcessStatementsView.php
src/Ksfraser/FaBankImport/views/TransDate.php
src/Ksfraser/FaBankImport/views/TransType.php

# Other FaBankImport:
src/Ksfraser/FaBankImport/Entity/PartnerEntity.php
src/Ksfraser/FaBankImport/Entity/PartnerMatchResult.php
src/Ksfraser/FaBankImport/Exception/PartnerException.php
src/Ksfraser/FaBankImport/Exception/PartnerNotFoundException.php
src/Ksfraser/FaBankImport/Exception/PartnerPersistenceException.php
src/Ksfraser/FaBankImport/Exception/PartnerValidationException.php
src/Ksfraser/FaBankImport/Exception/TrainingException.php
src/Ksfraser/FaBankImport/Contracts/Command.php
src/Ksfraser/FaBankImport/Contracts/Logger.php
src/Ksfraser/FaBankImport/Contracts/Migration.php
src/Ksfraser/FaBankImport/Contracts/PartnerRepository.php
src/Ksfraser/FaBankImport/Contracts/TrainingService.php
src/Ksfraser/FaBankImport/Repository/PartnerRepository.php
src/Ksfraser/FaBankImport/Repository/PdoPartnerRepository.php
src/Ksfraser/FaBankImport/Results/PaginatedTransactionResult.php
src/Ksfraser/FaBankImport/Reporting/MatchingReport.php
src/Ksfraser/FaBankImport/Reporting/OperationReport.php
src/Ksfraser/FaBankImport/Reporting/ReportingService.php
src/Ksfraser/FaBankImport/models/BankAccountByNumber.php
src/Ksfraser/FaBankImport/models/MatchingJEs.php
src/Ksfraser/FaBankImport/repositories/TransactionRepository.php
src/Ksfraser/FaBankImport/config/BankImportConfig.php
src/Ksfraser/FaBankImport/commands/CommandDispatcher.php
src/Ksfraser/FaBankImport/commands/ProcessTransactionCommand.php
src/Ksfraser/FaBankImport/Cli/Commands/MigrationCommand.php
src/Ksfraser/FaBankImport/Cli/Commands/TrainingCommand.php
src/Ksfraser/FaBankImport/Cli/Kernel.php
```

### 2i. src/Ksfraser/HTML/Elements/ (library updates — ~30 files)
These existing element files have updated content. Deploying `composer.json`/`composer.lock` and running `composer install` may supersede the need to manually copy these if they're now served from the vendor package. Verify after `composer install`.
```
src/Ksfraser/HTML/Elements/HtmlA.php        src/Ksfraser/HTML/Elements/HtmlBold.php
src/Ksfraser/HTML/Elements/HtmlButton.php   src/Ksfraser/HTML/Elements/HtmlEmail.php
src/Ksfraser/HTML/Elements/HtmlForm.php     src/Ksfraser/HTML/Elements/HtmlHidden.php*
src/Ksfraser/HTML/Elements/HtmlInput.php    src/Ksfraser/HTML/Elements/HtmlInputButton.php
src/Ksfraser/HTML/Elements/HtmlOption.php   src/Ksfraser/HTML/Elements/HtmlSelect.php
src/Ksfraser/HTML/Elements/HtmlTable.php    src/Ksfraser/HTML/HtmlAttributeList.php
src/Ksfraser/HTML/HtmlAttributesTrait.php   src/Ksfraser/HTML/HtmlElement.php
(+ ~20 more)
```

### 2j. src/Ksfraser/Application/ layer
```
src/Ksfraser/Application/Application.php
src/Ksfraser/Application/Bootstrap.php
src/Ksfraser/Application/Container.php
src/Ksfraser/Application/config/admin_routes.php
src/Ksfraser/Application/config/monitoring.php
src/Ksfraser/Application/controllers/AbstractController.php
src/Ksfraser/Application/database/DatabaseFactory.php
src/Ksfraser/Application/database/QueryBuilder.php
src/Ksfraser/Application/exceptions/UnauthorizedException.php
src/Ksfraser/Application/handlers/ErrorHandler.php
src/Ksfraser/Application/http/RequestHandler.php
src/Ksfraser/Application/http/ResponseHandler.php
src/Ksfraser/Application/interfaces/CommandBusInterface.php
src/Ksfraser/Application/middleware/AdminMiddleware.php
src/Ksfraser/Application/middleware/AuthMiddleware.php
src/Ksfraser/Application/middleware/MiddlewareInterface.php
src/Ksfraser/Application/middleware/MiddlewarePipeline.php
src/Ksfraser/Application/middleware/PerformanceMonitoringMiddleware.php
src/Ksfraser/Application/services/EventDispatcher.php
src/Ksfraser/Application/services/PerformanceMonitoring.php
src/Ksfraser/Application/services/SimpleCommandBus.php
```

---

## STEP 3: Composer Update (CRITICAL)
```bash
# Deploy repo's composer.json + composer.lock, then:
composer install --no-dev
```
This installs `ksfraser/html ^2.0` package which supplies the Composites HTML classes. Without this, all HTML library references fail.

---

## STEP 4: Deploy New Files (MISSING_IN_PROD)

### 4a. StatementReconcile feature (~25 PHP files — the branch's primary feature)
```
reconcile_statement.php                     (new entry point at root)

src/Ksfraser/FaBankImport/StatementReconcile/Application/BankAccountMatchService.php
src/Ksfraser/FaBankImport/StatementReconcile/Application/PendingSessionStoreInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Application/PhpSessionPendingSessionStore.php
src/Ksfraser/FaBankImport/StatementReconcile/Application/ReconcileView.php
src/Ksfraser/FaBankImport/StatementReconcile/Application/ReconciliationCommitService.php
src/Ksfraser/FaBankImport/StatementReconcile/Application/StatementReconcileController.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Entity/ReconciliationSession.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Entity/StatementOcr.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Exception/ReconciliationException.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Exception/StatementOcrException.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Repository/ReconciliationSessionRepositoryInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Repository/StatementOcrRepositoryInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Service/MatchingEngineInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/Service/ReconciliationCommitServiceInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/ValueObject/BankTransactionDto.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/ValueObject/MatchedPair.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/ValueObject/RawOcrResult.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/ValueObject/StatementLine.php
src/Ksfraser/FaBankImport/StatementReconcile/Domain/ValueObject/StatementMetadata.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Database/Migrations/CreateReconciliationSessionTable.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Database/Migrations/CreateStatementOcrTable.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Database/Migrations/CreateStatementUploadTable.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Ocr/OllamaClient.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Ocr/OllamaClientInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Ocr/PdfTextExtractor.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Ocr/PdfTextExtractorInterface.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Ocr/StatementTextParser.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Persistence/PdoReconciliationSessionRepository.php
src/Ksfraser/FaBankImport/StatementReconcile/Infrastructure/Persistence/PdoStatementOcrRepository.php
src/Ksfraser/FaBankImport/StatementReconcile/Matching/SimpleMatchingEngine.php
```

### 4b. New entity / supporting files
```
src/Ksfraser/FaBankImport/Entity/PartnerType.php
src/Ksfraser/FaBankImport/Views/MatchingGLS.php   (capital V — if user cleaned Views/ on server)
src/Ksfraser/HTML/Buttons/ActionButton.php
src/Ksfraser/HTML/Elements/HtmlScript.php
```

### 4c. New views (repo-only, not in PROD yet)
Check these are referenced correctly before deploying:
```
src/Ksfraser/FaBankImport/views/HTML_ROW.php
src/Ksfraser/FaBankImport/views/HTML_ROW_LABELDecorator.php
src/Ksfraser/FaBankImport/views/HtmlTransactionView.php
src/Ksfraser/FaBankImport/views/LabelRowBase.php
src/Ksfraser/FaBankImport/views/MatchingGLS.php     (lowercase views/ version)
src/Ksfraser/FaBankImport/views/module_menu_view.php
```

### 4d. JavaScript / config
```
modules/bank_import/js/date-fallback.js
config/ofx_parser_config.php
ksf_modules_common/class.generic_fa_interface.php
ksf_modules_common/defines.inc.php
```

---

## STEP 5: Database Migrations
```bash
# Run any new migrations:
php artisan migrate  # or equivalent
# StatementReconcile migrations:
#   CreateReconciliationSessionTable
#   CreateStatementOcrTable  
#   CreateStatementUploadTable
# Partner migrations:
#   CreatePartnerTables
#   Migration002AddLastMatchedTs
```

---

## STEP 6: Clean Up PROD-Only Stale Files (NEW_IN_PROD — delete from server)

### 6a. Root-level stale copies (superseded by Services/)
These were old pre-refactoring copies placed at root — canonical versions are in `Services/`:
```bash
rm BankTransferAmountCalculator.php
rm BankTransferFactory.php
rm BankTransferFactoryInterface.php
rm ExchangeRateService.php
rm OperationTypeInterface.php
rm OperationTypesRegistry.php
rm TransactionFilterService.php
rm TransactionUpdater.php
rm TransferDirectionAnalyzer.php
rm PairedTransferProcessor.php
rm SquareTransaction.php
rm ThirdPartyTransaction.php
rm monitor_performance.php      # stale copy; canonical is cron/monitor_performance.php
```

### 6b. Service/ (singular) directory — old duplicate of Services/
```bash
rm -rf src/Ksfraser/FaBankImport/Service/
```
(Contains: `DuplicateDetector.php`, `FileStorageService.php`, `FileStorageServiceInterface.php`, `FileUploadService.php`, `TransactionCounter.php`)

### 6c. Stale Views/ uppercase directory (if it still exists alongside views/)
```bash
# Only if lowercase views/ was already merged into Views/ (user confirmed done)
rm -rf src/Ksfraser/FaBankImport/Views/   # stale uppercase copy with old file set
# Keep: src/Ksfraser/FaBankImport/views/  (lowercase — repo canonical)
```

### 6d. Old flat HTML library files (superseded by Elements/ subdirectory + composer)
After `composer install` works, these flat `src/Ksfraser/HTML/*.php` files are stale:
```bash
# Approximately 80 flat files like HtmlA.php, HtmlBr.php etc. at:
rm src/Ksfraser/HTML/HtmlA.php src/Ksfraser/HTML/HtmlBr.php ...  # (see NEW_IN_PROD list)
# Also old Composites/:
rm -rf src/Ksfraser/HTML/Composites/
rm -rf src/Ksfraser/HTML/HTML_ROW.php src/Ksfraser/HTML/HTML_ROW_LABEL.php ...
```
**Do this AFTER verifying `composer install` provides these via vendor/.**

### 6e. PROD-only dev/temp files (leave or remove as desired)
```
class.bi_lineitem.php.hotfpx     # old hotfix backup — safe to delete
import_statements-old.php        # old backup
import_statements_202510.php     # old backup
changed.txt                      # dev artifact
diffs.txt                        # dev artifact
PROD_changes.diff                # dev artifact
process_statements_preclean.php  # old (now in src/FaBankImport/ in repo)
```

---

## STEP 7: Review PROD-only Functionality (NEW_IN_PROD — bring to repo if needed)

These files exist in PROD but not in repo. Review whether they're actively used:

### 7a. OFX parser classes (root level)
```
class.AbstractQfxParser.php
class.CibcQfxParser.php
class.ManuQfxParser.php
class.PcmcQfxParser.php
class.QfxParserFactory.php
```
Check if anything requires these from root. They predate the `includes/` OFX parsers.

### 7b. services/ (lowercase) directory — 15 service files
PROD has `src/Ksfraser/FaBankImport/services/` (lowercase) that ISN'T in the repo. These include:
```
services/AlertService.php
services/KeywordExtractorService.php
services/KeywordMatchingService.php
services/MetricsAggregator.php
services/PartnerDataService.php
services/PerformanceMonitor.php
services/ReferenceNumberService.php
services/SimpleCommandBus.php
services/TransactionGLValidator.php
services/TransactionLogger.php
services/TransactionService.php
services/TransactionValidator.php
services/TransactionViewService.php
services/UploadedFileManager.php
```
These appear to be older versions of services now in `Services/` (uppercase) or `Application/Partner/`. Verify no production code requires them before deleting.

### 7c. Event/handler files
```
src/Ksfraser/FaBankImport/events/VendorAddedEvent.php
src/Ksfraser/FaBankImport/events/VendorNotAddedEvent.php
src/Ksfraser/FaBankImport/handlers/AddVendor.php
src/Ksfraser/FaBankImport/handlers/AddVendorCommandHandler.php
```
Check if used in production via `import_statements.php` or `process_statements.php`.

### 7d. PairedJEs model
```
src/Ksfraser/FaBankImport/models/PairedJEs.php
```
Dormant (isPaired() returns false in both PROD and repo), but should be committed to repo.

---

## STEP 8: Post-Deployment Verification
```bash
# Syntax check all deployed PHP files:
find . -name "*.php" -not -path "*/vendor/*" | xargs php -l

# Smoke test:
# 1. Load process_statements.php — verify line items render
# 2. Load import_statements.php — verify file upload form
# 3. Check that ksfraser/html package loaded (composer show ksfraser/html)
# 4. Verify Views/ path works for MatchingGLS

# Check error logs after first requests:
tail -f /var/log/apache2/error.log
```

---

## Summary Counts (after filtering noise)

| Category | Total Raw | Meaningful Actionable |
|---|---|---|
| DIFF (update on PROD) | 401 | ~120 PHP files |
| NEW_IN_PROD (PROD-only) | 248 | ~25 to commit + ~110 stale/cleanup |
| MISSING_IN_PROD (deploy to PROD) | 442 | ~35 new PHP files + composer |

**Recommended order:** Step 2 → Step 3 → Step 4 → Step 5 → Step 6 → verify → Step 7
