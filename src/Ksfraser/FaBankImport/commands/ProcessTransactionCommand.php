<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ProcessTransactionCommand [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ProcessTransactionCommand.
 */
namespace Ksfraser\FaBankImport\Commands;

class ProcessTransactionCommand
{
    private $transactionId;
    private $type;
    private $userId;

    public function __construct(int $transactionId, string $type, ?int $userId = null)
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
