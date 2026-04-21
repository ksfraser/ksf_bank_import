<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Service;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;

/**
 * Contract for the commit service that posts reconciliation results back to FA.
 *
 * The implementation is deferred until FA integration sub-phase.
 * This interface keeps the domain fully decoupled from FA internals.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Service
 * @author  Kevin Fraser
 */
interface ReconciliationCommitServiceInterface
{
    /**
     * Commit an approved ReconciliationSession to FA.
     *
     * The concrete implementation will:
     * 1. Call FA's bank reconciliation persistence routines for each matched pair.
     * 2. Mark the ReconciliationSession as committed.
     *
     * @param int $sessionId ID of the approved ReconciliationSession.
     * @param int $userId    FA user performing the commit.
     * @return void
     */
    public function commit(int $sessionId, int $userId): void;
}
