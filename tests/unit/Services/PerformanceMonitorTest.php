<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\PerformanceMonitor;
use Ksfraser\FaBankImport\Config\Config;

/**
 * Behavioural tests for the performance monitor.
 */
final class PerformanceMonitorTest extends TestCase
{
    /** @var string */
    private static $logDir;

    /** @var mixed */
    private static $originalLogPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$logDir = sys_get_temp_dir() . '/ksf_bank_import_perf_' . uniqid('', true);
        mkdir(self::$logDir, 0777, true);

        $config = Config::getInstance();
        self::$originalLogPath = $config->get('logging.path');
        $config->set('logging.path', self::$logDir);
    }

    public static function tearDownAfterClass(): void
    {
        $config = Config::getInstance();
        $config->set('logging.path', self::$originalLogPath);

        foreach (glob(self::$logDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir(self::$logDir)) {
            rmdir(self::$logDir);
        }

        parent::tearDownAfterClass();
    }

    public function testGetInstanceAlwaysReturnsTheSameMonitor(): void
    {
        $first = PerformanceMonitor::getInstance();
        $second = PerformanceMonitor::getInstance();

        $this->assertInstanceOf(PerformanceMonitor::class, $first);
        $this->assertSame($first, $second);
    }

    public function testEndMeasurementReturnsTheElapsedTimeAndMemory(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->startMeasurement('import');
        $metrics = $monitor->endMeasurement('import');

        $this->assertSame('import', $metrics['name']);
        $this->assertGreaterThanOrEqual(0, $metrics['time_elapsed']);
        $this->assertArrayHasKey('memory_used', $metrics);
    }

    public function testEndMeasurementThrowsWhenNoMeasurementWasStarted(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No measurement started for: never-started');

        $monitor->endMeasurement('never-started');
    }

    public function testEndMeasurementRemovesTheMeasurementSoItCannotBeEndedTwice(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->startMeasurement('single');
        $monitor->endMeasurement('single');

        $this->expectException(\RuntimeException::class);
        $monitor->endMeasurement('single');
    }

    public function testMeasurementsAreWrittenToTheConfiguredLogFile(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $monitor->startMeasurement('logged');
        $monitor->endMeasurement('logged');
        $monitor->recordMetric('custom_metric', ['value' => 42]);

        $this->assertFileExists(self::$logDir . '/performance.log');
        $contents = (string) file_get_contents(self::$logDir . '/performance.log');

        $this->assertStringContainsString('Performance metrics', $contents);
        $this->assertStringContainsString('Metric: custom_metric', $contents);
    }

    public function testGetAverageMetricsReturnsThePlaceholderSummary(): void
    {
        $monitor = PerformanceMonitor::getInstance();

        $this->assertSame(
            [
                'avg_time' => 0,
                'avg_memory' => 0,
                'count' => 0,
            ],
            $monitor->getAverageMetrics('import')
        );
    }
}
