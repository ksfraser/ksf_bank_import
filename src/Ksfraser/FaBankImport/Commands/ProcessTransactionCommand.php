<?php

namespace Ksfraser\FaBankImport\Commands;

/**
 * @BABOK CLASS ROLLUP: ProcessTransactionCommand — 4 methods; FR-014 (4)
 * @BABOK RTM: FR-014 / ARCH-004; file: Commands/ProcessTransactionCommand.php; coverage: docs/TestCases/ (file rollup)
 */
class ProcessTransactionCommand
{
    private $transactionId;
    private $type;
    private $userId;

    /** @BABOK FR-014 / ARCH-004 — method: __construct */
    public function __construct($transactionId, $type, $userId = null)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->userId = $userId;
    }

    /** @BABOK FR-014 / ARCH-004 — method: getTransactionId */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /** @BABOK FR-014 / ARCH-004 — method: getType */
    public function getType(): string
    {
        return $this->type;
    }

    /** @BABOK FR-014 / ARCH-004 — method: getUserId */
    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
