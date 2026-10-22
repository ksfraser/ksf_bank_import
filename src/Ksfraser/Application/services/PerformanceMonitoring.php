<?php

namespace Ksfraser\Application\Services;

use Ksfraser\Application\Config\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 */
class PerformanceMonitor
{
    private $logger;
    private $measurements = [];
    private static $instance = null;

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    private function __construct()
    {
        $config = Config::getInstance();
        $this->logger = new Logger('performance');
        $this->logger->pushHandler(
            new StreamHandler(
                $config->get('logging.path') . '/performance.log',
                Logger::INFO
            )
        );
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function startMeasurement(string $name): void
    {
        $this->measurements[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage()
        ];
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function endMeasurement(string $name): array
    {
        if (!isset($this->measurements[$name])) {
            throw new \RuntimeException("No measurement started for: {$name}");
        }

        $metrics = [
            'name' => $name,
            'time_elapsed' => microtime(true) - $this->measurements[$name]['start_time'],
            'memory_used' => memory_get_usage() - $this->measurements[$name]['start_memory']
        ];

        $this->logger->info('Performance metrics', $metrics);

        unset($this->measurements[$name]);
        return $metrics;
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function recordMetric(string $name, array $data): void
    {
        $this->logger->info("Metric: {$name}", $data);
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function getAverageMetrics(string $name, int $minutes = 5): array
    {
        // Implementation to calculate average metrics from log file
        // This is a placeholder - real implementation would parse the log file
        return [
            'avg_time' => 0,
            'avg_memory' => 0,
            'count' => 0
        ];
    }
}
