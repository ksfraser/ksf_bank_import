<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service;

use Ksfraser\FaBankImport\Service\ImportRunLogger;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the import run logger.
 *
 * @see \Ksfraser\FaBankImport\Service\ImportRunLogger
 */
final class ImportRunLoggerTest extends TestCase
{
    /** @var string The temporary directory of the test. */
    private $baseDir;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ksf_gap_logger_' . uniqid('', true);
        mkdir($this->baseDir, 0777, true);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
    }

    /**
     * Remove a directory tree again.
     *
     * @param string $path The directory to remove.
     * @return void
     */
    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($child)) {
                $this->removeTree($child);
                continue;
            }
            @chmod($child, 0666);
            @unlink($child);
        }

        @chmod($path, 0777);
        @rmdir($path);
    }

    /**
     * @return void
     */
    public function testARunIsStartedInANewDirectory(): void
    {
        $directory = $this->baseDir . DIRECTORY_SEPARATOR . 'logs';

        $logger = ImportRunLogger::start($directory);

        $this->assertDirectoryExists($directory);
        $this->assertFileExists($logger->getLogPath());
        $this->assertNotSame('', $logger->getRunId());
    }

    /**
     * @return void
     */
    public function testTheLogDirectoryIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('logDir must not be empty');

        ImportRunLogger::start('');
    }

    /**
     * @return void
     */
    public function testTheLogDirectoryCannotBeCreatedBelowAFile(): void
    {
        $blocker = $this->baseDir . DIRECTORY_SEPARATOR . 'blocker';
        file_put_contents($blocker, 'not a directory');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create log directory:');

        ImportRunLogger::start($blocker . DIRECTORY_SEPARATOR . 'logs');
    }

    /**
     * @return void
     */
    public function testTheLogFileCannotBeOpened(): void
    {
        $directory = $this->baseDir . DIRECTORY_SEPARATOR . 'readonly';
        mkdir($directory, 0500, true);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to open log file for writing:');

            ImportRunLogger::start($directory);
        } finally {
            chmod($directory, 0700);
        }
    }

    /**
     * @return void
     */
    public function testTheLogPathIsRequiredToResume(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('logPath must not be empty');

        ImportRunLogger::resume('');
    }

    /**
     * @return void
     */
    public function testTheLogFileHasToExistToResume(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Log file does not exist:');

        ImportRunLogger::resume($this->baseDir . DIRECTORY_SEPARATOR . 'missing.jsonl');
    }

    /**
     * @return void
     */
    public function testARunIsResumedFromItsLogFile(): void
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . 'import_run_20260101_120000_abcdef.jsonl';
        file_put_contents($path, '');

        $logger = ImportRunLogger::resume($path);

        $this->assertSame($path, $logger->getLogPath());
        $this->assertSame('20260101_120000_abcdef', $logger->getRunId());
    }

    /**
     * @return void
     */
    public function testARunWithAnUnrecognisedFileNameGetsAFreshIdentifier(): void
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . 'something-else.log';
        file_put_contents($path, '');

        $logger = ImportRunLogger::resume($path);

        $this->assertSame($path, $logger->getLogPath());
        $this->assertNotSame('', $logger->getRunId());
    }

    /**
     * @return void
     */
    public function testAnEventIsWrittenToTheLog(): void
    {
        $logger = ImportRunLogger::start($this->baseDir . DIRECTORY_SEPARATOR . 'logs');

        $logger->event('statement_imported', ['file' => 'statement.qfx']);

        $contents = (string) file_get_contents($logger->getLogPath());

        $this->assertStringContainsString('statement_imported', $contents);
        $this->assertStringContainsString('statement.qfx', $contents);
    }

    /**
     * @return void
     */
    public function testTheEventNameIsRequired(): void
    {
        $logger = ImportRunLogger::start($this->baseDir . DIRECTORY_SEPARATOR . 'logs');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('eventName must not be empty');

        $logger->event('');
    }

    /**
     * @return void
     */
    public function testAnEventThatCannotBeEncodedIsRejected(): void
    {
        $logger = ImportRunLogger::start($this->baseDir . DIRECTORY_SEPARATOR . 'logs');

        // The event name is not valid UTF-8, so the record cannot be
        // encoded - not even after the context was replaced.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode log record to JSON');

        $logger->event("\xB1\x31");
    }

    /**
     * @return void
     */
    public function testAnEventCannotBeWrittenToAReadOnlyLog(): void
    {
        $logger = ImportRunLogger::start($this->baseDir . DIRECTORY_SEPARATOR . 'logs');
        chmod($logger->getLogPath(), 0400);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to append to log file:');

            $logger->event('statement_imported');
        } finally {
            chmod($logger->getLogPath(), 0600);
        }
    }
}
