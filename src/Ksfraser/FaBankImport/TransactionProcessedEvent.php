<?php

namespace Ksfraser\FaBankImport\Events;

//TODO: Replace.  This looks like a DAO

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: TransactionProcessedEvent.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionProcessedEvent
{
    private $transactionId;
    private $type;
    private $timestamp;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct(int $transactionId, string $type)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->timestamp = new \DateTime();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTransactionId */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getType */
    public function getType(): string
    {
        return $this->type;
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: getTimestamp */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}
