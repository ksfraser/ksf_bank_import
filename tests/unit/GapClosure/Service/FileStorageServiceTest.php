<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service;

use Ksfraser\FaBankImport\Service\FileStorageService;
use Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service\Support\AlwaysExistingFileStorageService;
use Ksfraser\FaBankImport\ValueObject\FileInfo;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/AlwaysExistingFileStorageService.php';
require_once __DIR__ . '/../../Services/Support/fa_company_path.php';

/**
 * Coverage for the file storage service.
 *
 * @see \Ksfraser\FaBankImport\Service\FileStorageService
 */
final class FileStorageServiceTest extends TestCase
{
    /** @var string The temporary directory of the test. */
    private $baseDir;

    /** @var mixed The company path the test had before it started. */
    private $previousCompanyPath;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ksf_gap_storage_' . uniqid('', true);
        mkdir($this->baseDir, 0777, true);

        $this->previousCompanyPath = $GLOBALS['__ksf_test_company_path'] ?? null;
        $GLOBALS['__ksf_test_company_path'] = $this->baseDir;

    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->previousCompanyPath === null) {
            unset($GLOBALS['__ksf_test_company_path']);
        } else {
            $GLOBALS['__ksf_test_company_path'] = $this->previousCompanyPath;
        }

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

        $entries = scandir($path) ?: [];
        foreach ($entries as $entry) {
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
     * Run a callback with the PHPUnit error handler replaced by one that
     * ignores the warnings of the file system calls.
     *
     * @param callable $callback Callback to run.
     * @return mixed Whatever the callback returned.
     */
    private function withoutWarnings(callable $callback)
    {
        set_error_handler(static function () {
            return true;
        });

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return void
     */
    public function testTheDefaultStorageDirectorySitsUnderTheCompanyPath(): void
    {
        $service = new FileStorageService();

        $this->assertSame(
            $this->baseDir . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'uploads',
            $service->getStorageDirectory()
        );
    }

    /**
     * @return void
     */
    public function testAStoredFileIsMovedAndProtected(): void
    {
        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $source = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($source, 'a statement');

        $fileInfo = new FileInfo('statement.qfx', $source, 11, 'application/octet-stream');
        $result = $service->store($fileInfo, 'qfx');

        $this->assertFileExists($result['path']);
        $this->assertStringStartsWith('QFX_statement_', $result['filename']);
        $this->assertStringEndsWith('.qfx', $result['filename']);
        $this->assertFileExists($this->baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '.htaccess');
    }

    /**
     * @return void
     */
    public function testStoringFailsWhenTheFileCannotBeMoved(): void
    {
        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $fileInfo = new FileInfo(
            'statement.qfx',
            $this->baseDir . DIRECTORY_SEPARATOR . 'missing.qfx',
            11,
            'application/octet-stream'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to move uploaded file to:');

        $this->withoutWarnings(static function () use ($service, $fileInfo): void {
            $service->store($fileInfo, 'qfx');
        });
    }

    /**
     * @return void
     */
    public function testTheStorageDirectoryIsCreatedWithAProtectionFile(): void
    {
        $directory = $this->baseDir . DIRECTORY_SEPARATOR . 'uploads';
        $service = new FileStorageService($directory);

        $service->ensureStorageDirectoryExists();

        $this->assertDirectoryExists($directory);
        $this->assertFileExists($directory . DIRECTORY_SEPARATOR . '.htaccess');
    }

    /**
     * @return void
     */
    public function testTheStorageDirectoryCannotBeCreatedBelowAFile(): void
    {
        $blocker = $this->baseDir . DIRECTORY_SEPARATOR . 'blocker';
        file_put_contents($blocker, 'not a directory');

        $service = new FileStorageService($blocker . DIRECTORY_SEPARATOR . 'uploads');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create storage directory:');

        $this->withoutWarnings(static function () use ($service): void {
            $service->ensureStorageDirectoryExists();
        });
    }

    /**
     * @return void
     */
    public function testTheContentsOfAFileCanBeRead(): void
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($path, 'a statement');

        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->assertSame('a statement', $service->getContents($path));
    }

    /**
     * @return void
     */
    public function testReadingFailsWhenTheFileCannotBeOpened(): void
    {
        $service = new AlwaysExistingFileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read file:');

        $service->getContents($this->baseDir . DIRECTORY_SEPARATOR . 'missing.qfx');
    }

    /**
     * @return void
     */
    public function testTheSizeOfAFileCanBeRead(): void
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($path, 'a statement');

        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->assertSame(11, $service->getFileSize($path));
    }

    /**
     * @return void
     */
    public function testReadingTheSizeFailsWhenTheSizeCannotBeRead(): void
    {
        $service = new AlwaysExistingFileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to get file size:');

        $service->getFileSize($this->baseDir . DIRECTORY_SEPARATOR . 'missing.qfx');
    }

    /**
     * @return void
     */
    public function testTheModificationTimeOfAFileCanBeRead(): void
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($path, 'a statement');

        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->assertSame(filemtime($path), $service->getModificationTime($path));
    }

    /**
     * @return void
     */
    public function testReadingTheModificationTimeFailsForAMissingFile(): void
    {
        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found:');

        $service->getModificationTime($this->baseDir . DIRECTORY_SEPARATOR . 'missing.qfx');
    }

    /**
     * @return void
     */
    public function testReadingTheModificationTimeFailsWhenTheTimeCannotBeRead(): void
    {
        $service = new AlwaysExistingFileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to get file mtime:');

        $service->getModificationTime($this->baseDir . DIRECTORY_SEPARATOR . 'missing.qfx');
    }

    /**
     * @return void
     */
    public function testAFileIsCopiedIntoANewDirectory(): void
    {
        $source = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($source, 'a statement');

        $destination = $this->baseDir . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . 'statement.qfx';

        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->assertTrue($service->copy($source, $destination));
        $this->assertFileExists($destination);
    }

    /**
     * @return void
     */
    public function testCopyingFailsWhenTheDestinationCannotBeWritten(): void
    {
        $source = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($source, 'a statement');

        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->assertFalse($service->copy($source, $this->baseDir));
    }

    /**
     * @return void
     */
    public function testAFileIsDeleted(): void
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . 'statement.qfx';
        file_put_contents($path, 'a statement');

        $service = new FileStorageService($this->baseDir . DIRECTORY_SEPARATOR . 'uploads');

        $this->assertTrue($service->exists($path));
        $this->assertTrue($service->delete($path));
        $this->assertFalse($service->exists($path));
        $this->assertFalse($service->delete($path));
    }
}
