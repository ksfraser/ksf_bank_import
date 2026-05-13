<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

/**
 * PHP $_SESSION-backed implementation of PendingSessionStoreInterface.
 *
 * FA calls session_start() in its bootstrap before any module code runs, so
 * the session is already active when this class is used in production.  The
 * guard is kept as a safety net for edge cases (e.g. CLI scripts).
 */
class PhpSessionPendingSessionStore implements PendingSessionStoreInterface
{
    /** @var string $_SESSION key under which the pending data is stored. */
    private $key;

    public function __construct(string $key = 'sr_pending_session')
    {
        $this->key = $key;
    }

    public function store(array $data): void
    {
        $this->ensureSessionStarted();
        $_SESSION[$this->key] = $data;
    }

    public function load(): ?array
    {
        $this->ensureSessionStarted();
        return $_SESSION[$this->key] ?? null;
    }

    public function clear(): void
    {
        $this->ensureSessionStarted();
        unset($_SESSION[$this->key]);
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
