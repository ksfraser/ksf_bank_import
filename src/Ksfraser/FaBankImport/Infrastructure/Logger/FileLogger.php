<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Logger;

use Ksfraser\FaBankImport\Contracts\Logger;

/**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001

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

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function __construct(private readonly string $filepath)
    {
        $this->ensureDirectoryExists();
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
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

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->filepath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
