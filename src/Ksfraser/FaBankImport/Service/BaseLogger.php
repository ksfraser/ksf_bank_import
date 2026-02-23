<?php

namespace Ksfraser\FaBankImport\Service;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * BaseLogger: Generic, extensible logger for SRP-compliant logging.
 * Supports configurable log file, log level, and context.
 */
use Ksfraser\FaBankImport\Config\Config;

class BaseLogger
{
    /** @var Logger */
    protected $logger;
    /** @var bool */
    protected $debugMode = false;

    /**
     * @param string $name Logger name
     * @param string $logFile Path to log file
     * @param int|null $level Monolog log level (optional, overrides config)
     */
    public function __construct(string $name, string $logFile, ?int $level = null)
    {
        $config = class_exists('Ksfraser\\FaBankImport\\Config\\Config') ? Config::getInstance() : null;
        $this->debugMode = $config ? $config->get('app.debug', false) : false;
        $configLevel = $config ? $config->get('logging.level', Logger::INFO) : Logger::INFO;
        $useLevel = $level !== null ? $level : $configLevel;
        $this->logger = new Logger($name);
        $this->logger->pushHandler(new StreamHandler($logFile, $useLevel));
    }

    /**
     * Log a message with context and level.
     *
     * @param string $message
     * @param array $context
     * @param int $level
     */
    public function log(string $message, array $context = [], int $level = Logger::INFO): void
    {
        $this->logger->log($level, $message, $context);
        if ($this->debugMode && php_sapi_name() === 'cli') {
            echo '[DEBUG] ' . $message . (empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES)) . "\n";
        }
    }

    /**
     * Shortcut for info log.
     */
    public function info(string $message, array $context = []): void
    {
        $this->log($message, $context, Logger::INFO);
    }

    /**
     * Shortcut for warning log.
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log($message, $context, Logger::WARNING);
    }

    /**
     * Shortcut for error log.
     */
    public function error(string $message, array $context = []): void
    {
        $this->log($message, $context, Logger::ERROR);
    }
}
