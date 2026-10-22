<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport;

use Ksfraser\FaBankImport\Bootstrap;
use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;
use Ksfraser\FaBankImport\Services\EventDispatcher;
use Ksfraser\FaBankImport\Services\TransactionLogger;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FaBankImportTestSupport.php';
require_once __DIR__ . '/Support/fa_functions.php';

final class BootstrapTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    /**
     * @var int
     */
    private $errorHandlers = 0;

    protected function setUp(): void
    {
        $this->logPath = FaBankImportTestSupport::createTempDir('ksf_bootstrap_');

        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        FaBankImportTestSupport::resetContainer();

        $GLOBALS['__db_connect_calls'] = [];
        $GLOBALS['__db_connect_exception'] = null;
    }

    protected function tearDown(): void
    {
        while ($this->errorHandlers > 0) {
            restore_error_handler();
            $this->errorHandlers--;
        }

        FaBankImportTestSupport::restoreContainer();
        FaBankImportTestSupport::restoreConfig();
        FaBankImportTestSupport::removeDir($this->logPath);

        $GLOBALS['__db_connect_calls'] = [];
        $GLOBALS['__db_connect_exception'] = null;
    }

    public function testTheBootstrapRegistersAListenerForTheTransactionProcessedEvent(): void
    {
        new Bootstrap();

        $dispatcher = Container::getInstance()->getEventDispatcher();

        $listeners = $this->listenersOf($dispatcher);

        $this->assertArrayHasKey(TransactionProcessedEvent::class, $listeners);
        $this->assertCount(1, $listeners[TransactionProcessedEvent::class]);

        $listener = $listeners[TransactionProcessedEvent::class][0];
        $this->assertIsArray($listener);
        $this->assertInstanceOf(TransactionLogger::class, $listener[0]);
        $this->assertSame('logTransactionProcessed', $listener[1]);
    }

    public function testRunConnectsToTheConfiguredDatabase(): void
    {
        $bootstrap = new Bootstrap();

        $bootstrap->run();
        $this->errorHandlers++;

        $this->assertCount(1, $GLOBALS['__db_connect_calls']);

        $call = $GLOBALS['__db_connect_calls'][0];
        $this->assertSame(getenv('DB_HOST') ?: 'localhost', $call['host']);
        $this->assertSame(getenv('DB_NAME') ?: 'fa_bank_import', $call['dbname']);
        $this->assertSame(getenv('DB_USER') ?: 'root', $call['user']);
        $this->assertSame(getenv('DB_PASS') ?: '', $call['password']);
    }

    public function testRunCreatesTheLoggingDirectory(): void
    {
        $target = $this->logPath . '/created';
        FaBankImportTestSupport::useConfig([
            'logging.path' => $target,
        ]);

        $bootstrap = new Bootstrap();

        $bootstrap->run();
        $this->errorHandlers++;

        $this->assertDirectoryExists($target);
    }

    public function testRunInstallsAnErrorHandlerThatTurnsWarningsIntoExceptions(): void
    {
        $bootstrap = new Bootstrap();

        $bootstrap->run();
        $this->errorHandlers++;

        $this->expectException(\ErrorException::class);

        trigger_error('a warning', E_USER_WARNING);
    }

    public function testAFailingDatabaseConnectionIsReportedAsARuntimeException(): void
    {
        $GLOBALS['__db_connect_exception'] = 'no route to host';

        $bootstrap = new Bootstrap();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed: no route to host');

        try {
            $bootstrap->run();
        } finally {
            $this->errorHandlers++;
        }
    }

    public function testTheBootstrapReadsItsConfigurationFromTheConfigurationSingleton(): void
    {
        $bootstrap = new Bootstrap();

        $property = new \ReflectionProperty(Bootstrap::class, 'config');
        $property->setAccessible(true);

        $this->assertSame(Config::getInstance(), $property->getValue($bootstrap));
    }

    /**
     * The listeners the event dispatcher holds.
     *
     * @param EventDispatcher $dispatcher Dispatcher to inspect.
     * @return array
     */
    private function listenersOf(EventDispatcher $dispatcher): array
    {
        $property = new \ReflectionProperty(EventDispatcher::class, 'listeners');
        $property->setAccessible(true);

        return (array) $property->getValue($dispatcher);
    }
}
