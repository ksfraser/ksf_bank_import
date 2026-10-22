<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport;

use Ksfraser\FaBankImport\Application;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Controllers\BankImportController;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\RecordingBankImportController;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FaBankImportStubs.php';
require_once __DIR__ . '/Support/FaBankImportTestSupport.php';

final class ApplicationTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    /**
     * @var int
     */
    private $exceptionHandlers = 0;

    /**
     * @var array
     */
    private $sessionBackup = [];

    /**
     * @var array
     */
    private $serverBackup = [];

    /**
     * @var array
     */
    private $postBackup = [];

    protected function setUp(): void
    {
        $this->logPath = FaBankImportTestSupport::createTempDir('ksf_app_');

        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        FaBankImportTestSupport::resetPerformanceMonitor();
        FaBankImportTestSupport::resetContainer();

        $this->sessionBackup = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
        $this->serverBackup = $_SERVER;
        $this->postBackup = $_POST;

        $_SESSION = ['user_id' => 7, 'is_admin' => true];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        while ($this->exceptionHandlers > 0) {
            restore_exception_handler();
            $this->exceptionHandlers--;
        }

        $_SESSION = $this->sessionBackup;
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;

        FaBankImportTestSupport::restoreContainer();
        FaBankImportTestSupport::restorePerformanceMonitor();
        FaBankImportTestSupport::restoreConfig();
        FaBankImportTestSupport::removeDir($this->logPath);
    }

    /**
     * Builds an application whose controller records the action it ran.
     *
     * @param \Throwable|null $failure Failure the controller raises.
     * @return array The application and the recording controller.
     */
    private function buildApplication(?\Throwable $failure = null): array
    {
        $controller = new RecordingBankImportController(Container::getInstance());
        $controller->failure = $failure;

        FaBankImportTestSupport::seedContainerService(BankImportController::class, $controller);

        $application = new Application();
        $this->exceptionHandlers++;

        return [$application, $controller];
    }

    public function testTheConstructorInstallsAnExceptionHandler(): void
    {
        $this->buildApplication();

        $handler = set_exception_handler(static function () {
            return null;
        });
        restore_exception_handler();

        $this->assertIsArray($handler);
        $this->assertInstanceOf(\Ksfraser\FaBankImport\Handlers\ErrorHandler::class, $handler[0]);
        $this->assertSame('handleException', $handler[1]);
    }

    public function testAGetRequestIsRoutedToTheIndexAction(): void
    {
        list($application, $controller) = $this->buildApplication();

        $application->run();

        $this->assertSame('index', $controller->handledAction);
    }

    public function testAPostRequestIsRoutedToTheProcessAction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        list($application, $controller) = $this->buildApplication();

        $application->run();

        $this->assertSame('process', $controller->handledAction);
    }

    public function testTheRequestIsMeasuredByThePerformanceMiddleware(): void
    {
        list($application) = $this->buildApplication();

        $application->run();

        $log = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('list_transactions', $log);
    }

    public function testAFailingControllerIsHandledByTheErrorHandler(): void
    {
        list($application) = $this->buildApplication(new \RuntimeException('Bank import controller failure'));

        ob_start();
        $application->run();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("class='error'", $output);
        $this->assertStringContainsString(
            'Bank import controller failure',
            (string) file_get_contents($this->logPath . '/error.log')
        );
    }

    public function testTheErrorHandlerShowsTheDetailsWhenDebuggingIsEnabled(): void
    {
        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'app.debug' => true,
        ]);

        list($application) = $this->buildApplication(new \RuntimeException('Detailed failure'));

        ob_start();
        $application->run();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Detailed failure', $output);
    }
}
