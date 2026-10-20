<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Contracts;

/**
 * @BABOK RTM: FR-015 / ARCH-001; file: Contracts/Logger.php; coverage: docs/TestCases/ (file rollup)
 * Logger Contract
 * 
 * Defines logging operations for the Partner subsystem.
 * Implementations may log to files, databases, or other systems.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
interface Logger
{
    /**
 * @BABOK FR-015 / ARCH-001 — method: emergency
     * Log emergency message (system is unusable)
     */
    public function emergency(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: alert
     * Log alert message (action must be taken immediately)
     */
    public function alert(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: critical
     * Log critical message (critical conditions)
     */
    public function critical(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: error
     * Log error message
     */
    public function error(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: warning
     * Log warning message
     */
    public function warning(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: notice
     * Log notice message (normal but significant condition)
     */
    public function notice(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: info
     * Log info message
     */
    public function info(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: debug
     * Log debug message
     */
    public function debug(string $message, array $context = []): void;

    /**
 * @BABOK FR-015 / ARCH-001 — method: log
     * Log at specified level
     */
    public function log(string $level, string $message, array $context = []): void;
}
