<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application;

use Ksfraser\Application\Container;
use Ksfraser\Application\Controllers\AdminController;
use Ksfraser\Application\Services\EventDispatcher;
use Ksfraser\Application\Services\MetricsAggregator;
use Ksfraser\Application\Services\PerformanceMonitor;
use Ksfraser\Application\Services\SimpleCommandBus;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ApplicationClasses.php';
require_once __DIR__ . '/Support/ApplicationStubs.php';
require_once __DIR__ . '/Support/ApplicationTestSupport.php';

final class ContainerTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_container_');
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::resetPerformanceMonitor();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::resetContainer();
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restoreContainer();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restorePerformanceMonitor();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restoreConfig();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::removeDir($this->logPath);
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = Container::getInstance();
        $second = Container::getInstance();

        $this->assertSame($first, $second);
    }

    public function testGetEventDispatcherReturnsACachedDispatcher(): void
    {
        $container = Container::getInstance();

        $dispatcher = $container->getEventDispatcher();

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertSame($dispatcher, $container->getEventDispatcher());
    }

    public function testGetMetricsAggregatorReturnsACachedAggregator(): void
    {
        $container = Container::getInstance();

        $aggregator = $container->getMetricsAggregator();

        $this->assertInstanceOf(MetricsAggregator::class, $aggregator);
        $this->assertSame($aggregator, $container->getMetricsAggregator());
    }

    public function testGetPerformanceMonitorReturnsTheSharedMonitor(): void
    {
        $container = Container::getInstance();

        $monitor = $container->getPerformanceMonitor();

        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
        $this->assertSame($monitor, $container->getPerformanceMonitor());
        $this->assertSame(PerformanceMonitor::getInstance(), $monitor);
    }

    public function testGetAdminControllerReturnsACachedController(): void
    {
        $container = Container::getInstance();

        $controller = $container->getAdminController();

        $this->assertInstanceOf(AdminController::class, $controller);
        $this->assertSame($controller, $container->getAdminController());
    }

    public function testGetCommandBusRegistersTheProcessTransactionHandler(): void
    {
        $container = Container::getInstance();

        $bus = $container->getCommandBus();

        $this->assertInstanceOf(SimpleCommandBus::class, $bus);
        $this->assertSame($bus, $container->getCommandBus());

        $command = new \Ksfraser\Application\Commands\ProcessTransactionCommand(3);
        $this->assertStringContainsString('handled', (string) $bus->dispatch($command));
    }

    public function testGetProcessTransactionCommandHandlerReturnsACachedHandler(): void
    {
        $container = Container::getInstance();

        $handler = $container->getProcessTransactionCommandHandler();

        $this->assertInstanceOf(\Ksfraser\Application\Handlers\ProcessTransactionCommandHandler::class, $handler);
        $this->assertSame($handler, $container->getProcessTransactionCommandHandler());
    }

    public function testGetTransactionRepositoryReturnsACachedRepository(): void
    {
        $container = Container::getInstance();

        $repository = $container->getTransactionRepository();

        $this->assertInstanceOf(\Ksfraser\Application\Repositories\TransactionRepository::class, $repository);
        $this->assertSame($repository, $container->getTransactionRepository());
    }

    public function testGetTransactionTypeFactoryReturnsACachedFactory(): void
    {
        $container = Container::getInstance();

        $factory = $container->getTransactionTypeFactory();

        $this->assertInstanceOf(\Ksfraser\Application\Factories\TransactionTypeFactory::class, $factory);
        $this->assertSame($factory, $container->getTransactionTypeFactory());
    }

    public function testGetTransactionServiceReturnsACachedService(): void
    {
        $container = Container::getInstance();

        $service = $container->getTransactionService();

        $this->assertInstanceOf(\Ksfraser\Application\Services\TransactionService::class, $service);
        $this->assertSame($service, $container->getTransactionService());
    }

    public function testGetTransactionViewServiceBuildsTheViewService(): void
    {
        $container = Container::getInstance();

        $viewService = $container->getTransactionViewService(['transactionDC' => 'C', 'id' => 9]);

        $this->assertInstanceOf(\Ksfraser\Application\Services\TransactionViewService::class, $viewService);
    }

    public function testGetBankImportControllerReturnsACachedController(): void
    {
        $container = Container::getInstance();

        $controller = $container->getBankImportController();

        $this->assertInstanceOf(\Ksfraser\Application\Controllers\BankImportController::class, $controller);
        $this->assertSame($controller, $container->getBankImportController());
    }
}
