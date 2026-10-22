<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Middleware\PerformanceMonitoringMiddleware;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportTestSupport.php';

final class PerformanceMonitoringMiddlewareTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = FaBankImportTestSupport::createTempDir('ksf_perf_');

        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        FaBankImportTestSupport::resetPerformanceMonitor();
    }

    protected function tearDown(): void
    {
        FaBankImportTestSupport::restorePerformanceMonitor();
        FaBankImportTestSupport::restoreConfig();
        FaBankImportTestSupport::removeDir($this->logPath);
    }

    public function testTheConstructorCreatesTheLogDirectory(): void
    {
        $target = $this->logPath . '/created';
        FaBankImportTestSupport::useConfig(['logging.path' => $target]);
        FaBankImportTestSupport::resetPerformanceMonitor();

        new PerformanceMonitoringMiddleware();

        $this->assertDirectoryExists($target);
    }

    public function testARequestWithoutATransactionCommandIsMeasuredAsAListRequest(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();

        $result = $middleware->process(new RequestHandler(), function () {
            return 'handler-result';
        });

        $this->assertSame('handler-result', $result);

        $log = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('list_transactions', $log);
        $this->assertStringContainsString('request_type', $log);
    }

    public function testARequestWithATransactionCommandIsMeasuredAsAProcessRequest(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();
        $request = new RequestHandler(['transaction' => ['id' => 12, 'type' => 'C']]);

        $result = $middleware->process($request, function () {
            return 'handler-result';
        });

        $this->assertSame('handler-result', $result);

        $log = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('process_transaction', $log);
        $this->assertStringContainsString('transaction_id', $log);
        $this->assertStringContainsString('transaction_type', $log);
    }

    public function testAFailingRequestIsMeasuredAndTheErrorIsRethrown(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();

        try {
            $middleware->process(new RequestHandler(), function () {
                throw new \RuntimeException('handler failure');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('handler failure', $exception->getMessage());
        }

        $log = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('list_transactions', $log);
    }

    public function testBeforeAndAfterDoNothingWhenTheMonitoringIsDisabled(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();

        $middleware->before();
        $middleware->after('some_operation');

        $this->assertFileDoesNotExist($this->logPath . '/performance_' . date('Ymd') . '.log');
    }

    public function testBeforeAndAfterWriteTheSampleWhenTheMonitoringIsEnabled(): void
    {
        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'monitoring.enabled' => true,
            'monitoring.sampling_rate' => 1.0,
        ]);

        $middleware = new PerformanceMonitoringMiddleware();

        $middleware->before();
        $middleware->after('some_operation');

        $file = $this->logPath . '/performance_' . date('Ymd') . '.log';
        $this->assertFileExists($file);

        $metrics = json_decode(trim((string) file_get_contents($file)), true);
        $this->assertSame('some_operation', $metrics['context']['name']);
        $this->assertArrayHasKey('time_elapsed', $metrics['context']);
        $this->assertArrayHasKey('memory_used', $metrics['context']);
        $this->assertArrayHasKey('peak_memory', $metrics['context']);
    }

    public function testAfterDoesNothingWhenBeforeWasNotCalled(): void
    {
        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'monitoring.enabled' => true,
            'monitoring.sampling_rate' => 1.0,
        ]);

        $middleware = new PerformanceMonitoringMiddleware();

        $middleware->after('some_operation');

        $this->assertFileDoesNotExist($this->logPath . '/performance_' . date('Ymd') . '.log');
    }
}
