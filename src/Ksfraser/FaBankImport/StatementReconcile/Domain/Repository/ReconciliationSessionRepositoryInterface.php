<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Repository;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;

/**
 * @BABOK CLASS ROLLUP: ReconciliationSessionRepositoryInterface — 4 methods; FR-007 (4); BR-004 (4)
 * @BABOK RTM: FR-007 / BR-004 / ARCH-010; file: StatementReconcile/Domain/Repository/ReconciliationSessionRepositoryInterface.php; coverage: docs/TestCases/ (file rollup)
 * Contract for persisting and retrieving ReconciliationSession aggregates.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Repository
 * @author  Kevin Fraser
 */
interface ReconciliationSessionRepositoryInterface
{
    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: save
     * Persist a session (insert or update).
     *
     * @param ReconciliationSession $session
     * @return int Assigned database ID.
     * @throws ReconciliationException on persistence failure.
     */
    public function save(ReconciliationSession $session): int;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: findById
     * Retrieve by primary key.
     *
     * @param int $id
     * @return ReconciliationSession|null  Null when not found.
     */
    public function findById(int $id): ?ReconciliationSession;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: findLatestByStatementOcrId
     * Retrieve the most recent session for a given StatementOcr ID.
     *
     * @param int $statementOcrId
     * @return ReconciliationSession|null
     */
    public function findLatestByStatementOcrId(int $statementOcrId): ?ReconciliationSession;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: approve
     * Shortcut: approve the session and persist in a single call.
     *
     * @param int $sessionId
     * @param int $userId    FA user approving.
     * @throws ReconciliationException if not found or already approved.
     */
    public function approve(int $sessionId, int $userId): void;
}
