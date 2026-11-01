<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ksfraser\Application\Services\AlertService;
use Ksfraser\Application\Services\MetricsAggregator;
use Ksfraser\Application\Config\Config;

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $config = Config::getInstance();
    
    // Clean up old log files
    $logPath = $config->get('logging.path');
    $retentionDays = $config->get('logging.retention_days', 30);
    $pattern = $config->get('logging.metrics_file_pattern', 'performance_*.log');
    
    foreach (glob($logPath . '/' . $pattern) as $file) {
        if (filemtime($file) < strtotime("-{$retentionDays} days")) {
            unlink($file);
        }
    }

    // Check for and send alerts
    $alertService = AlertService::getInstance();
    $alertService->checkAndSendAlerts();

    // Log successful execution
    error_log('[' . date('Y-m-d H:i:s') . '] Performance monitoring cron completed successfully');
    exit(0);
} catch (Throwable $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] Performance monitoring cron failed: ' . $e->getMessage());
    exit(1);
}
