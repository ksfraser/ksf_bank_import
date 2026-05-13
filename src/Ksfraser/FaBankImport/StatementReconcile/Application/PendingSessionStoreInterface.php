<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

/**
 * Persists the in-progress reconciliation state across HTTP requests.
 *
 * The interface is intentionally narrow: store, load, clear.  Implementations
 * may back this with PHP's native session, a database, or an in-memory array
 * (for tests).
 */
interface PendingSessionStoreInterface
{
    /**
     * Persist the pending session data.
     *
     * @param array $data Arbitrary key-value state for the current workflow step.
     */
    public function store(array $data): void;

    /**
     * Retrieve the pending session data, or null if none exists.
     *
     * @return array|null
     */
    public function load(): ?array;

    /**
     * Discard the pending session data.
     */
    public function clear(): void;
}
