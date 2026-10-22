<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\DuplicateDetector;
use Ksfraser\FaBankImport\Entity\UploadedFile;
use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\Repository\UploadedFileRepositoryInterface;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Service\FileStorageServiceInterface;

/**
 * Behavioural tests for the duplicate detector.
 */
final class DuplicateDetectorTest extends TestCase
{
    /** @var UploadedFileRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileRepository;

    /** @var ConfigRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configRepository;

    /** @var FileStorageServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $storageService;

    /** @var string */
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileRepository = $this->createMock(UploadedFileRepositoryInterface::class);
        $this->configRepository = $this->createMock(ConfigRepositoryInterface::class);
        $this->storageService = $this->createMock(FileStorageServiceInterface::class);

        $this->tempDir = sys_get_temp_dir() . '/ksf_bank_import_dupes_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function testDetectReturnsNotDuplicateWhenDuplicateCheckingIsDisabled(): void
    {
        $this->configRepository->method('get')->with('check_duplicates', true)->willReturn(false);
        $this->fileRepository->expects($this->never())->method('findDuplicate');

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertFalse($result->isDuplicate());
        $this->assertNull($result->getExistingFile());
    }

    public function testDetectReturnsNotDuplicateWhenNoSimilarFileExists(): void
    {
        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn(null);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertFalse($result->isDuplicate());
    }

    public function testDetectReturnsNotDuplicateWhenTheContentDiffers(): void
    {
        $existingPath = $this->tempDir . '/existing.qfx';
        file_put_contents($existingPath, 'different body');

        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($existingPath));
        $this->storageService->method('exists')->willReturn(true);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertFalse($result->isDuplicate());
    }

    public function testDetectReturnsNotDuplicateWhenTheStoredFileIsGone(): void
    {
        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($this->tempDir . '/gone.qfx'));
        $this->storageService->method('exists')->willReturn(false);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertFalse($result->isDuplicate());
    }

    public function testDetectReturnsNotDuplicateWhenTheStoredFileCannotBeRead(): void
    {
        // The storage service still claims the file exists, but the file is
        // gone, so the hash cannot be calculated.
        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn(
            $this->existingFile($this->tempDir . '/vanished.qfx')
        );
        $this->storageService->method('exists')->willReturn(true);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertFalse($result->isDuplicate());
    }

    public function testDetectWarnsAboutAnIdenticalFileByDefault(): void
    {
        $existingPath = $this->tempDir . '/existing.qfx';
        file_put_contents($existingPath, 'body');

        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($existingPath));
        $this->storageService->method('exists')->willReturn(true);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertTrue($result->isDuplicate());
        $this->assertTrue($result->shouldWarn());
        $this->assertSame('warn', $result->getAction());
        $this->assertSame(5, $result->getExistingFile()->getId());
    }

    public function testDetectAllowsAnIdenticalFileWhenTheActionIsAllow(): void
    {
        $existingPath = $this->tempDir . '/existing.qfx';
        file_put_contents($existingPath, 'body');

        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                if ($key === 'check_duplicates') {
                    return true;
                }
                if ($key === 'duplicate_action') {
                    return 'allow';
                }

                return $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($existingPath));
        $this->storageService->method('exists')->willReturn(true);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertTrue($result->isDuplicate());
        $this->assertTrue($result->shouldAllow());
    }

    public function testDetectBlocksAnIdenticalFileWhenTheActionIsBlock(): void
    {
        $existingPath = $this->tempDir . '/existing.qfx';
        file_put_contents($existingPath, 'body');

        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                if ($key === 'check_duplicates') {
                    return true;
                }
                if ($key === 'duplicate_action') {
                    return 'block';
                }

                return $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($existingPath));
        $this->storageService->method('exists')->willReturn(true);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertTrue($result->isDuplicate());
        $this->assertTrue($result->shouldBlock());
    }

    public function testDetectFallsBackToWarnForAnUnknownAction(): void
    {
        $existingPath = $this->tempDir . '/existing.qfx';
        file_put_contents($existingPath, 'body');

        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                if ($key === 'check_duplicates') {
                    return true;
                }
                if ($key === 'duplicate_action') {
                    return 'nonsense';
                }

                return $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($existingPath));
        $this->storageService->method('exists')->willReturn(true);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);
        $result = $detector->detect($this->fileInfo('statement.qfx', 'body'));

        $this->assertTrue($result->shouldWarn());
    }

    public function testIsDuplicateReportsWhetherASimilarFileIsStored(): void
    {
        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn($this->existingFile($this->tempDir . '/existing.qfx'));

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);

        $this->assertTrue($detector->isDuplicate('statement.qfx', 10));
    }

    public function testIsDuplicateIsFalseWhenDuplicateCheckingIsDisabled(): void
    {
        $this->configRepository->method('get')->with('check_duplicates', true)->willReturn(false);
        $this->fileRepository->expects($this->never())->method('findDuplicate');

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);

        $this->assertFalse($detector->isDuplicate('statement.qfx', 10));
    }

    public function testIsDuplicateIsFalseWhenNothingSimilarIsStored(): void
    {
        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                return $key === 'check_duplicates' ? true : $default;
            }
        );
        $this->fileRepository->method('findDuplicate')->willReturn(null);

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);

        $this->assertFalse($detector->isDuplicate('statement.qfx', 10));
    }

    public function testGetStatisticsReportsTheCurrentConfiguration(): void
    {
        $this->configRepository->method('get')->willReturnCallback(
            function ($key, $default = null) {
                if ($key === 'check_duplicates') {
                    return true;
                }
                if ($key === 'duplicate_window_days') {
                    return 30;
                }
                if ($key === 'duplicate_action') {
                    return 'block';
                }

                return $default;
            }
        );

        $detector = new DuplicateDetector($this->fileRepository, $this->configRepository, $this->storageService);

        $this->assertSame(
            [
                'enabled' => true,
                'window_days' => 30,
                'action' => 'block',
            ],
            $detector->getStatistics()
        );
    }

    private function fileInfo(string $name, string $contents): FileInfo
    {
        $path = $this->tempDir . '/' . $name;
        file_put_contents($path, $contents);

        return new FileInfo($name, $path, strlen($contents), 'application/x-qfx');
    }

    private function existingFile(string $path): UploadedFile
    {
        return new UploadedFile(
            5,
            'existing.qfx',
            'statement.qfx',
            $path,
            10,
            'application/x-qfx',
            new \DateTime('2026-01-15 10:00:00'),
            'admin',
            'qfx',
            null,
            0,
            null
        );
    }
}
