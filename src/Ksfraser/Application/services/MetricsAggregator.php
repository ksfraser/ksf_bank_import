<?php

namespace Ksfraser\Application\Services;

class MetricsAggregator
{
    private const METRICS_FILE_PATTERN = '/performance_*.log';
    private $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
    }

    public function aggregateMetrics(string $startDate, string $endDate): array
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
