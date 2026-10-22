<?php

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class ProcessTransactionCommandHandler
{
    private $transactionService;

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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
