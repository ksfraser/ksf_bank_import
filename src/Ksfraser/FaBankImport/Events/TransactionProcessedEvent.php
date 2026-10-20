<?php

namespace Ksfraser\FaBankImport\Events;

/**
 * @BABOK RTM: BR-003 / ARCH-004; file: Events/TransactionProcessedEvent.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionProcessedEvent
{
    private $transactionId;
    private $type;
    private $timestamp;

    /** @BABOK BR-003 / ARCH-004 — method: __construct */
    public function __construct(int $transactionId, string $type)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->timestamp = new \DateTime();
    }

    /** @BABOK BR-003 / ARCH-004 — method: getTransactionId */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /** @BABOK BR-003 / ARCH-004 — method: getType */
    public function getType(): string
    {
        return $this->type;
    }

    /** @BABOK BR-003 / ARCH-004 — method: getTimestamp */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}
