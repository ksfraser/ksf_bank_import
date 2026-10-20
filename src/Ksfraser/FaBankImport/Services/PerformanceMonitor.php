<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Config\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * @BABOK CLASS ROLLUP: PerformanceMonitor — 6 methods; BR-001 (6); BR-002 (6); FR-008 (6)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/PerformanceMonitor.php; coverage: docs/TestCases/ (file rollup)
 */
class PerformanceMonitor
{
    private $logger;
    private $measurements = [];
    private static $instance = null;

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct */
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

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getInstance */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: startMeasurement */
    public function startMeasurement(string $name): void
    {
        $this->measurements[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage()
        ];
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: endMeasurement */
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

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: recordMetric */
    public function recordMetric(string $name, array $data): void
    {
        $this->logger->info("Metric: {$name}", $data);
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getAverageMetrics */
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