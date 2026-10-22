<?php

declare(strict_types=1);

/**
 * Scenario: exercises \Ksfraser\Application\Services\AlertService.
 *
 * The alert service sends its notifications with mail(), so the scenario runs
 * with a sendmail_path that captures the messages into a file.
 */

use Ksfraser\Application\Config\Config;
use Ksfraser\Application\Services\AlertService;

$mode = isset($args[0]) ? (string) $args[0] : 'disabled';
$logPath = isset($args[1]) ? (string) $args[1] : sys_get_temp_dir();

$config = Config::getInstance();
$config->set('logging.path', $logPath);
$config->set('alerts.enabled', $mode === 'enabled');
$config->set('alerts.email.subject_prefix', '[ALERT]');
$config->set('alerts.email.to', 'ops@example.com');
$config->set('alerts.email.from', 'bank@example.com');
$config->set('monitoring.anomaly_threshold', 2.0);
$config->set('monitoring.slow_transaction_threshold', 1000);
$config->set('monitoring.high_memory_threshold', 10 * 1024 * 1024);

$service = AlertService::getInstance();
$service->checkAndSendAlerts();

echo "ALERTS_DONE";
