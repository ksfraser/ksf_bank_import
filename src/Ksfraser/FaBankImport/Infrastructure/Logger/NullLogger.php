<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Logger;

use Ksfraser\FaBankImport\Contracts\Logger;

/**
 * @BABOK RTM: FR-006 / ARCH-007; file: Infrastructure/Logger/NullLogger.php; coverage: docs/TestCases/ (file rollup)
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
    /** @BABOK FR-006 / ARCH-007 — method: emergency */
    public function emergency(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: alert */
    public function alert(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: critical */
    public function critical(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: error */
    public function error(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: warning */
    public function warning(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: notice */
    public function notice(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: info */
    public function info(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: debug */
    public function debug(string $message, array $context = []): void
    {
        // No-op
    }

    /** @BABOK FR-006 / ARCH-007 — method: log */
    public function log(string $level, string $message, array $context = []): void
    {
        // No-op
    }
}
