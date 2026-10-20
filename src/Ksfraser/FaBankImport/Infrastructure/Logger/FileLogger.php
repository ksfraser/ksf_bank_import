<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Logger;

use Ksfraser\FaBankImport\Contracts\Logger;

/**
 * @BABOK RTM: FR-006 / ARCH-007; file: Infrastructure/Logger/FileLogger.php; coverage: docs/TestCases/ (file rollup)
 * FileLogger - Logs messages to a file
 * 
 * Writes messages to a specified log file with timestamps and severity levels.
 * Creates file if it doesn't exist, appends to existing file.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class FileLogger implements Logger
{
    private const LOG_FORMAT = '[%s] %s: %s %s' . PHP_EOL;

    /** @BABOK FR-006 / ARCH-007 — method: __construct */
    public function __construct(private readonly string $filepath)
    {
        $this->ensureDirectoryExists();
    }

    /** @BABOK FR-006 / ARCH-007 — method: emergency */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: alert */
    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: critical */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: error */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: warning */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: notice */
    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: info */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: debug */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /** @BABOK FR-006 / ARCH-007 — method: log */
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';

        $line = sprintf(
            self::LOG_FORMAT,
            $timestamp,
            strtoupper($level),
            $message,
            $contextStr
        );

        file_put_contents($this->filepath, $line, FILE_APPEND);
    }

    /** @BABOK FR-006 / ARCH-007 — method: ensureDirectoryExists */
    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->filepath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
