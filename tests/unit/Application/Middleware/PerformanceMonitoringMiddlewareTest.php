<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Middleware\PerformanceMonitoringMiddleware;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';

final class PerformanceMonitoringMiddlewareTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_perf_mw_');
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::resetPerformanceMonitor();
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restorePerformanceMonitor();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restoreConfig();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::removeDir($this->logPath);
    }

    /**
     * @return string
     */
    private function metricsLogFile(): string
    {
        return $this->logPath . '/performance_' . date('Ymd') . '.log';
    }

    public function testARequestWithoutATransactionIsMeasuredAsAListRequest(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();

        $result = $middleware->process(new RequestHandler(), function () {
            return 'handled';
        });

        $this->assertSame('handled', $result);

        $contents = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('list_transactions', $contents);
        $this->assertStringContainsString('request_processing', $contents);
    }

    public function testARequestWithATransactionIsMeasuredAsAProcessRequest(): void
    {
        $request = new RequestHandler(['transaction' => ['id' => 12, 'type' => 'C']]);

        $middleware = new PerformanceMonitoringMiddleware();
        $middleware->process($request, function () {
            return 'handled';
        });

        $contents = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('process_transaction', $contents);
        $this->assertStringContainsString('transaction_id', $contents);
        $this->assertStringContainsString('12', $contents);
        $this->assertStringContainsString('transaction_type', $contents);
    }

    public function testTheExceptionOfTheNextMiddlewareIsRethrown(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $middleware->process(new RequestHandler(), function () {
            throw new \RuntimeException('boom');
        });
    }

    public function testTheMeasurementIsClosedWhenTheNextMiddlewareFails(): void
    {
        $middleware = new PerformanceMonitoringMiddleware();

        try {
            $middleware->process(new RequestHandler(), function () {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $contents = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('list_transactions', $contents);
    }

    public function testBeforeAndAfterDoNothingWhenMonitoringIsDisabled(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'monitoring.enabled' => false,
        ]);

        $middleware = new PerformanceMonitoringMiddleware();
        $middleware->before();
        $middleware->after('list_transactions');

        $this->assertFileDoesNotExist($this->metricsLogFile());
    }

    public function testAfterDoesNothingWhenBeforeWasNotCalled(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'monitoring.enabled' => true,
            'monitoring.sampling_rate' => 1.0,
        ]);

        $middleware = new PerformanceMonitoringMiddleware();
        $middleware->after('list_transactions');

        $this->assertFileDoesNotExist($this->metricsLogFile());
    }

    public function testBeforeAndAfterWriteTheMetricsWhenMonitoringIsEnabled(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'monitoring.enabled' => true,
            'monitoring.sampling_rate' => 1.0,
        ]);

        $middleware = new PerformanceMonitoringMiddleware();
        $middleware->before();
        $middleware->after('list_transactions');

        $this->assertFileExists($this->metricsLogFile());

        $entries = array_filter(explode("\n", (string) file_get_contents($this->metricsLogFile())));
        $this->assertCount(1, $entries);

        $metrics = json_decode((string) reset($entries), true);
        $this->assertSame('list_transactions', $metrics['context']['name']);
        $this->assertArrayHasKey('time_elapsed', $metrics['context']);
        $this->assertArrayHasKey('memory_used', $metrics['context']);
        $this->assertArrayHasKey('peak_memory', $metrics['context']);
        $this->assertArrayHasKey('datetime', $metrics);
    }

    public function testTheSamplingRateCanExcludeTheRequest(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
            'monitoring.enabled' => true,
            'monitoring.sampling_rate' => 0.0,
        ]);

        $middleware = new PerformanceMonitoringMiddleware();
        $middleware->before();
        $middleware->after('list_transactions');

        $this->assertFileDoesNotExist($this->metricsLogFile());
    }

    public function testTheLoggingDirectoryIsCreatedWhenItIsMissing(): void
    {
        $target = $this->logPath . '/nested';
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $target,
        ]);

        new PerformanceMonitoringMiddleware();

        $this->assertDirectoryExists($target);
    }
}
