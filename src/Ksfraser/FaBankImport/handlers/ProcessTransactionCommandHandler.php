<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ProcessTransactionCommandHandler [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ProcessTransactionCommandHandler.
 */
namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;

class ProcessTransactionCommandHandler
{
    private $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function handle(ProcessTransactionCommand $command): TransactionProcessedEvent
    {
        $success = $this->transactionService->processTransaction(
            $command->getTransactionId(),
            $command->getType()
        );

        if (!$success) {
            throw new \RuntimeException(
                "Failed to process transaction {$command->getTransactionId()}"
            );
        }

        return new TransactionProcessedEvent(
            $command->getTransactionId(),
            $command->getType()
        );
    }
}
