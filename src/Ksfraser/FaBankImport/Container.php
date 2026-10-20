<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
use Ksfraser\FaBankImport\Services\TransactionViewService;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;
use Ksfraser\FaBankImport\Views\HtmlTransactionView;
use Ksfraser\FaBankImport\Services\SimpleCommandBus;
use Ksfraser\FaBankImport\Services\EventDispatcher;
use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Handlers\ProcessTransactionCommandHandler;
use Ksfraser\FaBankImport\Services\PerformanceMonitor;
use Ksfraser\FaBankImport\Services\MetricsAggregator;
use Ksfraser\FaBankImport\Controllers\AdminController;
use Ksfraser\FaBankImport\Controllers\BankImportController;
use Ksfraser\FaBankImport\Config\Config;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Container.php; coverage: docs/TestCases/ (file rollup)
 */
class Container
{
    private static $instance = null;
    private $services = [];
    private $config;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    private function __construct()
    {
        $this->config = Config::getInstance();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getInstance */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTransactionRepository */
    public function getTransactionRepository(): TransactionRepository
    {
        return $this->services[TransactionRepository::class] 
            ?? $this->services[TransactionRepository::class] = new TransactionRepository();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTransactionTypeFactory */
    public function getTransactionTypeFactory(): TransactionTypeFactory
    {
        return $this->services[TransactionTypeFactory::class] 
            ?? $this->services[TransactionTypeFactory::class] = new TransactionTypeFactory();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTransactionService */
    public function getTransactionService(): TransactionService
    {
        return $this->services[TransactionService::class] 
            ?? $this->services[TransactionService::class] = new TransactionService(
                $this->getTransactionRepository(),
                $this->getTransactionTypeFactory()
            );
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTransactionViewService */
    public function getTransactionViewService(array $transactionData): TransactionViewService
    {
        $factory = $this->getTransactionTypeFactory();
        $transaction = $factory->createTransactionType($transactionData['transactionDC'], $transactionData);
        $view = new HtmlTransactionView($transaction);
        
        return new TransactionViewService($transaction, $view);
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getCommandBus */
    public function getCommandBus(): SimpleCommandBus
    {
        if (!isset($this->services[SimpleCommandBus::class])) {
            $commandBus = new SimpleCommandBus();
            $commandBus->register(
                ProcessTransactionCommand::class,
                $this->getProcessTransactionCommandHandler()
            );
            $this->services[SimpleCommandBus::class] = $commandBus;
        }
        return $this->services[SimpleCommandBus::class];
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getEventDispatcher */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->services[EventDispatcher::class] 
            ?? $this->services[EventDispatcher::class] = new EventDispatcher();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getProcessTransactionCommandHandler */
    public function getProcessTransactionCommandHandler(): ProcessTransactionCommandHandler
    {
        return $this->services[ProcessTransactionCommandHandler::class] 
            ?? $this->services[ProcessTransactionCommandHandler::class] = new ProcessTransactionCommandHandler(
                $this->getTransactionService()
            );
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getPerformanceMonitor */
    public function getPerformanceMonitor(): PerformanceMonitor
    {
        return $this->getSingletonService(PerformanceMonitor::class, function() {
            return PerformanceMonitor::getInstance();
        });
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getMetricsAggregator */
    public function getMetricsAggregator(): MetricsAggregator
    {
        return $this->getSingletonService(MetricsAggregator::class, function() {
            return new MetricsAggregator($this->config->get('logging.path'));
        });
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getAdminController */
    public function getAdminController(): AdminController
    {
        return $this->getSingletonService(AdminController::class, function() {
            return new AdminController();
        });
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getBankImportController */
    public function getBankImportController(): BankImportController
    {
        return $this->getSingletonService(BankImportController::class, function() {
            return new BankImportController();
        });
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getSingletonService */
    private function getSingletonService(string $class, callable $factory)
    {
        if (!isset($this->services[$class])) {
            $this->services[$class] = $factory();
        }
        return $this->services[$class];
    }
}