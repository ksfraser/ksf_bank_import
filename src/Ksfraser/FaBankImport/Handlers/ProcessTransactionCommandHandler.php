<?php

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;

/**
 * @BABOK RTM: FR-049 / ARCH-004; file: Handlers/ProcessTransactionCommandHandler.php; coverage: docs/TestCases/ (file rollup)
 */
class ProcessTransactionCommandHandler
{
    private $transactionService;

    /** @BABOK FR-049 / ARCH-004 — method: __construct */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /** @BABOK FR-049 / ARCH-004 — method: handle */
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
