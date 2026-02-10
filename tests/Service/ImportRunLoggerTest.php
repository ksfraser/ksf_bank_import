<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Service;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\ImportRunLogger;

final class ImportRunLoggerTest extends TestCase
{
    public function test_it_creates_a_new_log_file_in_directory(): void
    {
        $dir = $this->makeTempDir();

        $logger = ImportRunLogger::start($dir);

        $this->assertFileExists($logger->getLogPath());
        $this->assertStringContainsString('import_run_', basename($logger->getLogPath()));
        $this->assertSame($dir, dirname($logger->getLogPath()));
    }

    public function test_it_appends_jsonl_events(): void
    {
        $dir = $this->makeTempDir();
        $logger = ImportRunLogger::start($dir);

        $logger->event('run.started', ['parser' => 'qfx']);
        $logger->event('file.parsed', ['file_index' => 0, 'filename' => 'x.qfx']);

        $lines = file($logger->getLogPath(), FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($lines);
        $this->assertCount(2, $lines);

        $first = json_decode($lines[0], true);
        $second = json_decode($lines[1], true);

        $this->assertIsArray($first);
        $this->assertSame('run.started', $first['event'] ?? null);
        $this->assertSame('qfx', $first['context']['parser'] ?? null);

        $this->assertIsArray($second);
        $this->assertSame('file.parsed', $second['event'] ?? null);
        $this->assertSame('x.qfx', $second['context']['filename'] ?? null);

        $this->assertNotEmpty($first['ts'] ?? null);
        $this->assertNotEmpty($first['run_id'] ?? null);
        $this->assertSame($first['run_id'], $second['run_id'] ?? null);
    }

    public function test_it_safely_creates_directory_if_missing(): void
    {
        $base = $this->makeTempDir();
        $dir = $base . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'logs';

        $logger = ImportRunLogger::start($dir);

        $this->assertDirectoryExists($dir);
        $this->assertFileExists($logger->getLogPath());
    }

    public function test_it_can_resume_an_existing_log_file(): void
    {
        $dir = $this->makeTempDir();
        $logger = ImportRunLogger::start($dir);
        $logger->event('run.started', ['step' => 'upload']);

        $resumed = ImportRunLogger::resume($logger->getLogPath());
        $resumed->event('run.resumed', ['step' => 'import']);

        $lines = file($logger->getLogPath(), FILE_IGNORE_NEW_LINES);
        $this->assertCount(2, $lines);

        $first = json_decode($lines[0], true);
        $second = json_decode($lines[1], true);

        $this->assertSame($first['run_id'] ?? null, $second['run_id'] ?? null);
        $this->assertSame('run.resumed', $second['event'] ?? null);
    }

    private function makeTempDir(): string
    {
        $base = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'ksf_bank_import_tests';
        @mkdir($base, 0777, true);

        $dir = $base . DIRECTORY_SEPARATOR . 'run_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(8)), 0, 8);
        $ok = @mkdir($dir, 0777, true);
        $this->assertTrue($ok, 'Failed to create temp dir: ' . $dir);

        return $dir;
    }
}
