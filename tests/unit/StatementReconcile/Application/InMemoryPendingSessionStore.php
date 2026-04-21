<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\PendingSessionStoreInterface;

/**
 * In-memory test double for PendingSessionStoreInterface.
 *
 * Holds state in a plain array — no PHP session involved.
 * Instantiate with initial data to simulate a mid-workflow request.
 */
class InMemoryPendingSessionStore implements PendingSessionStoreInterface
{
    /** @var array|null */
    private $data;

    public function __construct(?array $initialData = null)
    {
        $this->data = $initialData;
    }

    public function store(array $data): void
    {
        $this->data = $data;
    }

    public function load(): ?array
    {
        return $this->data;
    }

    public function clear(): void
    {
        $this->data = null;
    }
}
