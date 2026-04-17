<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Contracts;

/**
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
     * Log emergency message (system is unusable)
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Log alert message (action must be taken immediately)
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Log critical message (critical conditions)
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log notice message (normal but significant condition)
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log at specified level
     */
    public function log(string $level, string $message, array $context = []): void;
}
