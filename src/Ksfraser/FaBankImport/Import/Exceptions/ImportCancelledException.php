<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Exception thrown when import is cancelled by user
 *
 * Indicates that the user requested cancellation of the import process,
 * which may be during validation, transformation, or duplicate resolution.
 */
class ImportCancelledException extends ImportException
{
    /**
     * Create exception for user-initiated cancellation
     *
     * @param string $reason The reason for cancellation
     * @param string|null $sessionId The session ID being cancelled
     * @return self
     */
    public static function byUser(string $reason, ?string $sessionId = null): self
    {
        $message = "Import cancelled by user: {$reason}";
        if ($sessionId) {
            $message .= " (session: {$sessionId})";
        }
        return new self($message);
    }

    /**
     * Create exception for system-initiated cancellation
     *
     * @param string $reason The reason for cancellation
     * @return self
     */
    public static function bySystem(string $reason): self
    {
        return new self("Import cancelled by system: {$reason}");
    }

    /**
     * Create exception for timeout cancellation
     *
     * @param int $timeoutSeconds The timeout period
     * @return self
     */
    public static function timeout(int $timeoutSeconds): self
    {
        return new self("Import cancelled: timeout exceeded after {$timeoutSeconds} seconds");
    }
}
