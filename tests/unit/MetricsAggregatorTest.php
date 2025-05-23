<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\MetricsAggregator;
use org\bovigo\vfs\vfsStream;

class MetricsAggregatorTest extends TestCase
{
    private $root;
    private $aggregator;
    private $logPath;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
        $this->logPath = vfsStream::url('root');
        $this->aggregator = new MetricsAggregator($this->logPath);

        // Create sample log files
        $this->createSampleLogFile('performance_20250520.log', [
            ['datetime' => '2025-05-20 10:00:00', 'context' => ['name' => 'test_metric', 'time_elapsed' => 0.1, 'memory_used' => 1024]],
            ['datetime' => '2025-05-20 10:01:00', 'context' => ['name' => 'test_metric', 'time_elapsed' => 0.2, 'memory_used' => 2048]]
        ]);
    }

    private function createSampleLogFile(string $filename, array $entries): void
    {
        $content = '';
        foreach ($entries as $entry) {
            $content .= json_encode($entry) . "\n";
        }
        vfsStream::newFile($filename)->at($this->root)->setContent($content);
    }

    public function testAggregateMetrics(): void
    {
        $metrics = $this->aggregator->aggregateMetrics('2025-05-20', '2025-05-21');

        $this->assertArrayHasKey('test_metric', $metrics);
        $this->assertEquals(2, $metrics['test_metric']['count']);
        $this->assertEquals(0.15, $metrics['test_metric']['avg_time']);
        $this->assertEquals(1536, $metrics['test_metric']['avg_memory']);
        $this->assertEquals(0.2, $metrics['test_metric']['max_time']);
        $this->assertEquals(2048, $metrics['test_metric']['max_memory']);
    }

    public function testGetHistoricalTrends(): void
    {
        $trends = $this->aggregator->getHistoricalTrends('test_metric', 1);

        $this->assertCount(2, $trends);
        $this->assertArrayHasKey(date('Y-m-d'), $trends);
        $this->assertArrayHasKey(date('Y-m-d', strtotime('-1 day')), $trends);
    }

    public function testDetectPerformanceAnomalies(): void
    {
        $metrics = [
            'normal_metric' => [
                'count' => 10,
                'avg_time' => 0.1,
                'max_time' => 0.15
            ],
            'anomaly_metric' => [
                'count' => 10,
                'avg_time' => 0.1,
                'max_time' => 0.3
            ]
        ];

        $anomalies = $this->aggregator->detectPerformanceAnomalies($metrics, 2.0);

        $this->assertCount(1, $anomalies);
        $this->assertEquals('anomaly_metric', $anomalies[0]['metric']);
        $this->assertEquals(2.0, $anomalies[0]['deviation']);
    }
}