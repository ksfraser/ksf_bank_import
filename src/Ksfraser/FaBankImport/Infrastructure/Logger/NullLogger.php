<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Logger;

use Ksfraser\FaBankImport\Contracts\Logger;

/**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001

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
    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function emergency(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function alert(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function critical(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function error(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function warning(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function notice(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function info(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function debug(string $message, array $context = []): void
    {
        // No-op
    }

    /** * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
*/
    public function log(string $level, string $message, array $context = []): void
    {
        // No-op
    }
}
