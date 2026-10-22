<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application;

use Ksfraser\Application\Bootstrap;
use Ksfraser\Application\Container;
use Ksfraser\Application\Events\TransactionProcessedEvent;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ApplicationClasses.php';
require_once __DIR__ . '/Support/ApplicationStubs.php';
require_once __DIR__ . '/Support/ApplicationTestSupport.php';
require_once __DIR__ . '/Support/fa_functions.php';

final class BootstrapTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = ApplicationTestSupport::createTempDir('ksf_bootstrap_');
        ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        ApplicationTestSupport::resetContainer();

        $GLOBALS['__db_connect_calls'] = [];
        $GLOBALS['__db_connect_exception'] = null;
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        ApplicationTestSupport::restoreContainer();
        ApplicationTestSupport::restoreConfig();
        ApplicationTestSupport::removeDir($this->logPath);

        $GLOBALS['__db_connect_calls'] = [];
        $GLOBALS['__db_connect_exception'] = null;
    }

    public function testTheBootstrapRegistersAListenerForTheTransactionProcessedEvent(): void
    {
        new Bootstrap();

        $dispatcher = Container::getInstance()->getEventDispatcher();
        $dispatcher->dispatch(new TransactionProcessedEvent(11, 'credit'));

        $this->assertFileExists($this->logPath . '/transactions.log');
        $this->assertStringContainsString(
            'Transaction 11 processed as type credit',
            (string) file_get_contents($this->logPath . '/transactions.log')
        );
    }

    public function testRunConnectsToTheConfiguredDatabase(): void
    {
        $bootstrap = new Bootstrap();

        $bootstrap->run();

        $this->assertCount(1, $GLOBALS['__db_connect_calls']);

        $call = $GLOBALS['__db_connect_calls'][0];
        $this->assertSame(getenv('DB_HOST') ?: 'localhost', $call['host']);
        $this->assertSame(getenv('DB_NAME') ?: 'test_db', $call['dbname']);
        $this->assertSame(getenv('DB_USER') ?: 'test_user', $call['user']);
        $this->assertSame(getenv('DB_PASS') ?: 'test_pass', $call['password']);
    }

    public function testRunCreatesTheLoggingDirectory(): void
    {
        $target = $this->logPath . '/created';
        ApplicationTestSupport::useConfig([
            'logging.path' => $target,
        ]);

        $bootstrap = new Bootstrap();

        // The transaction logger of the bootstrap already created the
        // directory, remove it so that the bootstrap has to create it again.
        ApplicationTestSupport::removeDir($target);

        $bootstrap->run();

        $this->assertDirectoryExists($target);
    }

    public function testRunInstallsAnErrorHandlerThatTurnsWarningsIntoExceptions(): void
    {
        $bootstrap = new Bootstrap();
        $bootstrap->run();

        $this->expectException(\ErrorException::class);

        trigger_error('a warning', E_USER_WARNING);
    }

    public function testAFailingDatabaseConnectionIsReportedAsARuntimeException(): void
    {
        $GLOBALS['__db_connect_exception'] = 'no route to host';

        $bootstrap = new Bootstrap();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed: no route to host');

        $bootstrap->run();
    }
}
