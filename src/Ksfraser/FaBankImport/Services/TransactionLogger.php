<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;

/**
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/TransactionLogger.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionLogger
{
    private $logFile;

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct */
    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/transactions.log';
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: logTransactionProcessed */
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