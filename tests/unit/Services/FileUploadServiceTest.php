<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\FileUploadService;
use Ksfraser\FaBankImport\Service\DuplicateDetector;
use Ksfraser\FaBankImport\Entity\UploadedFile;
use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\ValueObject\DuplicateResult;
use Ksfraser\FaBankImport\Repository\UploadedFileRepositoryInterface;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Service\FileStorageServiceInterface;

/**
 * Behavioural tests for the file upload service.
 */
final class FileUploadServiceTest extends TestCase
{
    /** @var UploadedFileRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileRepository;

    /** @var FileStorageServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $storageService;

    /** @var DuplicateDetector|\PHPUnit\Framework\MockObject\MockObject */
    private $duplicateDetector;

    /** @var ConfigRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configRepository;

    /** @var FileUploadService */
    private $service;

    /** @var string */
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileRepository = $this->createMock(UploadedFileRepositoryInterface::class);
        $this->storageService = $this->createMock(FileStorageServiceInterface::class);
        $this->duplicateDetector = $this->createMock(DuplicateDetector::class);
        $this->configRepository = $this->createMock(ConfigRepositoryInterface::class);

        $this->service = new FileUploadService(
            $this->fileRepository,
            $this->storageService,
            $this->duplicateDetector,
            $this->configRepository
        );

