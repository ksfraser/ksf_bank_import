<?php

namespace Ksfraser\Application;

use Ksfraser\Application\Factories\TransactionTypeFactory;
use Ksfraser\Application\Services\TransactionViewService;
use Ksfraser\Application\Services\TransactionService;
use Ksfraser\Application\Repositories\TransactionRepository;
use Ksfraser\Application\Views\HtmlTransactionView;
use Ksfraser\Application\Services\SimpleCommandBus;
use Ksfraser\Application\Services\EventDispatcher;
use Ksfraser\Application\Commands\ProcessTransactionCommand;
use Ksfraser\Application\Handlers\ProcessTransactionCommandHandler;
use Ksfraser\Application\Services\PerformanceMonitor;
use Ksfraser\Application\Services\MetricsAggregator;
use Ksfraser\Application\Controllers\AdminController;
use Ksfraser\Application\Controllers\BankImportController;
use Ksfraser\Application\Config\Config;

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class Container
{
    private static $instance = null;
    private $services = [];
    private $config;

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function __construct()
    {
        $this->config = Config::getInstance();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionRepository(): TransactionRepository
    {
        return $this->services[TransactionRepository::class] 
            ?? $this->services[TransactionRepository::class] = new TransactionRepository();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionTypeFactory(): TransactionTypeFactory
    {
        return $this->services[TransactionTypeFactory::class] 
            ?? $this->services[TransactionTypeFactory::class] = new TransactionTypeFactory();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionService(): TransactionService
    {
        return $this->services[TransactionService::class] 
            ?? $this->services[TransactionService::class] = new TransactionService(
                $this->getTransactionRepository(),
                $this->getTransactionTypeFactory()
            );
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionViewService(array $transactionData): TransactionViewService
    {
        $factory = $this->getTransactionTypeFactory();
        $transaction = $factory->createTransactionType($transactionData['transactionDC'], $transactionData);
        $view = new HtmlTransactionView($transaction);
        
        return new TransactionViewService($transaction, $view);
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->services[EventDispatcher::class] 
            ?? $this->services[EventDispatcher::class] = new EventDispatcher();
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getProcessTransactionCommandHandler(): ProcessTransactionCommandHandler
    {
        return $this->services[ProcessTransactionCommandHandler::class] 
            ?? $this->services[ProcessTransactionCommandHandler::class] = new ProcessTransactionCommandHandler(
                $this->getTransactionService()
            );
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPerformanceMonitor(): PerformanceMonitor
    {
        return $this->getSingletonService(PerformanceMonitor::class, function() {
            return PerformanceMonitor::getInstance();
        });
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMetricsAggregator(): MetricsAggregator
    {
        return $this->getSingletonService(MetricsAggregator::class, function() {
            return new MetricsAggregator($this->config->get('logging.path'));
        });
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAdminController(): AdminController
    {
        return $this->getSingletonService(AdminController::class, function() {
            return new AdminController();
        });
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getBankImportController(): BankImportController
    {
        return $this->getSingletonService(BankImportController::class, function() {
            return new BankImportController();
        });
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    private function getSingletonService(string $class, callable $factory)
    {
        if (!isset($this->services[$class])) {
            $this->services[$class] = $factory();
        }
        return $this->services[$class];
    }
}
