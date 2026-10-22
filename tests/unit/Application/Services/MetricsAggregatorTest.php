<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Services;

use Ksfraser\Application\Services\MetricsAggregator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';

final class MetricsAggregatorTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    /**
     * @var MetricsAggregator
     */
    private $aggregator;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_metrics_');
        $this->aggregator = new MetricsAggregator($this->logPath);
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::removeDir($this->logPath);
    }

    /**
     * Writes a metrics log file into the configured log directory.
     *
     * @param string $name    File name.
     * @param array  $entries Log entries.
     * @return void
     */
    private function writeLog(string $name, array $entries): void
    {
        $content = '';
        foreach ($entries as $entry) {
            $content .= is_string($entry) ? $entry . "\n" : json_encode($entry) . "\n";
        }

        file_put_contents($this->logPath . '/' . $name, $content);
    }

    public function testAggregateMetricsReturnsNothingWhenNoLogExists(): void
    {
        $this->assertSame([], $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21'));
    }

    public function testAggregateMetricsSumsCountsTimesAndMemory(): void
    {
        $this->writeLog('performance_20250520.log', [
            [
                'datetime' => '2025-05-20 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 0.1, 'memory_used' => 1024],
            ],
            [
                'datetime' => '2025-05-20 10:01:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 0.3, 'memory_used' => 2048],
            ],
        ]);

        $metrics = $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21');

        $this->assertArrayHasKey('process_transaction', $metrics);
        $this->assertSame(2, $metrics['process_transaction']['count']);
        $this->assertEqualsWithDelta(0.2, $metrics['process_transaction']['avg_time'], 1e-10);
        $this->assertSame(3072, $metrics['process_transaction']['total_memory']);
        $this->assertEqualsWithDelta(1536, $metrics['process_transaction']['avg_memory'], 1e-10);
        $this->assertEqualsWithDelta(0.3, $metrics['process_transaction']['max_time'], 1e-10);
        $this->assertSame(2048, $metrics['process_transaction']['max_memory']);
    }

    public function testAggregateMetricsIgnoresEntriesOutsideTheDateRange(): void
    {
        $this->writeLog('performance_20250520.log', [
            [
                'datetime' => '2025-05-19 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 1.0, 'memory_used' => 10],
            ],
            [
                'datetime' => '2025-05-20 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 0.5, 'memory_used' => 10],
            ],
            [
                'datetime' => '2025-05-22 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 2.0, 'memory_used' => 10],
            ],
        ]);

        $metrics = $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21');

        $this->assertSame(1, $metrics['process_transaction']['count']);
        $this->assertEqualsWithDelta(0.5, $metrics['process_transaction']['avg_time'], 1e-10);
    }

    public function testAggregateMetricsSkipsMalformedLines(): void
    {
        $this->writeLog('performance_20250520.log', [
            'this is not json',
            '',
            [
                'datetime' => '2025-05-20 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 0.5, 'memory_used' => 10],
            ],
        ]);

        $metrics = $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21');

        $this->assertSame(1, $metrics['process_transaction']['count']);
    }

    public function testAggregateMetricsFallsBackToTheUnknownMetricName(): void
    {
        $this->writeLog('performance_20250520.log', [
            [
                'datetime' => '2025-05-20 10:00:00',
                'context' => ['time_elapsed' => 0.5, 'memory_used' => 10],
            ],
        ]);

        $metrics = $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21');

        $this->assertArrayHasKey('unknown', $metrics);
        $this->assertSame(1, $metrics['unknown']['count']);
    }

    public function testAggregateMetricsIgnoresFilesThatAreNotPerformanceLogs(): void
    {
        $this->writeLog('transactions.log', [
            [
                'datetime' => '2025-05-20 10:00:00',
                'context' => ['name' => 'should_be_ignored', 'time_elapsed' => 0.5, 'memory_used' => 10],
            ],
        ]);

        $this->assertSame([], $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21'));
    }

    public function testAggregateMetricsWorksWithAStreamWrapperPath(): void
    {
        $root = vfsStream::setup('logs');
        vfsStream::newFile('performance_20250520.log')
            ->at($root)
            ->setContent(json_encode([
                'datetime' => '2025-05-20 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 0.5, 'memory_used' => 20],
            ]) . "\n");
        vfsStream::newFile('other.log')->at($root)->setContent("should be ignored\n");

        $aggregator = new MetricsAggregator(vfsStream::url('logs'));
        $metrics = $aggregator->aggregateMetrics('2025-05-20', '2025-05-21');

        $this->assertArrayHasKey('process_transaction', $metrics);
        $this->assertSame(1, $metrics['process_transaction']['count']);
        $this->assertArrayNotHasKey('should_be_ignored', $metrics);
    }

    public function testAggregateMetricsReturnsNothingForAMissingStreamWrapperDirectory(): void
    {
        $aggregator = new MetricsAggregator(vfsStream::url('missing'));

        $this->assertSame([], $aggregator->aggregateMetrics('2025-05-20', '2025-05-21'));
    }

    public function testGetHistoricalTrendsReturnsOneEntryPerDay(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $this->writeLog('performance_today.log', [
            [
                'datetime' => $today . ' 10:00:00',
                'context' => ['name' => 'process_transaction', 'time_elapsed' => 0.5, 'memory_used' => 20],
            ],
        ]);

        $trends = $this->aggregator->getHistoricalTrends('process_transaction', 1);

        $this->assertArrayHasKey($yesterday, $trends);
        $this->assertArrayHasKey($today, $trends);
        $this->assertSame(1, $trends[$today]['count']);
        $this->assertSame(0, $trends[$yesterday]['count']);
        $this->assertSame(0, $trends[$yesterday]['avg_time']);
        $this->assertSame(0, $trends[$yesterday]['avg_memory']);
    }

    public function testGetHistoricalTrendsUsesTheRequestedNumberOfDays(): void
    {
        $trends = $this->aggregator->getHistoricalTrends('process_transaction', 3);

        $this->assertCount(4, $trends);
    }

    public function testDetectPerformanceAnomaliesReportsOutliers(): void
    {
        $anomalies = $this->aggregator->detectPerformanceAnomalies([
            'normal_metric' => ['count' => 10, 'avg_time' => 0.1, 'max_time' => 0.15],
            'anomaly_metric' => ['count' => 10, 'avg_time' => 0.1, 'max_time' => 0.3],
        ], 2.0);

        $this->assertCount(1, $anomalies);
        $this->assertSame('anomaly_metric', $anomalies[0]['metric']);
        $this->assertEqualsWithDelta(0.1, $anomalies[0]['avg_time'], 1e-10);
        $this->assertEqualsWithDelta(0.3, $anomalies[0]['max_time'], 1e-10);
        $this->assertEqualsWithDelta(2.0, $anomalies[0]['deviation'], 1e-10);
    }

    public function testDetectPerformanceAnomaliesIgnoresMetricsWithASingleSample(): void
    {
        $anomalies = $this->aggregator->detectPerformanceAnomalies([
            'single_sample' => ['count' => 1, 'avg_time' => 0.1, 'max_time' => 5.0],
        ], 2.0);

        $this->assertSame([], $anomalies);
    }

    public function testDetectPerformanceAnomaliesUsesTheGivenThreshold(): void
    {
        $metrics = [
            'metric' => ['count' => 4, 'avg_time' => 0.1, 'max_time' => 0.15],
        ];

        $this->assertSame([], $this->aggregator->detectPerformanceAnomalies($metrics, 2.0));
        $this->assertCount(1, $this->aggregator->detectPerformanceAnomalies($metrics, 1.0));
    }
}
