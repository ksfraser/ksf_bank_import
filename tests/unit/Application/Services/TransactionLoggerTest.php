<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Services;

use Ksfraser\Application\Services\TransactionLogger;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';

final class TransactionLoggerTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->logPath = \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::createTempDir('ksf_txlog_');
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restoreConfig();
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::removeDir($this->logPath);
    }

    public function testTheLogFileIsUsedFromTheConfiguredLoggingDirectory(): void
    {
        $logger = new TransactionLogger();
        $logger->logTransactionProcessed(new TransactionProcessedEvent(3, 'credit'));

        $this->assertFileExists($this->logPath . '/transactions.log');
        $this->assertStringContainsString(
            'Transaction 3 processed as type credit',
            (string) file_get_contents($this->logPath . '/transactions.log')
        );
    }

    public function testTheLogDirectoryIsCreatedWhenItDoesNotExist(): void
    {
        $target = $this->logPath . '/nested/deeper';
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useConfig([
            'logging.path' => $target,
        ]);

        new TransactionLogger();

        $this->assertDirectoryExists($target);
    }

    public function testAnExplicitLogFileIsUsedAsIs(): void
    {
        $logFile = $this->logPath . '/custom.log';

        $logger = new TransactionLogger($logFile);
        $logger->logTransactionProcessed(new TransactionProcessedEvent(42, 'credit'));

        $this->assertFileExists($logFile);
        $this->assertStringContainsString('Transaction 42 processed as type credit', (string) file_get_contents($logFile));
    }

    public function testTheEventTimestampIsUsedWhenAvailable(): void
    {
        $logFile = $this->logPath . '/custom.log';
        $event = new TransactionProcessedEvent(7, 'debit');

        $logger = new TransactionLogger($logFile);
        $logger->logTransactionProcessed($event);

        $contents = (string) file_get_contents($logFile);
        $this->assertStringContainsString('[' . $event->getTimestamp()->format('Y-m-d H:i:s') . ']', $contents);
        $this->assertStringContainsString('Transaction 7 processed as type debit', $contents);
    }

    public function testAnEventWithoutTheExpectedMethodsFallsBackToPlaceholders(): void
    {
        $logFile = $this->logPath . '/custom.log';

        $logger = new TransactionLogger($logFile);
        $logger->logTransactionProcessed(new \stdClass());

        $contents = (string) file_get_contents($logFile);
        $this->assertStringContainsString('Transaction ? processed as type ?', $contents);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $contents);
    }

    public function testANonObjectEventFallsBackToPlaceholders(): void
    {
        $logFile = $this->logPath . '/custom.log';

        $logger = new TransactionLogger($logFile);
        $logger->logTransactionProcessed('not an event');

        $contents = (string) file_get_contents($logFile);
        $this->assertStringContainsString('Transaction ? processed as type ?', $contents);
    }

    public function testEntriesAreAppendedToTheLogFile(): void
    {
        $logFile = $this->logPath . '/custom.log';

        $logger = new TransactionLogger($logFile);
        $logger->logTransactionProcessed(new TransactionProcessedEvent(1, 'credit'));
        $logger->logTransactionProcessed(new TransactionProcessedEvent(2, 'debit'));

        $contents = (string) file_get_contents($logFile);
        $this->assertStringContainsString('Transaction 1 processed as type credit', $contents);
        $this->assertStringContainsString('Transaction 2 processed as type debit', $contents);
    }
}
