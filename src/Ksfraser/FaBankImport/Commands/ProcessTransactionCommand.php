<?php

namespace Ksfraser\FaBankImport\Commands;

class ProcessTransactionCommand
{
    private $transactionId;
    private $type;
    private $userId;

    public function __construct($transactionId, $type, $userId = null)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->userId = $userId;
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
