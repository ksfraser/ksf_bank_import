<?php

declare(strict_types=1);

/**
 * Guard: every class of the module shell has to be reachable through the
 * composer autoloader.
 *
 * This file used to load the classes the autoloader could not resolve on a
 * case sensitive file system (the PSR-4 map pointed CamelCase namespaces at
 * lower case directories). The autoloading defect is fixed now, so the
 * workaround is gone and the classes are loaded the way production loads them.
 *
 * If a directory name and its namespace segment ever drift apart again, the
 * classes below cannot be autoloaded and this file fails loudly.
 */

$root = dirname(__DIR__, 4);

$classes = [
    'Ksfraser\FaBankImport\Application',
    'Ksfraser\FaBankImport\Bootstrap',
    'Ksfraser\FaBankImport\Container',
    'Ksfraser\FaBankImport\Controllers\AbstractController',
    'Ksfraser\FaBankImport\Controllers\AdminController',
    'Ksfraser\FaBankImport\Controllers\BankImportController',
    'Ksfraser\FaBankImport\Controllers\BankImportController_MVC',
    'Ksfraser\FaBankImport\Controller\BankImportController',
    'Ksfraser\FaBankImport\Controller\BiLineItemController',
    'Ksfraser\FaBankImport\Config\Config',
    'Ksfraser\FaBankImport\Config\ConfigService',
    'Ksfraser\FaBankImport\Config\BankImportConfig',
    'Ksfraser\FaBankImport\Database\QueryBuilder',
    'Ksfraser\FaBankImport\Database\DatabaseFactory',
    'Ksfraser\FaBankImport\Database\TransactionQueryBuilder',
    'Ksfraser\FaBankImport\Events\TransactionProcessedEvent',
    'Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException',
    'Ksfraser\FaBankImport\Exceptions\TransactionValidationException',
    'Ksfraser\FaBankImport\Exceptions\UnauthorizedException',
    'Ksfraser\FaBankImport\Factories\TransactionTypeFactory',
    'Ksfraser\FaBankImport\Handlers\AbstractTransactionHandler',
    'Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler',
    'Ksfraser\FaBankImport\Handlers\CustomerTransactionHandler',
    'Ksfraser\FaBankImport\Handlers\ManualSettlementHandler',
    'Ksfraser\FaBankImport\Handlers\MatchedTransactionHandler',
    'Ksfraser\FaBankImport\Handlers\QuickEntryTransactionHandler',
    'Ksfraser\FaBankImport\Handlers\SupplierTransactionHandler',
    'Ksfraser\FaBankImport\Http\Request',
    'Ksfraser\FaBankImport\Http\RequestHandler',
    'Ksfraser\FaBankImport\Http\Response',
    'Ksfraser\FaBankImport\Http\ResponseHandler',
    'Ksfraser\FaBankImport\Interfaces\BankTransactionInterface',
    'Ksfraser\FaBankImport\Interfaces\CommandBusInterface',
    'Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface',
    'Ksfraser\FaBankImport\Interfaces\TransactionViewInterface',
    'Ksfraser\FaBankImport\Middleware\AdminMiddleware',
    'Ksfraser\FaBankImport\Middleware\AuthMiddleware',
    'Ksfraser\FaBankImport\Middleware\MiddlewareInterface',
    'Ksfraser\FaBankImport\Middleware\MiddlewarePipeline',
    'Ksfraser\FaBankImport\Middleware\PerformanceMonitoringMiddleware',
    'Ksfraser\FaBankImport\Middleware\TransactionValidationMiddleware',
    'Ksfraser\FaBankImport\Model\BiLineItemModel',
    'Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface',
    'Ksfraser\FaBankImport\Repository\DatabaseConfigRepository',
    'Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository',
    'Ksfraser\FaBankImport\Repository\DatabaseUploadedFileRepository',
    'Ksfraser\FaBankImport\Repository\ParameterBinder',
    'Ksfraser\FaBankImport\Repositories\AbstractRepository',
    'Ksfraser\FaBankImport\Repositories\TransactionRepository',
    'Ksfraser\FaBankImport\Services\AlertService',
    'Ksfraser\FaBankImport\Services\EventDispatcher',
    'Ksfraser\FaBankImport\Services\KeywordExtractorService',
    'Ksfraser\FaBankImport\Services\KeywordMatchingService',
    'Ksfraser\FaBankImport\Services\MetricsAggregator',
    'Ksfraser\FaBankImport\Services\PartnerDataService',
    'Ksfraser\FaBankImport\Services\PerformanceMonitor',
    'Ksfraser\FaBankImport\Services\ReferenceNumberService',
    'Ksfraser\FaBankImport\Services\SimpleCommandBus',
    'Ksfraser\FaBankImport\Services\TransactionGLValidator',
    'Ksfraser\FaBankImport\Services\TransactionLogger',
    'Ksfraser\FaBankImport\Services\TransactionProcessor',
    'Ksfraser\FaBankImport\Services\TransactionService',
    'Ksfraser\FaBankImport\Services\TransactionValidator',
    'Ksfraser\FaBankImport\Services\TransactionViewService',
    'Ksfraser\FaBankImport\Services\UploadedFileManager',
    'Ksfraser\FaBankImport\View\BiLineItemView',
    'Ksfraser\FaBankImport\Views\AddCustomerButton',
    'Ksfraser\FaBankImport\Views\AddNoButton',
    'Ksfraser\FaBankImport\Views\AddVendorButton',
    'Ksfraser\FaBankImport\Views\AmountCharges',
    'Ksfraser\FaBankImport\Views\Comment',
    'Ksfraser\FaBankImport\Views\CustomerBranch',
    'Ksfraser\FaBankImport\Views\DisplaySettledTransactions',
    'Ksfraser\FaBankImport\Views\FromBankAccount',
    'Ksfraser\FaBankImport\Views\HTML_ROW_LABELDecorator',
    'Ksfraser\FaBankImport\Views\HtmlTransactionView',
    'Ksfraser\FaBankImport\Views\LineitemDisplayLeft',
    'Ksfraser\FaBankImport\Views\LineitemDisplayRight',
    'Ksfraser\FaBankImport\Views\MatchingGLS',
    'Ksfraser\FaBankImport\Views\MatchingGLFactory',
    'Ksfraser\FaBankImport\Views\Operation',
    'Ksfraser\FaBankImport\Views\OperationDeposit',
    'Ksfraser\FaBankImport\Views\OperationPayment',
    'Ksfraser\FaBankImport\Views\OtherBankAccount',
    'Ksfraser\FaBankImport\Views\OurBankAccount',
    'Ksfraser\FaBankImport\Views\PartnerSubSelect',
    'Ksfraser\FaBankImport\Views\PartnerType',
    'Ksfraser\FaBankImport\Views\SettledDefault',
    'Ksfraser\FaBankImport\Views\SettledDepositTable',
    'Ksfraser\FaBankImport\Views\SettledManual',
    'Ksfraser\FaBankImport\Views\SettledPaymentTable',
    'Ksfraser\FaBankImport\Views\SettledSupplier',
    'Ksfraser\FaBankImport\Views\SettledTable',
    'Ksfraser\FaBankImport\Views\StatusSettled',
    'Ksfraser\FaBankImport\Views\ToggleTransactionTypeButton',
    'Ksfraser\FaBankImport\Views\TransactionCustomerDetails',
    'Ksfraser\FaBankImport\Views\TransactionTypeLabel',
    'Ksfraser\FaBankImport\Views\TransDate',
    'Ksfraser\FaBankImport\Views\TransTitle',
    'Ksfraser\FaBankImport\Views\TransType',
    'Ksfraser\FaBankImport\Views\UnsetTransaction',
];

$unreachable = [];
foreach ($classes as $class) {
    if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
        $unreachable[] = $class;
    }
}

if ($unreachable !== []) {
    throw new \RuntimeException(
        "These classes are not autoloadable (directory name and namespace segment "
        . "have drifted apart):\n  " . implode("\n  ", $unreachable)
    );
}
