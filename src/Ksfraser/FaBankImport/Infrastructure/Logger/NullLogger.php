<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Logger;

use Ksfraser\FaBankImport\Contracts\Logger;

/**
 * NullLogger - Discards all log messages
 * 
 * Use when logging is disabled or for testing.
 * Implements a no-op (null object pattern).
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class NullLogger implements Logger
{
    public function emergency(string $message, array $context = []): void
    {
        // No-op
    }

    public function alert(string $message, array $context = []): void
    {
        // No-op
    }

    public function critical(string $message, array $context = []): void
    {
        // No-op
    }

    public function error(string $message, array $context = []): void
    {
        // No-op
    }

    public function warning(string $message, array $context = []): void
    {
        // No-op
    }

    public function notice(string $message, array $context = []): void
    {
        // No-op
    }

    public function info(string $message, array $context = []): void
    {
        // No-op
    }

    public function debug(string $message, array $context = []): void
    {
        // No-op
    }

    public function log(string $level, string $message, array $context = []): void
    {
        // No-op
    }
}
