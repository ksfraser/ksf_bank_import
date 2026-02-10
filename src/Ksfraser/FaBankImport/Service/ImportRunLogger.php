<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

final class ImportRunLogger
{
    /** @var string */
    private $logPath;

    /** @var string */
    private $runId;

    private function __construct(string $logPath, string $runId)
    {
        $this->logPath = $logPath;
        $this->runId = $runId;
    }

    public static function start(string $logDir): self
    {
        if ($logDir === '') {
            throw new \InvalidArgumentException('logDir must not be empty');
        }

        if (!is_dir($logDir)) {
            if (!@mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                throw new \RuntimeException('Failed to create log directory: ' . $logDir);
            }
        }

        $runId = self::generateRunId();
        $fileName = sprintf('import_run_%s.jsonl', $runId);
        $logPath = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        $handle = @fopen($logPath, 'ab');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open log file for writing: ' . $logPath);
        }
        fclose($handle);

        return new self($logPath, $runId);
    }

    public static function resume(string $logPath): self
    {
        if ($logPath === '') {
            throw new \InvalidArgumentException('logPath must not be empty');
        }

        if (!is_file($logPath)) {
            throw new \RuntimeException('Log file does not exist: ' . $logPath);
        }

        $base = basename($logPath);
        $runId = '';
        if (preg_match('/^import_run_(.+)\\.jsonl$/', $base, $m) === 1) {
            $runId = (string)$m[1];
        }
        if ($runId === '') {
            $runId = self::generateRunId();
        }

        return new self($logPath, $runId);
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function event(string $eventName, array $context = []): void
    {
        if ($eventName === '') {
            throw new \InvalidArgumentException('eventName must not be empty');
        }

        $record = [
            'ts' => gmdate('c'),
            'run_id' => $this->runId,
            'event' => $eventName,
            'context' => $context,
        ];

        $json = json_encode($record, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            // Keep logging robust even if context contains weird encodings.
            $record['context'] = ['_logger_error' => 'json_encode_failed'];
            $json = json_encode($record, JSON_UNESCAPED_SLASHES);
        }

        if ($json === false) {
            throw new \RuntimeException('Failed to encode log record to JSON');
        }

        $line = $json . "\n";

        $bytes = @file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
        if ($bytes === false) {
            throw new \RuntimeException('Failed to append to log file: ' . $this->logPath);
        }
    }

    private static function generateRunId(): string
    {
        // Sortable and collision-resistant.
        $timePart = gmdate('Ymd_His');
        $randPart = bin2hex(random_bytes(8));
        return $timePart . '_' . $randPart;
    }
}
