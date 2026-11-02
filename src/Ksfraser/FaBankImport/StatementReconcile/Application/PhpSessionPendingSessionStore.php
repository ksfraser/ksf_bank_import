<?php

declare(strict_types=1);

/** @BABOK RTM: FR-007 / BR-004 / ARCH-010*/
namespace Ksfraser\FaBankImport\StatementReconcile\Application;

/**
 * @BABOK CLASS ROLLUP: PhpSessionPendingSessionStore — 5 methods; FR-007 (5); BR-004 (5)
 * @BABOK RTM: FR-007 / BR-004 / ARCH-010; file: StatementReconcile/Application/PhpSessionPendingSessionStore.php; coverage: docs/TestCases/ (file rollup)
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

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: __construct */
    public function __construct(string $key = 'sr_pending_session')
    {
        $this->key = $key;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: store */
    public function store(array $data): void
    {
        $this->ensureSessionStarted();
        $_SESSION[$this->key] = $data;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: load */
    public function load(): ?array
    {
        $this->ensureSessionStarted();
        return $_SESSION[$this->key] ?? null;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: clear */
    public function clear(): void
    {
        $this->ensureSessionStarted();
        unset($_SESSION[$this->key]);
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: ensureSessionStarted */
    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