        $this->tempDir = sys_get_temp_dir() . '/ksf_bank_import_upload_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        $_SESSION['wa_current_user'] = new class {
            /** @var string */
            public $username = 'admin';
        };
    }

    protected function tearDown(): void
    {
        unset($_SESSION['wa_current_user']);

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

    public function testUploadStoresTheFileAndSavesTheMetadata(): void
    {
        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::notDuplicate());

        $this->storageService->expects($this->once())
            ->method('store')
            ->willReturn(['filename' => 'stored.qfx', 'path' => '/tmp/stored.qfx']);

        $this->fileRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UploadedFile $file): bool {
                return $file->getFilename() === 'stored.qfx'
                    && $file->getOriginalFilename() === 'statement.qfx'
                    && $file->getFilePath() === '/tmp/stored.qfx'
                    && $file->getUploadUser() === 'admin'
                    && $file->getParserType() === 'qfx'
                    && $file->getBankAccountId() === 3
                    && $file->getNotes() === 'some notes';
            }))
            ->willReturn(21);

        $result = $this->service->upload($this->fileInfo(), 'qfx', 3, false, 'some notes');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(21, $result->getFileId());
        $this->assertSame('stored.qfx', $result->getFilename());
    }

    public function testUploadUsesTheUnknownUserWhenThereIsNoSession(): void
    {
        unset($_SESSION['wa_current_user']);

        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::notDuplicate());
        $this->storageService->method('store')->willReturn(['filename' => 'stored.qfx', 'path' => '/tmp/stored.qfx']);
        $this->fileRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UploadedFile $file): bool {
                return $file->getUploadUser() === 'unknown';
            }))
            ->willReturn(22);

        $this->assertTrue($this->service->upload($this->fileInfo(), 'qfx')->isSuccess());
    }

    public function testUploadSkipsTheDuplicateCheckWhenTheUploadIsForced(): void
    {
        $this->duplicateDetector->expects($this->never())->method('detect');
        $this->storageService->method('store')->willReturn(['filename' => 'stored.qfx', 'path' => '/tmp/stored.qfx']);
        $this->fileRepository->method('save')->willReturn(23);

        $result = $this->service->upload($this->fileInfo(), 'qfx', null, true);

        $this->assertTrue($result->isSuccess());
    }

    public function testUploadReusesTheExistingFileWhenDuplicatesAreAllowed(): void
    {
        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::allowDuplicate($this->existingFile()));
        $this->configRepository->method('get')->with('duplicate_action', 'warn')->willReturn('allow');
        $this->storageService->expects($this->never())->method('store');

        $result = $this->service->upload($this->fileInfo(), 'qfx');

        $this->assertTrue($result->isReused());
        $this->assertSame(5, $result->getFileId());
        $this->assertStringContainsString('Using existing file', $result->getMessage());
    }

    public function testUploadWarnsAboutADuplicateAndAllowsForcing(): void
    {
        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::warnDuplicate($this->existingFile()));
        $this->configRepository->method('get')->with('duplicate_action', 'warn')->willReturn('warn');
        $this->storageService->expects($this->never())->method('store');

        $result = $this->service->upload($this->fileInfo(), 'qfx');

        $this->assertTrue($result->isDuplicate());
        $this->assertTrue($result->allowForce());
        $this->assertStringContainsString('Warning: This file appears to be a duplicate', $result->getMessage());
    }

    public function testUploadBlocksADuplicate(): void
    {
        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::blockDuplicate($this->existingFile()));
        $this->configRepository->method('get')->with('duplicate_action', 'warn')->willReturn('block');
        $this->storageService->expects($this->never())->method('store');

        $result = $this->service->upload($this->fileInfo(), 'qfx');

        $this->assertTrue($result->isDuplicate());
        $this->assertFalse($result->allowForce());
        $this->assertStringContainsString('Error: Duplicate file detected', $result->getMessage());
    }

    public function testUploadFailsWhenTheFileCannotBeStored(): void
    {
        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::notDuplicate());
        $this->storageService->method('store')->willThrowException(new \RuntimeException('disk full'));

        $result = $this->service->upload($this->fileInfo(), 'qfx');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('Failed to store file: disk full', $result->getMessage());
    }

    public function testUploadRemovesTheStoredFileWhenTheMetadataCannotBeSaved(): void
    {
        $this->duplicateDetector->method('detect')->willReturn(DuplicateResult::notDuplicate());
        $this->storageService->method('store')->willReturn(['filename' => 'stored.qfx', 'path' => '/tmp/stored.qfx']);

        $this->fileRepository->method('save')->willThrowException(new \RuntimeException('db down'));

        $this->storageService->expects($this->once())
            ->method('delete')
            ->with('/tmp/stored.qfx')
            ->willReturn(true);

        $result = $this->service->upload($this->fileInfo(), 'qfx');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('Failed to save file metadata: db down', $result->getMessage());
    }

    public function testLinkToStatementsDelegatesToTheRepository(): void
    {
        $this->fileRepository->expects($this->once())
            ->method('linkToStatements')
            ->with(7, [10, 11])
            ->willReturn(true);

        $this->assertTrue($this->service->linkToStatements(7, [10, 11]));
    }

    public function testDeleteRemovesTheFileFromDiskAndTheDatabase(): void
    {
        $this->fileRepository->method('findById')->with(7)->willReturn($this->existingFile());
        $this->storageService->expects($this->once())
            ->method('delete')
            ->with('/tmp/existing.qfx')
            ->willReturn(true);
        $this->fileRepository->expects($this->once())
            ->method('delete')
            ->with(7)
            ->willReturn(true);

        $this->assertTrue($this->service->delete(7));
    }

    public function testDeleteReturnsFalseForAnUnknownFile(): void
    {
        $this->fileRepository->method('findById')->with(7)->willReturn(null);
        $this->storageService->expects($this->never())->method('delete');

        $this->assertFalse($this->service->delete(7));
    }

    public function testGetFileReturnsTheStoredEntity(): void
    {
        $this->fileRepository->method('findById')->with(7)->willReturn($this->existingFile());

        $file = $this->service->getFile(7);

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame(5, $file->getId());
    }

    public function testGetFileContentsReturnsTheFileBody(): void
    {
        $this->fileRepository->method('findById')->with(7)->willReturn($this->existingFile());
        $this->storageService->method('getContents')->with('/tmp/existing.qfx')->willReturn('file body');

        $this->assertSame('file body', $this->service->getFileContents(7));
    }

    public function testGetFileContentsIsNullForAnUnknownFile(): void
    {
        $this->fileRepository->method('findById')->with(7)->willReturn(null);
        $this->storageService->expects($this->never())->method('getContents');

        $this->assertNull($this->service->getFileContents(7));
    }

    public function testGetFileContentsIsNullWhenTheFileCannotBeRead(): void
    {
        $this->fileRepository->method('findById')->with(7)->willReturn($this->existingFile());
        $this->storageService->method('getContents')->willThrowException(new \RuntimeException('gone'));

        $this->assertNull($this->service->getFileContents(7));
    }

    public function testListFilesDelegatesToTheRepository(): void
    {
        $this->fileRepository->expects($this->once())
            ->method('findAll')
            ->with(['parser_type' => 'qfx'], 10, 20)
            ->willReturn([$this->existingFile()]);

        $this->assertCount(1, $this->service->listFiles(['parser_type' => 'qfx'], 10, 20));
    }

    public function testCountFilesDelegatesToTheRepository(): void
    {
        $this->fileRepository->expects($this->once())
            ->method('count')
            ->with([])
            ->willReturn(4);

        $this->assertSame(4, $this->service->countFiles());
    }

    public function testGetStatisticsDelegatesToTheRepository(): void
    {
        $this->fileRepository->expects($this->once())
            ->method('getStatistics')
            ->willReturn(['total_files' => 2]);

        $this->assertSame(['total_files' => 2], $this->service->getStatistics());
    }

    public function testCreateBuildsTheServiceWithTheInjectedDependencies(): void
    {
        $service = FileUploadService::create(
            $this->fileRepository,
            $this->storageService,
            $this->duplicateDetector,
            $this->configRepository
        );

        $this->assertInstanceOf(FileUploadService::class, $service);
    }

    private function fileInfo(): FileInfo
    {
        $path = $this->tempDir . '/statement.qfx';
        file_put_contents($path, 'body');

        return new FileInfo('statement.qfx', $path, 4, 'application/x-qfx');
    }

    private function existingFile(): UploadedFile
    {
        return new UploadedFile(
            5,
            'existing.qfx',
            'statement.qfx',
            '/tmp/existing.qfx',
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
