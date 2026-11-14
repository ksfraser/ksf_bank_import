<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Repository;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;

/**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001

 * Contract for persisting and retrieving StatementOcr aggregates.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Repository
 * @author  Kevin Fraser
 */
interface StatementOcrRepositoryInterface
{
    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     * Persist a StatementOcr. On insert returns the new ID; on update returns existing ID.
     *
     * @param StatementOcr $entity
     * @return int Assigned database ID.
     * @throws StatementOcrException on persistence failure.
     */
    public function save(StatementOcr $entity): int;

    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     * Retrieve by primary key.
     *
     * @param int $id
     * @return StatementOcr|null  Null when not found.
     */
    public function findById(int $id): ?StatementOcr;

    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     * Retrieve all statements for a given account identifier, newest first.
     *
     * @param string $accountIdentifier
     * @return StatementOcr[]
     */
    public function findByAccountIdentifier(string $accountIdentifier): array;
}
