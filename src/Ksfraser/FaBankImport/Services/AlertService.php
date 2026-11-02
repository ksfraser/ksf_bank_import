<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Config\Config;

/**
 * @BABOK CLASS ROLLUP: AlertService — 7 methods; BR-001 (1); BR-002 (1); FR-008 (1); BR-012 (2); FR-012 (4); BR-012-003 (2)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/AlertService.php; coverage: docs/TestCases/ (file rollup)
 */
class AlertService
{
    private $config;
    private $aggregator;
    private static $instance = null;

        /** @BABOK ARCH-001 / FR-015 (DI injection) — method: __construct */
    private function __construct()
    {
        $this->config = Config::getInstance();
        $this->aggregator = new MetricsAggregator($this->config->get('logging.path'));
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getInstance */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

        /** @BABOK BR-012 / FR-012 (Alert monitoring) — method: checkAndSendAlerts */
    public function checkAndSendAlerts(): void
    {
        if (!$this->config->get('alerts.enabled', false)) {
            return;
        }

        $metrics = $this->aggregator->aggregateMetrics(
            date('Y-m-d', strtotime('-1 hour')),
            date('Y-m-d H:i:s')
        );

        $anomalies = $this->aggregator->detectPerformanceAnomalies(
            $metrics,
            $this->config->get('monitoring.anomaly_threshold', 2.0)
        );

        if (!empty($anomalies)) {
            $this->sendAnomalyAlert($anomalies);
        }

        // Check for slow transactions
        foreach ($metrics as $name => $metric) {
            if ($metric['avg_time'] * 1000 > $this->config->get('monitoring.slow_transaction_threshold', 1000)) {
                $this->sendSlowTransactionAlert($name, $metric);
            }
            
            if ($metric['avg_memory'] > $this->config->get('monitoring.high_memory_threshold', 10 * 1024 * 1024)) {
                $this->sendHighMemoryAlert($name, $metric);
            }
        }
    }

        /** @BABOK BR-012 / FR-012 — method: sendAnomalyAlert */
    private function sendAnomalyAlert(array $anomalies): void
    {
        $subject = $this->config->get('alerts.email.subject_prefix') . ' Performance Anomalies Detected';
        
        $message = "The following performance anomalies were detected:\n\n";
        foreach ($anomalies as $anomaly) {
            $message .= sprintf(
                "Metric: %s\nDeviation: %.1f%%\nAvg Time: %.2f ms\nMax Time: %.2f ms\n\n",
                $anomaly['metric'],
                $anomaly['deviation'] * 100,
                $anomaly['avg_time'] * 1000,
                $anomaly['max_time'] * 1000
            );
        }

        $this->sendEmail($subject, $message);
    }

        /** @BABOK BR-012-003 / FR-012 — method: sendSlowTransactionAlert */
    private function sendSlowTransactionAlert(string $metricName, array $metric): void
    {
        $subject = $this->config->get('alerts.email.subject_prefix') . ' Slow Transactions Detected';
        
        $message = sprintf(
            "Slow transactions detected for %s:\nAverage Time: %.2f ms\nTransaction Count: %d",
            $metricName,
            $metric['avg_time'] * 1000,
            $metric['count']
        );

        $this->sendEmail($subject, $message);
    }

        /** @BABOK BR-012-003 / FR-012 — method: sendHighMemoryAlert */
    private function sendHighMemoryAlert(string $metricName, array $metric): void
    {
        $subject = $this->config->get('alerts.email.subject_prefix') . ' High Memory Usage Detected';
        
        $message = sprintf(
            "High memory usage detected for %s:\nAverage Memory: %.2f MB\nTransaction Count: %d",
            $metricName,
            $metric['avg_memory'] / (1024 * 1024),
            $metric['count']
        );

        $this->sendEmail($subject, $message);
    }

        /** @BABOK ARCH-007 / FR-012 — method: sendEmail */
    private function sendEmail(string $subject, string $message): void
    {
        $to = $this->config->get('alerts.email.to');
        $from = $this->config->get('alerts.email.from');
        $headers = "From: $from\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($to, $subject, $message, $headers);
    }
}