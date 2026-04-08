<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * Import Run Logger - Logs structured import events to JSONL file
 * 
 * Tracks the progress of an import run with timestamped JSON events
 * Supports starting new logs and resuming existing logs
 */
final class ImportRunLogger
{
    /** @var string Unique identifier for this import run */
    private string $runId;
    
    /** @var string Path to the log file */
    private string $logPath;
    
    /**
     * Create new import run logger
     * 
     * @param string $runId Unique run identifier
     * @param string $logPath Path to log file
     */
    private function __construct(string $runId, string $logPath)
    {
        $this->runId = $runId;
        $this->logPath = $logPath;
    }
    
    /**
     * Start a new import run log
     * 
     * Creates log file in specified directory with unique name
     * 
     * @param string $directory Directory to store log file
     * @return self
     */
    public static function start(string $directory): self
    {
        // Ensure directory exists
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }
        
        // Generate unique log filename
        $timestamp = strftime('%Y%m%d_%H%M%S');
        $random = substr(bin2hex(random_bytes(4)), 0, 8);
        $filename = "import_run_{$timestamp}_{$random}.jsonl";
        $logPath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;
        
        // Generate unique run ID
        $runId = bin2hex(random_bytes(16));
        
        // Create empty log file
        @touch($logPath);
        
        return new self($runId, $logPath);
    }
    
    /**
     * Resume logging to existing log file
     * 
     * Reads existing log file to extract run ID
     * 
     * @param string $logPath Path to existing log file
     * @return self
     */
    public static function resume(string $logPath): self
    {
        // Read first line to get run_id
        $lines = file($logPath, FILE_IGNORE_NEW_LINES);
        $runId = null;
        
        if (is_array($lines) && !empty($lines)) {
            $first = json_decode($lines[0], true);
            if (is_array($first) && isset($first['run_id'])) {
                $runId = $first['run_id'];
            }
        }
        
        // If no run_id found, generate a new one
        if (!$runId) {
            $runId = bin2hex(random_bytes(16));
        }
        
        return new self($runId, $logPath);
    }
    
    /**
     * Get path to log file
     * 
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }
    
    /**
     * Log a structured event
     * 
     * @param string $eventName Event identifier
     * @param array $context Event context data
     * @return void
     */
    public function event(string $eventName, array $context = []): void
    {
        $logEntry = [
            'ts' => microtime(true),
            'run_id' => $this->runId,
            'event' => $eventName,
            'context' => $context,
        ];
        
        $line = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->logPath, $line . PHP_EOL, FILE_APPEND);
    }
}
