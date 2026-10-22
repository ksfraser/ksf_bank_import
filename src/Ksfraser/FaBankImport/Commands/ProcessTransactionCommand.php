<?php

namespace Ksfraser\FaBankImport\Commands;

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class ProcessTransactionCommand
{
    private $transactionId;
    private $type;
    private $userId;

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(int $transactionId, string $type, ?int $userId = null)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->userId = $userId;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
