<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\ValueObject;

use Ksfraser\FaBankImport\ValueObject\FileInfo;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the file information value object.
 *
 * @see \Ksfraser\FaBankImport\ValueObject\FileInfo
 */
final class FileInfoTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public function invalidUploadProvider(): array
    {
        return [
            'no name' => [['tmp_name' => '/tmp/x', 'size' => 1, 'type' => 'text/plain']],
            'no temporary name' => [['name' => 'x', 'size' => 1, 'type' => 'text/plain']],
            'no size' => [['name' => 'x', 'tmp_name' => '/tmp/x', 'type' => 'text/plain']],
            'no type' => [['name' => 'x', 'tmp_name' => '/tmp/x', 'size' => 1]],
        ];
    }

    /**
     * @dataProvider invalidUploadProvider
     * @param array $fileData The upload data.
     * @return void
     */
    public function testInvalidUploadDataIsRejected(array $fileData): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file upload data');

        FileInfo::fromUpload($fileData);
    }

    /**
     * The upload error codes and the message they are reported with.
     *
     * @return array<string, array{0: int, 1: string}>
     */
    public function uploadErrorProvider(): array
    {
        return [
            'no temporary folder' => [UPLOAD_ERR_NO_TMP_DIR, 'Missing temporary folder'],
            'cannot write' => [UPLOAD_ERR_CANT_WRITE, 'Failed to write file to disk'],
            'stopped by extension' => [UPLOAD_ERR_EXTENSION, 'File upload stopped by extension'],
            'unknown' => [999, 'Unknown upload error: 999'],
        ];
    }

    /**
     * @dataProvider uploadErrorProvider
     * @param int $errorCode The upload error code.
     * @param string $message The message the code is reported with.
     * @return void
     */
    public function testAnUploadErrorIsReported(int $errorCode, string $message): void
    {
        $fileData = [
            'name' => 'statement.qfx',
            'tmp_name' => '/tmp/statement.qfx',
            'size' => 10,
            'type' => 'application/octet-stream',
            'error' => $errorCode,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        FileInfo::fromUpload($fileData);
    }

    /**
     * @return void
     */
    public function testAnUploadIsAccepted(): void
    {
        $fileData = [
            'name' => 'statement.qfx',
            'tmp_name' => '/tmp/statement.qfx',
            'size' => 10,
            'type' => 'application/octet-stream',
            'error' => UPLOAD_ERR_OK,
        ];

        $fileInfo = FileInfo::fromUpload($fileData);

        $this->assertSame('statement.qfx', $fileInfo->getOriginalFilename());
        $this->assertSame('/tmp/statement.qfx', $fileInfo->getTmpPath());
        $this->assertSame(10, $fileInfo->getSize());
        $this->assertSame('application/octet-stream', $fileInfo->getMimeType());
        $this->assertSame('qfx', $fileInfo->getExtension());
        $this->assertSame('statement', $fileInfo->getBasename());
    }
}
