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

class Container
{
    private static $instance = null;
    private $services = [];
    private $config;

    private function __construct()
    {
        $this->config = Config::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getTransactionRepository(): TransactionRepository
    {
        return $this->services[TransactionRepository::class] 
            ?? $this->services[TransactionRepository::class] = new TransactionRepository();
    }

    public function getTransactionTypeFactory(): TransactionTypeFactory
    {
        return $this->services[TransactionTypeFactory::class] 
            ?? $this->services[TransactionTypeFactory::class] = new TransactionTypeFactory();
    }

    public function getTransactionService(): TransactionService
    {
        return $this->services[TransactionService::class] 
            ?? $this->services[TransactionService::class] = new TransactionService(
                $this->getTransactionRepository(),
                $this->getTransactionTypeFactory()
            );
    }

    public function getTransactionViewService(array $transactionData): TransactionViewService
    {
        $factory = $this->getTransactionTypeFactory();
        $transaction = $factory->createTransactionType($transactionData['transactionDC'], $transactionData);
        $view = new HtmlTransactionView($transaction);
        
        return new TransactionViewService($transaction, $view);
    }

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

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->services[EventDispatcher::class] 
            ?? $this->services[EventDispatcher::class] = new EventDispatcher();
    }

    public function getProcessTransactionCommandHandler(): ProcessTransactionCommandHandler
    {
        return $this->services[ProcessTransactionCommandHandler::class] 
            ?? $this->services[ProcessTransactionCommandHandler::class] = new ProcessTransactionCommandHandler(
                $this->getTransactionService()
            );
    }

    public function getPerformanceMonitor(): PerformanceMonitor
    {
        return $this->getSingletonService(PerformanceMonitor::class, function() {
            return PerformanceMonitor::getInstance();
        });
    }

    public function getMetricsAggregator(): MetricsAggregator
    {
        return $this->getSingletonService(MetricsAggregator::class, function() {
            return new MetricsAggregator($this->config->get('logging.path'));
        });
    }

    public function getAdminController(): AdminController
    {
        return $this->getSingletonService(AdminController::class, function() {
            return new AdminController();
        });
    }

    public function getBankImportController(): BankImportController
    {
        return $this->getSingletonService(BankImportController::class, function() {
            return new BankImportController();
        });
    }

    private function getSingletonService(string $class, callable $factory)
    {
        if (!isset($this->services[$class])) {
            $this->services[$class] = $factory();
        }
        return $this->services[$class];
    }
}