<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Services;

use Ksfraser\Application\Services\PerformanceMonitor;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';

final class PerformanceMonitorTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_perf_');
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

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = PerformanceMonitor::getInstance();
        $second = PerformanceMonitor::getInstance();

        $this->assertSame($first, $second);
    }

    public function testEndMeasurementWithoutAStartThrows(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No measurement started for: missing');

        $monitor->endMeasurement('missing');
    }

    public function testEndMeasurementReturnsTheElapsedTimeAndMemory(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->startMeasurement('process_transaction');
        $metrics = $monitor->endMeasurement('process_transaction');

        $this->assertSame('process_transaction', $metrics['name']);
        $this->assertIsFloat($metrics['time_elapsed']);
        $this->assertGreaterThanOrEqual(0, $metrics['time_elapsed']);
        $this->assertIsInt($metrics['memory_used']);
    }

    public function testEndMeasurementClearsTheMeasurement(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->startMeasurement('process_transaction');
        $monitor->endMeasurement('process_transaction');

        $this->expectException(\RuntimeException::class);

        $monitor->endMeasurement('process_transaction');
    }

    public function testEndMeasurementWritesTheMetricsToTheLogFile(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->startMeasurement('process_transaction');
        $monitor->endMeasurement('process_transaction');

        $logFile = $this->logPath . '/performance.log';
        $this->assertFileExists($logFile);
        $this->assertStringContainsString('Performance metrics', (string) file_get_contents($logFile));
        $this->assertStringContainsString('process_transaction', (string) file_get_contents($logFile));
    }

    public function testRecordMetricWritesTheGivenDataToTheLogFile(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->recordMetric('request_processing', ['request_type' => 'list_transactions']);

        $contents = (string) file_get_contents($this->logPath . '/performance.log');
        $this->assertStringContainsString('Metric: request_processing', $contents);
        $this->assertStringContainsString('list_transactions', $contents);
    }

    public function testGetAverageMetricsReturnsThePlaceholderValues(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $averages = $monitor->getAverageMetrics('process_transaction', 60);

        $this->assertSame(
            ['avg_time' => 0, 'avg_memory' => 0, 'count' => 0],
            $averages
        );
    }
}
