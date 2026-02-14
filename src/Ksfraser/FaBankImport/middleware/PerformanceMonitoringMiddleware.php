<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :PerformanceMonitoringMiddleware [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for PerformanceMonitoringMiddleware.
 */
namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Services\PerformanceMonitor;

class PerformanceMonitoringMiddleware implements MiddlewareInterface
{
    private $config;
    private $monitor;
    private $startTime;
    private $startMemory;
    private $logPath;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->monitor = PerformanceMonitor::getInstance();
        $this->logPath = $this->config->get('logging.path');
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function process(RequestHandler $request, callable $next)
    {
        $command = $request->getTransactionCommand();
        $requestName = $command ? 'process_transaction' : 'list_transactions';

        $this->monitor->startMeasurement($requestName);
        
        try {
            $result = $next($request);
            
            $metrics = $this->monitor->endMeasurement($requestName);
            
            // Add additional context
            $metrics['request_type'] = $requestName;
            if ($command) {
                $metrics['transaction_id'] = $command['id'];
                $metrics['transaction_type'] = $command['type'];
            }
            
            $this->monitor->recordMetric('request_processing', $metrics);
            
            return $result;
        } catch (\Throwable $e) {
            // Still record metrics even if there's an error
            $this->monitor->endMeasurement($requestName);
            throw $e;
        }
    }

    public function before(): void
    {
        if (!$this->shouldSample()) {
            return;
        }

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public function after(string $operationName): void
    {
        if (!$this->shouldSample() || !$this->startTime) {
            return;
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $metrics = [
            'datetime' => date('Y-m-d H:i:s'),
            'context' => [
                'name' => $operationName,
                'time_elapsed' => $endTime - $this->startTime,
                'memory_used' => $endMemory - $this->startMemory,
                'peak_memory' => memory_get_peak_usage()
            ]
        ];

        $this->logMetrics($metrics);
    }

    private function shouldSample(): bool
    {
        if (!$this->config->get('monitoring.enabled', false)) {
            return false;
        }

        $samplingRate = $this->config->get('monitoring.sampling_rate', 1.0);
        return (mt_rand() / mt_getrandmax()) <= $samplingRate;
    }

    private function logMetrics(array $metrics): void
    {
        $filename = $this->logPath . '/performance_' . date('Ymd') . '.log';
        file_put_contents(
            $filename,
            json_encode($metrics) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}