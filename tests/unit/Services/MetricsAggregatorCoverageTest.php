<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\MetricsAggregator;
use org\bovigo\vfs\vfsStream;

/**
 * Behavioural tests for the metrics aggregator.
 */
final class MetricsAggregatorCoverageTest extends TestCase
{
    /** @var string */
    private $logDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = sys_get_temp_dir() . '/ksf_bank_import_metrics_' . uniqid('', true);
        mkdir($this->logDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->logDir)) {
            rmdir($this->logDir);
        }

        parent::tearDown();
    }

    public function testAggregateMetricsSummarisesTheLoggedMeasurements(): void
    {
        $this->writeLogFile('performance_20260115.log', [
            ['datetime' => '2026-01-15 10:00:00', 'context' => ['name' => 'import', 'time_elapsed' => 0.5, 'memory_used' => 1024]],
            ['datetime' => '2026-01-15 10:05:00', 'context' => ['name' => 'import', 'time_elapsed' => 1.5, 'memory_used' => 2048]],
            ['datetime' => '2026-01-15 10:06:00', 'context' => ['name' => 'export', 'time_elapsed' => 0.25, 'memory_used' => 512]],
        ]);

        $aggregator = new MetricsAggregator($this->logDir);
        $metrics = $aggregator->aggregateMetrics('2026-01-15 00:00:00', '2026-01-15 23:59:59');

        $this->assertArrayHasKey('import', $metrics);
        $this->assertSame(2, $metrics['import']['count']);
        $this->assertEqualsWithDelta(1.0, $metrics['import']['avg_time'], 1e-10);
        $this->assertSame(3072, $metrics['import']['total_memory']);
        $this->assertEqualsWithDelta(1536.0, $metrics['import']['avg_memory'], 1e-10);
        $this->assertEqualsWithDelta(1.5, $metrics['import']['max_time'], 1e-10);
        $this->assertSame(2048, $metrics['import']['max_memory']);

        $this->assertArrayHasKey('export', $metrics);
        $this->assertSame(1, $metrics['export']['count']);
    }

    public function testAggregateMetricsIgnoresEntriesOutsideTheRequestedWindow(): void
    {
        $this->writeLogFile('performance_20260115.log', [
            ['datetime' => '2026-01-14 10:00:00', 'context' => ['name' => 'import', 'time_elapsed' => 1.0, 'memory_used' => 10]],
            ['datetime' => '2026-01-16 10:00:00', 'context' => ['name' => 'import', 'time_elapsed' => 2.0, 'memory_used' => 10]],
        ]);

        $aggregator = new MetricsAggregator($this->logDir);

        $this->assertSame([], $aggregator->aggregateMetrics('2026-01-15 00:00:00', '2026-01-15 23:59:59'));
    }

    public function testAggregateMetricsSkipsUnreadableLinesAndNamesUnknownMetrics(): void
    {
        $file = $this->logDir . '/performance_20260115.log';
        file_put_contents(
            $file,
            "not json at all\n"
            . json_encode(['datetime' => '2026-01-15 10:00:00', 'context' => ['time_elapsed' => 1.0, 'memory_used' => 8]]) . "\n"
        );

        $aggregator = new MetricsAggregator($this->logDir);
        $metrics = $aggregator->aggregateMetrics('2026-01-15 00:00:00', '2026-01-15 23:59:59');

        $this->assertArrayHasKey('unknown', $metrics);
        $this->assertSame(1, $metrics['unknown']['count']);
    }

    public function testAggregateMetricsReturnsNothingWhenNoLogFilesExist(): void
    {
        $aggregator = new MetricsAggregator($this->logDir);

        $this->assertSame([], $aggregator->aggregateMetrics('2026-01-15 00:00:00', '2026-01-15 23:59:59'));
    }

    public function testMetricsFilesAreAlsoReadFromStreamWrappers(): void
    {
        $root = vfsStream::setup('root');
        vfsStream::newFile('performance_20260115.log')
            ->at($root)
            ->setContent(json_encode([
                'datetime' => '2026-01-15 10:00:00',
                'context' => ['name' => 'import', 'time_elapsed' => 0.5, 'memory_used' => 1024],
            ]) . "\n");
        vfsStream::newFile('other.log')->at($root)->setContent('ignored');

        $aggregator = new MetricsAggregator(vfsStream::url('root'));
        $metrics = $aggregator->aggregateMetrics('2026-01-15 00:00:00', '2026-01-15 23:59:59');

        $this->assertArrayHasKey('import', $metrics);
        $this->assertSame(1, $metrics['import']['count']);
    }

    public function testMissingStreamWrapperDirectoryYieldsNoMetrics(): void
    {
        $root = vfsStream::setup('root');

        $aggregator = new MetricsAggregator(vfsStream::url('root/missing'));
        $metrics = $aggregator->aggregateMetrics('2026-01-15 00:00:00', '2026-01-15 23:59:59');

        $this->assertSame([], $metrics);
    }

    public function testGetHistoricalTrendsReturnsOneEntryPerDay(): void
    {
        $this->writeLogFile('performance_20260115.log', [
            ['datetime' => date('Y-m-d') . ' 10:00:00', 'context' => ['name' => 'import', 'time_elapsed' => 0.5, 'memory_used' => 1024]],
        ]);

        $aggregator = new MetricsAggregator($this->logDir);
        $trends = $aggregator->getHistoricalTrends('import', 2);

        $this->assertCount(3, $trends);
        $this->assertArrayHasKey(date('Y-m-d'), $trends);
        $this->assertSame(1, $trends[date('Y-m-d')]['count']);
        $this->assertArrayHasKey(date('Y-m-d', strtotime('-1 day')), $trends);
        $this->assertSame(0, $trends[date('Y-m-d', strtotime('-1 day'))]['count']);
    }

    public function testDetectPerformanceAnomaliesFlagsMetricsThatDeviateTooMuch(): void
    {
        $aggregator = new MetricsAggregator($this->logDir);

        $anomalies = $aggregator->detectPerformanceAnomalies([
            'steady' => ['count' => 5, 'avg_time' => 1.0, 'max_time' => 1.2],
            'spiky' => ['count' => 5, 'avg_time' => 1.0, 'max_time' => 3.0],
            'single' => ['count' => 1, 'avg_time' => 1.0, 'max_time' => 9.0],
        ], 2.0);

        $this->assertCount(1, $anomalies);
        $this->assertSame('spiky', $anomalies[0]['metric']);
        $this->assertEqualsWithDelta(2.0, $anomalies[0]['deviation'], 1e-10);
    }

    public function testDetectPerformanceAnomaliesReturnsNothingForSteadyMetrics(): void
    {
        $aggregator = new MetricsAggregator($this->logDir);

        $anomalies = $aggregator->detectPerformanceAnomalies([
            'steady' => ['count' => 5, 'avg_time' => 1.0, 'max_time' => 1.2],
        ], 2.0);

        $this->assertSame([], $anomalies);
    }

    /**
     * @param array<int,array<string,mixed>> $entries
     */
    private function writeLogFile(string $filename, array $entries): void
    {
        $content = '';
        foreach ($entries as $entry) {
            $content .= json_encode($entry) . "\n";
        }

        file_put_contents($this->logDir . '/' . $filename, $content);
    }
}
