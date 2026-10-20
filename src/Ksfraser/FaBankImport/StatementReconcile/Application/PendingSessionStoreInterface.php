<?php

declare(strict_types=1);

/** @BABOK See docs/Requirements/ for module coverage tracking - Referenced in RTM */
namespace Ksfraser\FaBankImport\StatementReconcile\Application;

/**
 * @BABOK RTM: FR-007 / BR-004 / ARCH-010; file: StatementReconcile/Application/PendingSessionStoreInterface.php; coverage: docs/TestCases/ (file rollup)
 * Persists the in-progress reconciliation state across HTTP requests.
 *
 * The interface is intentionally narrow: store, load, clear.  Implementations
 * may back this with PHP's native session, a database, or an in-memory array
 * (for tests).
 */
interface PendingSessionStoreInterface
{
    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: store
     * Persist the pending session data.
     *
     * @param array $data Arbitrary key-value state for the current workflow step.
     */
    public function store(array $data): void;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: load
     * Retrieve the pending session data, or null if none exists.
     *
     * @return array|null
     */
    public function load(): ?array;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: clear
     * Discard the pending session data.
     */
    public function clear(): void;
}
