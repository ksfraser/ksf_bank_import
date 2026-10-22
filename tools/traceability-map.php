<?php
/**
 * Class/function -> Functional Requirement map.
 *
 * This is the single source for the `@BABOK FR-xxx | BR-xxx | UAT-xxx`
 * docblock tags that make the code cross-referenceable to the Requirements
 * Traceability Matrix in "Project Documents/<feature>/RTM.md".
 *
 * Key   = exact FQCN (or function name) as declared in the code
 * Value = the FR ids the unit implements
 *
 * Method-level exceptions use "FQCN::method" as the key.
 *
 * FR index (FR => [BR, UAT]) is derived from the RTM files by
 * tools/lib/rtm_index.php - the tags are generated from this map plus that
 * index, so the two can never drift apart.
 */

declare(strict_types=1);

return [
    // ==================== Application (generic MVC shell) ====================
    'Ksfraser\Application\Application' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\Application\Bootstrap' => ['FR-LOG-002', 'FR-LOG-003'],
    'Ksfraser\Application\Container' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\Application\Config\Config' => ['FR-LOG-001', 'FR-IMP-007'],
    'Ksfraser\Application\Controllers\AbstractController' => ['FR-PROC-001'],
    'Ksfraser\Application\Controllers\AdminController' => ['FR-PROC-001'],
    'Ksfraser\Application\Database\DatabaseFactory' => ['FR-IMP-007', 'FR-IMP-008'],
    'Ksfraser\Application\Database\QueryBuilder' => ['FR-IMP-007', 'FR-IMP-008'],
    'Ksfraser\Application\Exceptions\UnauthorizedException' => ['FR-LOG-006'],
    'Ksfraser\Application\Handlers\ErrorHandler' => ['FR-LOG-002', 'FR-LOG-005'],
    'Ksfraser\Application\Http\RequestHandler' => ['FR-PROC-002'],
    'Ksfraser\Application\Http\ResponseHandler' => ['FR-PROC-010'],
    'Ksfraser\Application\Interfaces\CommandBusInterface' => ['FR-PROC-002'],
    'Ksfraser\Application\Middleware\AdminMiddleware' => ['FR-LOG-006'],
    'Ksfraser\Application\Middleware\AuthMiddleware' => ['FR-IMP-001', 'FR-PROC-001', 'FR-LOG-006'],
    'Ksfraser\Application\Middleware\MiddlewareInterface' => ['FR-IMP-001', 'FR-PROC-001', 'FR-LOG-006'],
    'Ksfraser\Application\Middleware\MiddlewarePipeline' => ['FR-IMP-001', 'FR-PROC-001', 'FR-LOG-006'],
    'Ksfraser\Application\Middleware\PerformanceMonitoringMiddleware' => ['FR-LOG-002', 'FR-LOG-003'],
    'Ksfraser\Application\Repositories\AbstractRepository' => ['FR-IMP-007', 'FR-IMP-008'],
    'Ksfraser\Application\Services\AlertService' => ['FR-PROC-001'],
    'Ksfraser\Application\Services\EventDispatcher' => ['FR-LOG-003'],
    'Ksfraser\Application\Services\MetricsAggregator' => ['FR-LOG-002'],
    'Ksfraser\Application\Services\PerformanceMonitor' => ['FR-LOG-002'],
    'Ksfraser\Application\Services\SimpleCommandBus' => ['FR-PROC-002'],
    'Ksfraser\Application\Services\TransactionLogger' => ['FR-LOG-003'],

    // ==================== FaBankImport: domain, values, entities ====================
    'Ksfraser\FaBankImport\Domain\Exceptions\InvalidKeywordException' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Domain\Exceptions\PartnerDataNotFoundException' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Domain\ValueObjects\Keyword' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Entity\UploadedFile' => ['FR-IMP-003', 'FR-IMP-009', 'FR-UPL-001', 'FR-UPL-003', 'FR-UPL-004', 'FR-UPL-005'],
    'Ksfraser\FaBankImport\ValueObject\DuplicateResult' => ['FR-IMP-004'],
    'Ksfraser\FaBankImport\ValueObject\FileInfo' => ['FR-IMP-001', 'FR-IMP-003'],
    'Ksfraser\FaBankImport\ValueObject\UploadResult' => ['FR-IMP-004', 'FR-IMP-010'],
    'Ksfraser\FaBankImport\Types\BankTransferTransaction' => ['FR-PROC-007'],
    'Ksfraser\FaBankImport\Types\CreditTransaction' => ['FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007'],
    'Ksfraser\FaBankImport\Types\DebitTransaction' => ['FR-PROC-004', 'FR-PROC-006', 'FR-PROC-007'],
    'Ksfraser\FaBankImport\Results\TransactionResult' => ['FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009', 'FR-PROC-010'],

    // ==================== FaBankImport: duplicate strategies ====================
    'Ksfraser\FaBankImport\Strategy\AllowDuplicateStrategy' => ['FR-IMP-004'],
    'Ksfraser\FaBankImport\Strategy\BlockDuplicateStrategy' => ['FR-IMP-004'],
    'Ksfraser\FaBankImport\Strategy\DuplicateStrategyFactory' => ['FR-IMP-004'],
    'Ksfraser\FaBankImport\Strategy\DuplicateStrategyInterface' => ['FR-IMP-004'],
    'Ksfraser\FaBankImport\Strategy\WarnDuplicateStrategy' => ['FR-IMP-004'],

    // ==================== FaBankImport: services ====================
    'Ksfraser\FaBankImport\Service\BankImportPathResolver' => ['FR-IMP-003', 'FR-LOG-001', 'FR-UPL-004'],
    'Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey' => ['FR-005', 'FR-006'],
    'Ksfraser\FaBankImport\Service\DuplicateDetector' => ['FR-IMP-004'],
    'Ksfraser\FaBankImport\Service\FileStorageService' => ['FR-IMP-003', 'FR-UPL-004', 'FR-UPL-005'],
    'Ksfraser\FaBankImport\Service\FileStorageServiceInterface' => ['FR-IMP-003', 'FR-UPL-004', 'FR-UPL-005'],
    'Ksfraser\FaBankImport\Service\FileUploadService' => ['FR-IMP-001', 'FR-IMP-003', 'FR-IMP-004', 'FR-IMP-009', 'FR-UPL-001', 'FR-UPL-002', 'FR-UPL-004', 'FR-UPL-005'],
    'Ksfraser\FaBankImport\Service\ImportRunLogger' => ['FR-LOG-001', 'FR-LOG-002', 'FR-LOG-003', 'FR-LOG-004'],
    'Ksfraser\FaBankImport\Service\StatementAccountMappingService' => ['FR-001', 'FR-004', 'FR-IMP-006'],

    // ==================== FaBankImport: repositories ====================
    'Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface' => ['FR-005', 'FR-IMP-004', 'FR-LOG-006', 'FR-UPL-004'],
    'Ksfraser\FaBankImport\Repository\DatabaseConfigRepository' => ['FR-005', 'FR-IMP-004', 'FR-LOG-006', 'FR-UPL-004'],
    'Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Repository\DatabaseUploadedFileRepository' => ['FR-IMP-003', 'FR-IMP-009', 'FR-UPL-001', 'FR-UPL-002', 'FR-UPL-003', 'FR-UPL-004', 'FR-UPL-005'],
    'Ksfraser\FaBankImport\Repository\ParameterBinder' => ['FR-PROC-002', 'FR-PROC-001'],
    'Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Repository\UploadedFileRepositoryInterface' => ['FR-IMP-003', 'FR-IMP-009', 'FR-UPL-001', 'FR-UPL-002', 'FR-UPL-003', 'FR-UPL-004', 'FR-UPL-005'],
    'Ksfraser\FaBankImport\Repositories\AbstractRepository' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Repositories\TransactionRepository' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009', 'FR-PROC-011', 'FR-PROC-012', 'FR-VAL-001'],

    // ==================== FaBankImport: commands ====================
    'Ksfraser\FaBankImport\Commands\AddCustomerCommand' => ['FR-PROC-013'],
    'Ksfraser\FaBankImport\Commands\AddVendorCommand' => ['FR-PROC-013'],
    'Ksfraser\FaBankImport\Commands\CommandDispatcher' => ['FR-PROC-011', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\Commands\ProcessTransactionCommand' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Commands\ToggleDebitCreditCommand' => ['FR-PROC-011'],
    'Ksfraser\FaBankImport\Commands\UnsetTransactionCommand' => ['FR-PROC-011'],

    // ==================== FaBankImport: config ====================
    'Ksfraser\FaBankImport\Config\Config' => ['FR-IMP-004', 'FR-LOG-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Config\ConfigService' => ['FR-005', 'FR-IMP-004', 'FR-LOG-006', 'FR-UPL-004'],
    'Ksfraser\FaBankImport\Config\BankImportConfig' => ['FR-PROC-006'],

    // ==================== FaBankImport: database ====================
    'Ksfraser\FaBankImport\Database\DatabaseFactory' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Database\QueryBuilder' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Database\TransactionQueryBuilder' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-003', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009', 'FR-PROC-011', 'FR-PROC-012'],

    // ==================== FaBankImport: http ====================
    'Ksfraser\FaBankImport\Http\Request' => ['FR-IMP-001', 'FR-PROC-011'],
    'Ksfraser\FaBankImport\Http\RequestHandler' => ['FR-IMP-001', 'FR-PROC-011'],
    'Ksfraser\FaBankImport\Http\Response' => ['FR-IMP-010', 'FR-PROC-010'],
    'Ksfraser\FaBankImport\Http\ResponseHandler' => ['FR-IMP-010', 'FR-PROC-010'],

    // ==================== FaBankImport: middleware ====================
    'Ksfraser\FaBankImport\Middleware\AdminMiddleware' => ['FR-LOG-006', 'FR-UPL-004'],
    'Ksfraser\FaBankImport\Middleware\AuthMiddleware' => ['FR-LOG-006', 'FR-UPL-001'],
    'Ksfraser\FaBankImport\Middleware\MiddlewareInterface' => ['FR-IMP-001', 'FR-PROC-001'],
    'Ksfraser\FaBankImport\Middleware\MiddlewarePipeline' => ['FR-IMP-001', 'FR-PROC-001'],
    'Ksfraser\FaBankImport\Middleware\PerformanceMonitoringMiddleware' => ['FR-LOG-002'],
    'Ksfraser\FaBankImport\Middleware\TransactionValidationMiddleware' => ['FR-PROC-002'],

    // ==================== FaBankImport: events, factories, interfaces, contracts ====================
    'Ksfraser\FaBankImport\Events\TransactionProcessedEvent' => ['FR-PROC-002', 'FR-LOG-003'],
    'Ksfraser\FaBankImport\Factories\TransactionTypeFactory' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Interfaces\BankTransactionInterface' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Interfaces\CommandBusInterface' => ['FR-PROC-011', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009', 'FR-PROC-011', 'FR-PROC-012', 'FR-VAL-001'],
    'Ksfraser\FaBankImport\Interfaces\TransactionViewInterface' => ['FR-PROC-001', 'FR-PROC-010'],
    'Ksfraser\FaBankImport\Contracts\CommandDispatcherInterface' => ['FR-PROC-011', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\Contracts\CommandInterface' => ['FR-PROC-011', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\Container\SimpleContainer' => ['FR-PROC-011', 'FR-PROC-013'],

    // ==================== FaBankImport: transaction handlers ====================
    'Ksfraser\FaBankImport\Handlers\AbstractTransactionHandler' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Handlers\SupplierTransactionHandler' => ['FR-PROC-004'],
    'Ksfraser\FaBankImport\Handlers\CustomerTransactionHandler' => ['FR-PROC-005'],
    'Ksfraser\FaBankImport\Handlers\QuickEntryTransactionHandler' => ['FR-PROC-006'],
    'Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler' => ['FR-PROC-007'],
    'Ksfraser\FaBankImport\Handlers\ManualSettlementHandler' => ['FR-PROC-008'],
    'Ksfraser\FaBankImport\Handlers\MatchedTransactionHandler' => ['FR-PROC-009'],
    'Ksfraser\FaBankImport\Handlers\ProcessTransactionCommandHandler' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Handlers\ErrorHandler' => ['FR-PROC-003', 'FR-LOG-005'],

    // ==================== FaBankImport: application shell ====================
    'Ksfraser\FaBankImport\Application' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Bootstrap' => ['FR-LOG-002', 'FR-LOG-003'],
    'Ksfraser\FaBankImport\Container' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Controller\BankImportController' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Controller\BiLineItemController' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Controllers\AbstractController' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Controllers\AdminController' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Controllers\BankImportController' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\FaBankImport\Controllers\BankImportController_MVC' => ['FR-PROC-001', 'FR-PROC-002'],

    // ==================== FaBankImport: transaction types ====================
    'Ksfraser\FaBankImport\CustomerTransaction' => ['FR-PROC-005', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\SupplierTransaction' => ['FR-PROC-004', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\QuickEntryTransaction' => ['FR-PROC-006'],
    'Ksfraser\FaBankImport\BankTransferTransaction' => ['FR-PROC-007'],
    'Ksfraser\FaBankImport\MatchedTransaction' => ['FR-PROC-009'],
    'Ksfraser\FaBankImport\ManualTransaction' => ['FR-PROC-008'],
    'Ksfraser\FaBankImport\SpecialCaseTransaction' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Transaction' => ['FR-PROC-001', 'FR-PROC-003', 'FR-PROC-011', 'FR-PROC-012', 'FR-PROC-013', 'FR-VAL-004'],
    'Ksfraser\FaBankImport\TransactionFactory' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\TransactionFactoryImp' => ['FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\TransactionProcessor' => ['FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\FaGLWrapper' => ['FR-VAL-004', 'FR-PROC-009'],

    // ==================== FaBankImport: models (FA adapters + fakes) ====================
    'Ksfraser\FaBankImport\Models\BankAccountDetailsProviderInterface' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Models\FaBankAccountsProvider' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Models\FaMatchingJEsFinder' => ['FR-VAL-004', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\Models\FakeBankAccountDetailsProvider' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Models\FakeMatchingTransactionsFinder' => ['FR-VAL-004'],
    'Ksfraser\FaBankImport\Models\MatchingTransactionsFinderInterface' => ['FR-VAL-004', 'FR-PROC-009'],

    // ==================== FaBankImport: exceptions ====================
    'Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Exceptions\TransactionValidationException' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Exceptions\UnauthorizedException' => ['FR-LOG-006'],

    'Ksfraser\FaBankImport\Models\BankAccountByNumber' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Models\MatchingJEs' => ['FR-VAL-004', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\Views\HTML_ROW_LABELDecorator' => ['FR-PROC-011', 'FR-PROC-013'],

    // ==================== FaBankImport: services (lowercase "services" tree) ====================
    'Ksfraser\FaBankImport\Services\AlertService' => ['FR-LOG-002', 'FR-PROC-001'],
    'Ksfraser\FaBankImport\Services\EventDispatcher' => ['FR-LOG-003'],
    'Ksfraser\FaBankImport\Services\KeywordExtractorService' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Services\KeywordMatchingService' => ['FR-PROC-002', 'FR-VAL-004'],
    'Ksfraser\FaBankImport\Services\MetricsAggregator' => ['FR-LOG-002'],
    'Ksfraser\FaBankImport\Services\PartnerDataService' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Services\PerformanceMonitor' => ['FR-LOG-002'],
    'Ksfraser\FaBankImport\Services\ReferenceNumberService' => ['FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007'],
    'Ksfraser\FaBankImport\Services\SimpleCommandBus' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Services\TransactionGLValidator' => ['FR-VAL-001', 'FR-VAL-002', 'FR-VAL-003', 'FR-VAL-005'],
    'Ksfraser\FaBankImport\Services\TransactionLogger' => ['FR-LOG-003'],
    'Ksfraser\FaBankImport\Services\TransactionProcessor' => ['FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\Services\TransactionService' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-011'],
    'Ksfraser\FaBankImport\Services\TransactionValidator' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Services\TransactionViewService' => ['FR-PROC-001', 'FR-PROC-010'],
    'Ksfraser\FaBankImport\Services\UploadedFileManager' => ['FR-IMP-003', 'FR-IMP-009', 'FR-UPL-001', 'FR-UPL-002', 'FR-UPL-003', 'FR-UPL-004', 'FR-UPL-005'],

    // ==================== FrontAccounting transaction types ====================
    'Ksfraser\FrontAccounting\TransactionTypes\TransactionTypeInterface' => ['FR-PROC-002'],
    'Ksfraser\FrontAccounting\TransactionTypes\TransactionTypesRegistry' => ['FR-PROC-002'],

    // ==================== Procedural entry points (no class) ====================
    'handleCommandAction' => ['FR-PROC-011', 'FR-PROC-013'],
    'handleLegacyAction' => ['FR-PROC-011', 'FR-PROC-013'],
    'import_statements' => ['FR-IMP-001', 'FR-IMP-002', 'FR-IMP-005', 'FR-IMP-007', 'FR-IMP-008', 'FR-IMP-010'],
    'importStatement' => ['FR-IMP-007', 'FR-IMP-008', 'FR-IMP-010'],
    'do_upload_form' => ['FR-IMP-001', 'FR-IMP-002', 'FR-IMP-003'],
    'parse_uploaded_files' => ['FR-IMP-005', 'FR-IMP-006'],

    // ==================== Global-namespace parser classes (legacy copies) ====================
    'AbstractQfxParser' => ['FR-IMP-005', 'FR-001'],
    'CibcQfxParser' => ['FR-IMP-005'],
    'PmcQfxParser' => ['FR-IMP-005'],
    'ManuQfxParser' => ['FR-IMP-005'],
    'QfxParserFactory' => ['FR-IMP-002', 'FR-IMP-005'],
    'ksf_modules_table_filter_by_date' => ['FR-PROC-001', 'FR-INQ-001'],

    // ==================== FaBankImport: views ====================
    'Ksfraser\FaBankImport\Views\AddCustomerButton' => ['FR-PROC-013'],
    'Ksfraser\FaBankImport\Views\AddNoButton' => ['FR-PROC-013'],
    'Ksfraser\FaBankImport\Views\AddVendorButton' => ['FR-PROC-013'],
    'Ksfraser\FaBankImport\Views\AmountCharges' => ['FR-PROC-001', 'FR-PROC-006'],
    'Ksfraser\FaBankImport\Views\Comment' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\DisplaySettledTransactions' => ['FR-PROC-001', 'FR-PROC-010', 'FR-PROC-011'],
    'Ksfraser\FaBankImport\Views\StatusSettled' => ['FR-PROC-001', 'FR-PROC-010'],
    'Ksfraser\FaBankImport\Views\UnsetTransaction' => ['FR-PROC-011'],
    'Ksfraser\FaBankImport\Views\SettledManual' => ['FR-PROC-008'],
    'Ksfraser\FaBankImport\Views\SettledDefault' => ['FR-PROC-010'],
    'Ksfraser\FaBankImport\Views\SettledTable' => ['FR-PROC-010'],
    'Ksfraser\FaBankImport\Views\SettledPaymentTable' => ['FR-PROC-004'],
    'Ksfraser\FaBankImport\Views\OperationPayment' => ['FR-PROC-004'],
    'Ksfraser\FaBankImport\Views\FromBankAccount' => ['FR-PROC-004'],
    'Ksfraser\FaBankImport\Views\SettledSupplier' => ['FR-PROC-004'],
    'Ksfraser\FaBankImport\Views\SettledDepositTable' => ['FR-PROC-005'],
    'Ksfraser\FaBankImport\Views\OperationDeposit' => ['FR-PROC-005'],
    'Ksfraser\FaBankImport\Views\CustomerBranch' => ['FR-PROC-005'],
    'Ksfraser\FaBankImport\Views\LineitemDisplayLeft' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\LineitemDisplayRight' => ['FR-PROC-001', 'FR-PROC-002', 'FR-VAL-004'],
    'Ksfraser\FaBankImport\Views\MatchingGLS' => ['FR-VAL-004', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\Views\MatchingGLFactory' => ['FR-VAL-004'],
    'Ksfraser\FaBankImport\Views\Operation' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\OtherBankAccount' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\OurBankAccount' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Views\PartnerSubSelect' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Views\PartnerType' => ['FR-PROC-002'],
    'Ksfraser\FaBankImport\Views\ToggleTransactionTypeButton' => ['FR-PROC-011'],
    'Ksfraser\FaBankImport\Views\TransDate' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\TransTitle' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\TransType' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Views\TransactionCustomerDetails' => ['FR-VAL-004', 'FR-PROC-010'],
    'Ksfraser\FaBankImport\Views\TransactionTypeLabel' => ['FR-PROC-001', 'FR-PROC-011'],
    'Ksfraser\FaBankImport\Views\HtmlTransactionView' => ['FR-PROC-001'],
    'HTML_ROW' => ['FR-PROC-001'],
    'HTML_ROW_LABELDecorator' => ['FR-PROC-011', 'FR-PROC-013'],
    'Ksfraser\FaBankImport\Views\ModuleMenuView' => ['FR-PROC-001', 'FR-IMP-001', 'FR-INQ-002', 'FR-UPL-001', 'FR-VAL-001'],

    // ==================== Shared views ====================
    'Ksfraser\Views\CommentSubmitView' => ['FR-PROC-001'],
    'Ksfraser\Views\PartnerTypeSelectorView' => ['FR-PROC-002'],

    // ==================== Models ====================
    'Ksfraser\FaBankImport\Model\BiLineItemModel' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-003', 'FR-PROC-009', 'FR-PROC-012'],
    'Ksfraser\FaBankImport\Models\BiLineItemModel' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-003', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\Models\AbstractTransaction' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\models\BankAccountByNumber' => ['FR-PROC-003'],
    'Ksfraser\FaBankImport\Models\BankTransaction' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\Models\BankTransactionInterface' => ['FR-PROC-001'],
    'Ksfraser\FaBankImport\models\MatchingJEs' => ['FR-VAL-004', 'FR-PROC-009'],
    'Ksfraser\FaBankImport\Models\SquareTransaction' => ['FR-PROC-011', 'FR-PROC-013'],

    // ==================== Partner types ====================
    'Ksfraser\PartnerTypes\PartnerTypeInterface' => ['FR-PROC-002'],
    'Ksfraser\PartnerTypes\AbstractPartnerType' => ['FR-PROC-002'],
    'Ksfraser\PartnerTypes\PartnerTypeRegistry' => ['FR-PROC-002'],
    'Ksfraser\PartnerTypes\SupplierPartnerType' => ['FR-PROC-002', 'FR-PROC-004'],
    'Ksfraser\PartnerTypes\CustomerPartnerType' => ['FR-PROC-002', 'FR-PROC-005'],
    'Ksfraser\PartnerTypes\QuickEntryPartnerType' => ['FR-PROC-002', 'FR-PROC-006'],
    'Ksfraser\PartnerTypes\BankTransferPartnerType' => ['FR-PROC-002', 'FR-PROC-007'],
    'Ksfraser\PartnerTypes\ManualSettlementPartnerType' => ['FR-PROC-002', 'FR-PROC-008'],
    'Ksfraser\PartnerTypes\MatchedPartnerType' => ['FR-PROC-002', 'FR-PROC-009'],
    'Ksfraser\PartnerTypes\UnknownPartnerType' => ['FR-PROC-002'],

    // ==================== Module-level data providers and helpers ====================
    'Ksfraser\BankAccountDataProvider' => ['FR-PROC-007'],
    'Ksfraser\CustomerDataProvider' => ['FR-PROC-005'],
    'Ksfraser\SupplierDataProvider' => ['FR-PROC-004'],
    'Ksfraser\QuickEntryDataProvider' => ['FR-PROC-006'],
    'Ksfraser\FormFieldNameGenerator' => ['FR-PROC-002'],
    'Ksfraser\MatchingTransactionsList' => ['FR-VAL-004', 'FR-PROC-009', 'FR-PROC-010'],
    'Ksfraser\PartnerFormData' => ['FR-PROC-002'],
    'Ksfraser\PartnerFormFactory' => ['FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009'],
    'Ksfraser\PartnerSelectionPanel' => ['FR-PROC-002'],
    'Ksfraser\PartnerTypeConstants' => ['FR-PROC-002'],
    'Ksfraser\SettledTransactionDisplay' => ['FR-PROC-004', 'FR-PROC-005', 'FR-PROC-010', 'FR-PROC-011'],
    'Ksfraser\UrlBuilder' => ['FR-PROC-010'],
    'Ksfraser\FaBankImport\View\BiLineItemView' => ['FR-PROC-001', 'FR-PROC-002', 'FR-PROC-004', 'FR-PROC-005', 'FR-PROC-006', 'FR-PROC-007', 'FR-PROC-008', 'FR-PROC-009', 'FR-PROC-011', 'FR-PROC-012', 'FR-PROC-013', 'FR-VAL-004'],

    // ==================== HTML library: composites ====================
    'Ksfraser\HTML\Composites\HTML_ROW' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Composites\HTML_ROW_LABEL' => ['FR-PROC-002', 'FR-IMP-002'],
    'Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator' => ['FR-PROC-002', 'FR-IMP-002'],
    'Ksfraser\HTML\Composites\LabelRowBase' => ['FR-PROC-002', 'FR-PROC-009'],
    'Ksfraser\HTML\Composites\HTML_TABLE' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Composites\HtmlLabelRow' => ['FR-PROC-002', 'FR-IMP-002'],
    'Ksfraser\HTML\Elements\HtmlLabelRow' => ['FR-PROC-002', 'FR-IMP-002'],

    // ==================== HTML library: links ====================
    'Ksfraser\HTML\Elements\HtmlA' => ['FR-PROC-010', 'FR-VAL-004'],
    'Ksfraser\HTML\Elements\HtmlLink' => ['FR-PROC-010', 'FR-VAL-004'],
    'Ksfraser\HTML\Elements\HtmlEmail' => ['FR-PROC-013'],

    // ==================== HTML library: inline formatting ====================
    'Ksfraser\HTML\Elements\HtmlFormatting' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlBold' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlB' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlStrong' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlI' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlItalic' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlEm' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlEmphasize' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlSmall' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlMark' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlSub' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlSubscript' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlSup' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlSuperscript' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlIns' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlInserted' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlDel' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlDeleted' => ['FR-IMP-010', 'FR-PROC-009'],

    // ==================== HTML library: document scaffold ====================
    'Ksfraser\HTML\Elements\HtmlHtml' => ['FR-IMP-001', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHead' => ['FR-IMP-001', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlBody' => ['FR-IMP-001', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlTitle' => ['FR-IMP-001', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlComment' => ['FR-IMP-001'],
    'Ksfraser\HTML\Elements\HtmlBr' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlHr' => ['FR-IMP-010'],

    // ==================== HTML library: headings ====================
    'Ksfraser\HTML\Elements\HtmlHeading' => ['FR-IMP-010', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHeading1' => ['FR-IMP-010', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHeading2' => ['FR-IMP-010', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHeading3' => ['FR-IMP-010', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHeading4' => ['FR-IMP-010', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHeading5' => ['FR-IMP-010', 'FR-UPL-001'],
    'Ksfraser\HTML\Elements\HtmlHeading6' => ['FR-IMP-010', 'FR-UPL-001'],

    // ==================== HTML library: text blocks ====================
    'Ksfraser\HTML\Elements\HtmlParagraph' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlP' => ['FR-IMP-010', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlPre' => ['FR-LOG-007'],
    'Ksfraser\HTML\Elements\HtmlPreformatted' => ['FR-LOG-007'],
    'Ksfraser\HTML\Elements\HtmlSpan' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlDiv' => ['FR-PROC-001'],
    'Ksfraser\HTML\Elements\HtmlString' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlRaw' => ['FR-PROC-013', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlOB' => ['FR-PROC-002'],

    // ==================== HTML library: lists ====================
    'Ksfraser\HTML\Elements\HtmlUnorderedList' => ['FR-VAL-004', 'FR-IMP-001'],
    'Ksfraser\HTML\Elements\HtmlUl' => ['FR-VAL-004', 'FR-IMP-001'],
    'Ksfraser\HTML\Elements\HtmlListItem' => ['FR-VAL-004', 'FR-IMP-001'],
    'Ksfraser\HTML\Elements\HtmlLi' => ['FR-VAL-004', 'FR-IMP-001'],
    'Ksfraser\HTML\Elements\HtmlOrderedList' => ['FR-VAL-004', 'FR-IMP-001'],
    'Ksfraser\HTML\Elements\HtmlOl' => ['FR-VAL-004', 'FR-IMP-001'],

    // ==================== HTML library: definition lists ====================
    'Ksfraser\HTML\Elements\HtmlDescriptionList' => ['FR-INQ-003', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlDescriptionDescription' => ['FR-INQ-003', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlDescriptionTerm' => ['FR-INQ-003', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlDl' => ['FR-INQ-003', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlDt' => ['FR-INQ-003', 'FR-PROC-002'],
    'Ksfraser\HTML\HTMLAtomic\HtmlDd' => ['FR-INQ-003', 'FR-PROC-002'],

    // ==================== HTML library: images ====================
    'Ksfraser\HTML\Elements\HtmlImage' => ['FR-VAL-005', 'FR-PROC-009'],
    'Ksfraser\HTML\Elements\HtmlImg' => ['FR-VAL-005', 'FR-PROC-009'],

    // ==================== HTML library: tables ====================
    'Ksfraser\HTML\Elements\HtmlTable' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableBody' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableHead' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableFoot' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableCaption' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableCol' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableColGroup' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableRow' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableRowCell' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTableHeaderCell' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTd' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],
    'Ksfraser\HTML\Elements\HtmlTh' => ['FR-PROC-001', 'FR-UPL-001', 'FR-INQ-003'],

    // ==================== HTML library: forms ====================
    'Ksfraser\HTML\Elements\HtmlForm' => ['FR-IMP-001', 'FR-PROC-002'],

    // ==================== HTML library: form inputs ====================
    'Ksfraser\HTML\Elements\Form\Input\HtmlInput' => ['FR-IMP-001', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\Form\Input\HtmlTextInput' => ['FR-IMP-002', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\Form\Input\HtmlCheckboxInput' => ['FR-IMP-004', 'FR-005'],
    'Ksfraser\HTML\Elements\Form\Input\HtmlRadioInput' => ['FR-IMP-002', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\Form\Input\HtmlHidden' => ['FR-PROC-002', 'FR-PROC-012'],
    'Ksfraser\HTML\Elements\Form\Input\HtmlOption' => ['FR-IMP-002', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\Form\Input\HtmlSelect' => ['FR-IMP-002', 'FR-PROC-002'],

    // ==================== HTML library: buttons ====================
    'Ksfraser\HTML\Elements\Buttons\HtmlButton' => ['FR-PROC-011', 'FR-PROC-013'],
    'Ksfraser\HTML\Elements\Buttons\HtmlInputButton' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\Buttons\HtmlInputGenericButton' => ['FR-PROC-011'],
    'Ksfraser\HTML\Elements\Buttons\HtmlInputReset' => ['FR-IMP-001', 'FR-IMP-002'],
    'Ksfraser\HTML\Elements\Buttons\HtmlSubmit' => ['FR-PROC-002', 'FR-PROC-011'],

    // ==================== HTML library: styles ====================
    'Ksfraser\HTML\Elements\HtmlStyle' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlStyleList' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\Elements\HtmlInternalCSS' => ['FR-PROC-001'],
    'Ksfraser\HTML\Elements\HtmlInternalCSSList' => ['FR-PROC-001'],
    'Ksfraser\HTML\Elements\HtmlExternalCSS' => ['FR-PROC-001'],

    // ==================== HTML library: core ====================
    'Ksfraser\HTML\HtmlElementInterface' => ['FR-IMP-001', 'FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\HtmlElement' => ['FR-IMP-001', 'FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\HtmlEmptyElement' => ['FR-IMP-001', 'FR-PROC-002'],
    'Ksfraser\HTML\HtmlAttribute' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\HtmlAttributeList' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\HTMLChildrenTrait' => ['FR-PROC-001', 'FR-PROC-002'],
    'Ksfraser\HTML\HtmlFragment' => ['FR-PROC-002', 'FR-PROC-007'],
    'Ksfraser\HTML\FaUiFunctions' => ['FR-IMP-001', 'FR-PROC-001'],
    'Ksfraser\HTML\LabelRowBase' => ['FR-PROC-001'],
    'Mouf\Html\Tags\AttributesTrait' => ['FR-PROC-001', 'FR-PROC-002'],
];
