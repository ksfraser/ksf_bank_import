<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application;

use Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ApplicationClasses.php';
require_once __DIR__ . '/Support/ApplicationStubs.php';
require_once __DIR__ . '/Support/ApplicationTestSupport.php';
require_once __DIR__ . '/Support/IsolatedProcess.php';

final class ApplicationTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_app_');
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::removeDir($this->logPath);
    }

    public function testAGetRequestIsRoutedToTheIndexAction(): void
    {
        $process = (new IsolatedProcess())->run('application', ['index', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('APP_COMPLETED', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testAPostRequestIsRoutedToTheProcessAction(): void
    {
        $process = (new IsolatedProcess())->run('application', ['process', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('APP_COMPLETED', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testTheRequestIsMeasuredByThePerformanceMiddleware(): void
    {
        $process = (new IsolatedProcess())->run('application', ['index', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString(
            'list_transactions',
            (string) file_get_contents($this->logPath . '/performance.log')
        );

        $process->mergeCoverage($this);
    }

    public function testAFailingControllerIsHandledByTheErrorHandler(): void
    {
        $process = (new IsolatedProcess())->run('application', ['error', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString("class='error'", $process->getStdout());
        $this->assertStringContainsString(
            'Bank import controller failure',
            (string) file_get_contents($this->logPath . '/error.log')
        );

        $process->mergeCoverage($this);
    }
}
