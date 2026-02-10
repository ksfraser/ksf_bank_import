<?php

namespace Ksfraser\Application\Services;

use Ksfraser\Application\Config\Config;

class TransactionLogger
{
    private $logFile;

    public function __construct(?string $logFile = null)
    {
        $config = Config::getInstance();
        $logDir = $config->get('logging.path', __DIR__ . '/../../../../logs');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logFile = $logFile ?? rtrim($logDir, "\\/\\") . DIRECTORY_SEPARATOR . 'transactions.log';
    }

    public function logTransactionProcessed($event): void
    {
        $timestamp = null;
        if (is_object($event) && method_exists($event, 'getTimestamp')) {
            $timestamp = $event->getTimestamp();
        }

        $timestampString = $timestamp instanceof \DateTimeInterface
            ? $timestamp->format('Y-m-d H:i:s')
            : date('Y-m-d H:i:s');

        $transactionId = is_object($event) && method_exists($event, 'getTransactionId')
            ? $event->getTransactionId()
            : null;

        $type = is_object($event) && method_exists($event, 'getType')
            ? $event->getType()
            : null;

        $message = sprintf(
            '[%s] Transaction %s processed as type %s',
            $timestampString,
            $transactionId !== null ? (string) $transactionId : '?',
            $type !== null ? (string) $type : '?'
        );

        file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND);
    }
}
