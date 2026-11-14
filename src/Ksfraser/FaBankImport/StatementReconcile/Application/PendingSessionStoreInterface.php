<?php

declare(strict_types=1);

/** @BABOK RTM: FR-007 / BR-004 / ARCH-010*/
namespace Ksfraser\FaBankImport\StatementReconcile\Application;

/**
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001

 * Persists the in-progress reconciliation state across HTTP requests.
 *
 * The interface is intentionally narrow: store, load, clear.  Implementations
 * may back this with PHP's native session, a database, or an in-memory array
 * (for tests).
 */
interface PendingSessionStoreInterface
{
    /**
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     * Persist the pending session data.
     *
     * @param array $data Arbitrary key-value state for the current workflow step.
     */
    public function store(array $data): void;

    /**
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     * Retrieve the pending session data, or null if none exists.
     *
     * @return array|null
     */
    public function load(): ?array;

    /**
 * @BABOK FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
     * Discard the pending session data.
     */
    public function clear(): void;
}
