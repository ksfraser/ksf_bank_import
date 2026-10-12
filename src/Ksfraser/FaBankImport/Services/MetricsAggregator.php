<?php

/** @BABOK See docs/Requirements/ for module coverage tracking 
 * Referenced in RTM for service layer (FR-001 through FR-018) 
 */

namespace Ksfraser\FaBankImport\Services;

class MetricsAggregator
{
    private const METRICS_FILE_PATTERN = '/performance_*.log';
    private $logPath;

    public function __construct(string $logPath)
        /** @BABOK @BABOK ARCH-001 / FR-015 (DI injection) — method: __construct */
    {
        $this->logPath = $logPath;
    }

    public function aggregateMetrics(string $startDate, string $endDate): array
        /** @BABOK @BABOK BR-012-002 / TC-012-A (Metrics aggregation) — method: aggregateMetrics */
    {
        $metrics = [];
        $files = glob($this->logPath . self::METRICS_FILE_PATTERN);

        foreach ($files as $file) {
            $handle = fopen($file, 'r');
            while (($line = fgets($handle)) !== false) {
                $data = json_decode($line, true);
                if (!$data) continue;

                $timestamp = strtotime($data['datetime'] ?? '');
                if ($timestamp >= strtotime($startDate) && $timestamp <= strtotime($endDate)) {
                    $metricName = $data['context']['name'] ?? 'unknown';
                    if (!isset($metrics[$metricName])) {
                        $metrics[$metricName] = [
                            'count' => 0,
                            'total_time' => 0,
                            'total_memory' => 0,
                            'max_time' => 0,
                            'max_memory' => 0
                        ];
                    }

                    $metrics[$metricName]['count']++;
                    $metrics[$metricName]['total_time'] += $data['context']['time_elapsed'] ?? 0;
                    $metrics[$metricName]['total_memory'] += $data['context']['memory_used'] ?? 0;
                    $metrics[$metricName]['max_time'] = max(
                        $metrics[$metricName]['max_time'],
                        $data['context']['time_elapsed'] ?? 0
                    );
                    $metrics[$metricName]['max_memory'] = max(
                        $metrics[$metricName]['max_memory'],
                        $data['context']['memory_used'] ?? 0
                    );
                }
            }
            fclose($handle);
        }

        // Calculate averages
        foreach ($metrics as &$metric) {
            if ($metric['count'] > 0) {
                $metric['avg_time'] = $metric['total_time'] / $metric['count'];
                $metric['avg_memory'] = $metric['total_memory'] / $metric['count'];
            }
        }

        return $metrics;
    }

    public function getHistoricalTrends(string $metricName, int $days = 7): array
        /** @BABOK @BABOK See docs/Requirements/ for module coverage — method: getHistoricalTrends */
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $dailyMetrics = [];
        $currentDate = $startDate;
        
        while ($currentDate <= $endDate) {
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            $metrics = $this->aggregateMetrics($currentDate, $nextDate);
            
            $dailyMetrics[$currentDate] = $metrics[$metricName] ?? [
                'count' => 0,
                'avg_time' => 0,
                'avg_memory' => 0
            ];
            
            $currentDate = $nextDate;
        }

        return $dailyMetrics;
    }

    public function detectPerformanceAnomalies(array $metrics, float $threshold = 2.0): array
        /** @BABOK @BABOK See docs/Requirements/ for module coverage — method: detectPerformanceAnomalies */
    {
        $anomalies = [];
        
        foreach ($metrics as $name => $metric) {
            if ($metric['count'] < 2) continue;

            $avgTime = $metric['avg_time'];
            $maxTime = $metric['max_time'];

            if ($maxTime > $avgTime * $threshold) {
                $anomalies[] = [
                    'metric' => $name,
                    'avg_time' => $avgTime,
                    'max_time' => $maxTime,
                    'deviation' => ($maxTime / $avgTime) - 1
                ];
            }
        }

        return $anomalies;
    }
}