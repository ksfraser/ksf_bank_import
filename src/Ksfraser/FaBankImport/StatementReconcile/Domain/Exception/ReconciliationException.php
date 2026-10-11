<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Exception;

use RuntimeException;

/**
 * Thrown when a reconciliation session is in an invalid state or operation fails.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Exception
 * @author  Kevin Fraser
 */
class ReconciliationException extends RuntimeException
{
    /**
     * @param int $sessionId
     * @return self
     */
    public static function sessionNotFound(int $sessionId): self
    {
        return new self('Reconciliation session not found: ' . $sessionId);
    }

    /**
     * @param string $currentStatus
     * @return self
     */
    public static function alreadyApproved(string $currentStatus): self
    {
        return new self('Cannot approve session already in status: ' . $currentStatus);
    }

    /**
     * @param string $reason
     * @return self
     */
    public static function forReason(string $reason): self
    {
        return new self('Reconciliation error: ' . $reason);
    }
}
