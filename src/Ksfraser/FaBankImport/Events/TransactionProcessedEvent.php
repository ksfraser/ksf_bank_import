<?php

namespace Ksfraser\FaBankImport\Events;

/**
 * @BABOK FR-PROC-002, FR-LOG-003 | BR-PROC-001 | UAT-PROC-001
 */
class TransactionProcessedEvent
{
    private $transactionId;
    private $type;
    private $timestamp;

    /**
 * @BABOK FR-PROC-002, FR-LOG-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(int $transactionId, string $type)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->timestamp = new \DateTime();
    }

    /**
 * @BABOK FR-PROC-002, FR-LOG-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
 * @BABOK FR-PROC-002, FR-LOG-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
 * @BABOK FR-PROC-002, FR-LOG-003 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}
