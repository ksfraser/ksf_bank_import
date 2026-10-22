<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionLogger;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;

/**
 * Unit tests for the transaction logger.
 */
final class TransactionLoggerTest extends TestCase
{
    /** @var string */
    private $logFile;

    /** @var string */
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/ksf_bank_import_txlogger_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
        $this->logFile = $this->tempDir . '/transactions.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function testLogTransactionProcessedWritesASummaryLine(): void
    {
        $logger = new TransactionLogger($this->logFile);
        $event = new TransactionProcessedEvent(42, 'SP');

        $logger->logTransactionProcessed($event);

        $this->assertFileExists($this->logFile);
        $contents = (string) file_get_contents($this->logFile);

        $this->assertStringContainsString('Transaction 42 processed as type SP', $contents);
        $this->assertStringContainsString($event->getTimestamp()->format('Y-m-d H:i:s'), $contents);
    }

    public function testLogTransactionProcessedAppendsToAnExistingLog(): void
    {
        $logger = new TransactionLogger($this->logFile);

        $logger->logTransactionProcessed(new TransactionProcessedEvent(1, 'SP'));
        $logger->logTransactionProcessed(new TransactionProcessedEvent(2, 'BT'));

        $lines = array_values(array_filter(explode("\n", (string) file_get_contents($this->logFile))));

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Transaction 1 processed as type SP', $lines[0]);
        $this->assertStringContainsString('Transaction 2 processed as type BT', $lines[1]);
    }

    public function testDefaultLogFileIsUsedWhenNoPathIsGiven(): void
    {
        $logger = new TransactionLogger();

        $reflection = new \ReflectionClass(TransactionLogger::class);
        $property = $reflection->getProperty('logFile');
        $property->setAccessible(true);

        $this->assertStringEndsWith('logs/transactions.log', (string) $property->getValue($logger));
    }
}
