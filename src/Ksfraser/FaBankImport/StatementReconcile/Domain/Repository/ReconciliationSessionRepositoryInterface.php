<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Repository;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;

/**
 * Contract for persisting and retrieving ReconciliationSession aggregates.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Repository
 * @author  Kevin Fraser
 */
interface ReconciliationSessionRepositoryInterface
{
    /**
     * Persist a session (insert or update).
     *
     * @param ReconciliationSession $session
     * @return int Assigned database ID.
     * @throws ReconciliationException on persistence failure.
     */
    public function save(ReconciliationSession $session): int;

    /**
     * Retrieve by primary key.
     *
     * @param int $id
     * @return ReconciliationSession|null  Null when not found.
     */
    public function findById(int $id): ?ReconciliationSession;

    /**
     * Retrieve the most recent session for a given StatementOcr ID.
     *
     * @param int $statementOcrId
     * @return ReconciliationSession|null
     */
    public function findLatestByStatementOcrId(int $statementOcrId): ?ReconciliationSession;

    /**
     * Shortcut: approve the session and persist in a single call.
     *
     * @param int $sessionId
     * @param int $userId    FA user approving.
     * @throws ReconciliationException if not found or already approved.
     */
    public function approve(int $sessionId, int $userId): void;
}
