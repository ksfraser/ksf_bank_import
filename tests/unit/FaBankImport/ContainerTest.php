<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Controllers\AdminController;
use Ksfraser\FaBankImport\Controllers\BankImportController;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
use Ksfraser\FaBankImport\Handlers\ProcessTransactionCommandHandler;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;
use Ksfraser\FaBankImport\Services\EventDispatcher;
use Ksfraser\FaBankImport\Services\MetricsAggregator;
use Ksfraser\FaBankImport\Services\PerformanceMonitor;
use Ksfraser\FaBankImport\Services\SimpleCommandBus;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Services\TransactionViewService;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeTransactionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FaBankImportStubs.php';
require_once __DIR__ . '/Support/FaBankImportTestSupport.php';
require_once __DIR__ . '/Support/ManualClassLoads.php';

final class ContainerTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = FaBankImportTestSupport::createTempDir('ksf_container_');

        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        FaBankImportTestSupport::resetPerformanceMonitor();
        FaBankImportTestSupport::resetContainer();
    }

    protected function tearDown(): void
    {
        FaBankImportTestSupport::restoreContainer();
        FaBankImportTestSupport::restorePerformanceMonitor();
        FaBankImportTestSupport::restoreConfig();
        FaBankImportTestSupport::removeDir($this->logPath);
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = Container::getInstance();
        $second = Container::getInstance();

        $this->assertSame($first, $second);
    }

    public function testGetTransactionRepositoryReturnsACachedRepository(): void
    {
        $container = Container::getInstance();

        $repository = $container->getTransactionRepository();

        $this->assertInstanceOf(TransactionRepository::class, $repository);
        $this->assertSame($repository, $container->getTransactionRepository());
    }

    public function testGetTransactionTypeFactoryReturnsACachedFactory(): void
    {
        $container = Container::getInstance();

        $factory = $container->getTransactionTypeFactory();

        $this->assertInstanceOf(TransactionTypeFactory::class, $factory);
        $this->assertSame($factory, $container->getTransactionTypeFactory());
    }

    public function testGetTransactionServiceReturnsACachedService(): void
    {
        $container = Container::getInstance();

        $service = $container->getTransactionService();

        $this->assertInstanceOf(TransactionService::class, $service);
        $this->assertSame($service, $container->getTransactionService());
    }

    public function testGetTransactionViewServiceBuildsTheViewService(): void
    {
        $container = Container::getInstance();

        $viewService = $container->getTransactionViewService(['transactionDC' => 'C', 'id' => 9]);

        $this->assertInstanceOf(TransactionViewService::class, $viewService);
    }

    public function testGetCommandBusReturnsACachedBus(): void
    {
        $container = Container::getInstance();

        $bus = $container->getCommandBus();

        $this->assertInstanceOf(SimpleCommandBus::class, $bus);
        $this->assertSame($bus, $container->getCommandBus());
    }

    public function testGetCommandBusDispatchesTheProcessTransactionCommand(): void
    {
        $container = Container::getInstance();
        FaBankImportTestSupport::seedContainerService(
            TransactionService::class,
            new FakeTransactionService([], true)
        );

        $bus = $container->getCommandBus();

        $event = $bus->dispatch(new ProcessTransactionCommand(3, 'C', 1));

        $this->assertInstanceOf(\Ksfraser\FaBankImport\Events\TransactionProcessedEvent::class, $event);
        $this->assertSame(3, $event->getTransactionId());
    }

    public function testGetEventDispatcherReturnsACachedDispatcher(): void
    {
        $container = Container::getInstance();

        $dispatcher = $container->getEventDispatcher();

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertSame($dispatcher, $container->getEventDispatcher());
    }

    public function testGetProcessTransactionCommandHandlerReturnsACachedHandler(): void
    {
        $container = Container::getInstance();

        $handler = $container->getProcessTransactionCommandHandler();

        $this->assertInstanceOf(ProcessTransactionCommandHandler::class, $handler);
        $this->assertSame($handler, $container->getProcessTransactionCommandHandler());
    }

    public function testGetPerformanceMonitorReturnsTheSharedMonitor(): void
    {
        $container = Container::getInstance();

        $monitor = $container->getPerformanceMonitor();

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
        $this->assertSame($monitor, $container->getPerformanceMonitor());
        $this->assertSame(PerformanceMonitor::getInstance(), $monitor);
    }

    public function testGetMetricsAggregatorReturnsACachedAggregator(): void
    {
        $container = Container::getInstance();

        $aggregator = $container->getMetricsAggregator();

        $this->assertInstanceOf(MetricsAggregator::class, $aggregator);
        $this->assertSame($aggregator, $container->getMetricsAggregator());
    }

    public function testGetAdminControllerReturnsACachedController(): void
    {
        $container = Container::getInstance();

        $controller = $container->getAdminController();

        $this->assertInstanceOf(AdminController::class, $controller);
        $this->assertSame($controller, $container->getAdminController());
    }

    public function testGetBankImportControllerReturnsACachedController(): void
    {
        $container = Container::getInstance();

        $controller = $container->getBankImportController();

        $this->assertInstanceOf(BankImportController::class, $controller);
        $this->assertSame($controller, $container->getBankImportController());
    }

    public function testTheContainerReadsItsConfigurationFromTheConfigurationSingleton(): void
    {
        $container = Container::getInstance();

        $property = new \ReflectionProperty(Container::class, 'config');
        $property->setAccessible(true);

        $this->assertSame(Config::getInstance(), $property->getValue($container));
    }
}
