<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionLogger [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionLogger.
 */
namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;

class TransactionLogger
{
    private $logFile;

    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/transactions.log';
    }

    public function logTransactionProcessed(TransactionProcessedEvent $event): void
    {
        $message = sprintf(
            '[%s] Transaction %d processed as type %s',
            $event->getTimestamp()->format('Y-m-d H:i:s'),
            $event->getTransactionId(),
            $event->getType()
        );

        file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND);
    }
}