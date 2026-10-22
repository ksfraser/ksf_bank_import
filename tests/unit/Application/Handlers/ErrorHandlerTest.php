<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Handlers;

use Ksfraser\Application\Handlers\ErrorHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';

final class ErrorHandlerTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_err_');
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restoreConfig();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::removeDir($this->logPath);
    }

    public function testTheGenericErrorMessageIsShownWhenDebugIsOff(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'app.debug' => false,
        ]);

        $handler = new ErrorHandler();

        ob_start();
        $handler->handleException(new \RuntimeException('secret detail'));
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('An error occurred. Please try again or contact support.', $output);
        $this->assertStringNotContainsString('secret detail', $output);
    }

    public function testTheDetailsAreShownWhenDebugIsOn(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'app.debug' => true,
        ]);

        $handler = new ErrorHandler();

        ob_start();
        $handler->handleException(new \RuntimeException('secret detail'));
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("<div class='error'>", $output);
        $this->assertStringContainsString('secret detail', $output);
        $this->assertStringContainsString('<pre>', $output);
    }

    public function testTheExceptionIsWrittenToTheErrorLog(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'app.debug' => false,
        ]);

        $handler = new ErrorHandler();
        $exception = new \RuntimeException('logged failure');

        ob_start();
        $handler->handleException($exception);
        ob_end_clean();

        $logFile = $this->logPath . '/error.log';
        $this->assertFileExists($logFile);

        $contents = (string) file_get_contents($logFile);
        $this->assertStringContainsString('logged failure', $contents);
        $this->assertStringContainsString(__FILE__, $contents);
    }

    public function testAnErrorIsHandledAsWell(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'app.debug' => true,
        ]);

        $handler = new ErrorHandler();

        ob_start();
        $handler->handleException(new \Error('an error'));
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('an error', $output);
    }
}
