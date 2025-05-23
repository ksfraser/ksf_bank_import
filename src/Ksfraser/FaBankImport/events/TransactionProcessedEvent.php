<?php

namespace Ksfraser\FaBankImport\Events;

class TransactionProcessedEvent
{
    private $transactionId;
    private $type;
    private $timestamp;

    public function __construct(int $transactionId, string $type)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->timestamp = new \DateTime();
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}