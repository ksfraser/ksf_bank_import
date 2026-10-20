<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Exception;

use RuntimeException;

/**
 * @BABOK CLASS ROLLUP: ReconciliationException — 3 methods; FR-007 (3); BR-004 (3)
 * @BABOK RTM: FR-007 / BR-004 / ARCH-010; file: StatementReconcile/Domain/Exception/ReconciliationException.php; coverage: docs/TestCases/ (file rollup)
 * Thrown when a reconciliation session is in an invalid state or operation fails.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Exception
 * @author  Kevin Fraser
 */
class ReconciliationException extends RuntimeException
{
    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: sessionNotFound
     * @param int $sessionId
     * @return self
     */
    public static function sessionNotFound(int $sessionId): self
    {
        return new self('Reconciliation session not found: ' . $sessionId);
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: alreadyApproved
     * @param string $currentStatus
     * @return self
     */
    public static function alreadyApproved(string $currentStatus): self
    {
        return new self('Cannot approve session already in status: ' . $currentStatus);
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: forReason
     * @param string $reason
     * @return self
     */
    public static function forReason(string $reason): self
    {
        return new self('Reconciliation error: ' . $reason);
    }
}
