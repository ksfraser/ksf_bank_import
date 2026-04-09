<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review\Interfaces;

/**
 * Simple logger interface compatible with PSR-3
 * 
 * This is a minimal interface for logging without requiring the psr/log package
 */
interface ILogger
{
    /**
     * Log an informational message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log an error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void;
}
