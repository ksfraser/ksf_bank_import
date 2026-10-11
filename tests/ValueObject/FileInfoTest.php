<?php

namespace Ksfraser\FaBankImport\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * Unit tests for FileInfo value object
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class FileInfoTest extends TestCase
{
    /**
     * Test successful creation from valid data
     */
    public function testConstructorWithValidData(): void
    {
        $fileInfo = new FileInfo(
            'test_file.qfx',
            '/tmp/phpABC123',
            1024,
            'application/x-ofx'
        );
        
        $this->assertInstanceOf(FileInfo::class, $fileInfo);
        $this->assertEquals('test_file.qfx', $fileInfo->getOriginalFilename());
        $this->assertEquals('/tmp/phpABC123', $fileInfo->getTmpPath());
        $this->assertEquals(1024, $fileInfo->getSize());
        $this->assertEquals('application/x-ofx', $fileInfo->getMimeType());
    }
    
    /**
     * Test validation fails with empty filename
     */
    public function testConstructorRejectsEmptyFilename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filename cannot be empty');
        
        new FileInfo('', '/tmp/phpABC123', 1024, 'application/x-ofx');
    }
    
    /**
     * Test validation fails with filename too long
     */
    public function testConstructorRejectsLongFilename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filename too long');
        
        $longFilename = str_repeat('a', 256) . '.qfx';
        new FileInfo($longFilename, '/tmp/phpABC123', 1024, 'application/x-ofx');
    }
    
    /**
     * Test validation fails with zero size
     */
    public function testConstructorRejectsZeroSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size must be positive');
        
        new FileInfo('test.qfx', '/tmp/phpABC123', 0, 'application/x-ofx');
    }
    
    /**
     * Test validation fails with negative size
     */
    public function testConstructorRejectsNegativeSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size must be positive');
        
        new FileInfo('test.qfx', '/tmp/phpABC123', -100, 'application/x-ofx');
    }
    
    /**
     * Test validation fails with size too large (>100MB)
     */
    public function testConstructorRejectsTooLargeSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds maximum');
        
        $sizeTooLarge = 101 * 1024 * 1024; // 101 MB
        new FileInfo('test.qfx', '/tmp/phpABC123', $sizeTooLarge, 'application/x-ofx');
    }
    
    /**
     * Test getExtension() method
     */
    public function testGetExtension(): void
    {
        $fileInfo = new FileInfo('test_file.qfx', '/tmp/phpABC123', 1024, 'application/x-ofx');
        $this->assertEquals('qfx', $fileInfo->getExtension());
        
        // Extension is normalized to lowercase for consistency
        $fileInfo2 = new FileInfo('statement.MT940', '/tmp/phpABC123', 1024, 'text/plain');
        $this->assertEquals('mt940', $fileInfo2->getExtension());
        
        $fileInfo3 = new FileInfo('noextension', '/tmp/phpABC123', 1024, 'text/plain');
        $this->assertEquals('', $fileInfo3->getExtension());
    }
    
    /**
     * Test getBasename() method
     */
    public function testGetBasename(): void
    {
        $fileInfo = new FileInfo('test_file.qfx', '/tmp/phpABC123', 1024, 'application/x-ofx');
        $this->assertEquals('test_file', $fileInfo->getBasename());
        
        $fileInfo2 = new FileInfo('statement.backup.csv', '/tmp/phpABC123', 1024, 'text/csv');
        $this->assertEquals('statement.backup', $fileInfo2->getBasename());
        
        $fileInfo3 = new FileInfo('noextension', '/tmp/phpABC123', 1024, 'text/plain');
        $this->assertEquals('noextension', $fileInfo3->getBasename());
    }
    
    /**
     * Test getMd5Hash() with actual file
     */
    public function testGetMd5HashWithRealFile(): void
    {
        // Create temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $testContent = 'Test file content for MD5 hash';
        file_put_contents($tmpFile, $testContent);
        
        $expectedHash = md5($testContent);
        
        $fileInfo = new FileInfo('test.txt', $tmpFile, strlen($testContent), 'text/plain');
        $actualHash = $fileInfo->getMd5Hash();
        
        $this->assertEquals($expectedHash, $actualHash);
        
        // Cleanup
        unlink($tmpFile);
    }
    
    /**
     * Test getMd5Hash() throws exception for non-existent file
     */
    public function testGetMd5HashThrowsExceptionForNonExistentFile(): void
    {
        $fileInfo = new FileInfo('test.txt', '/non/existent/file', 1024, 'text/plain');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to calculate MD5 hash');
        
        $fileInfo->getMd5Hash();
    }
    
    /**
     * Test fromUpload() factory method with valid upload
     */
    public function testFromUploadWithValidData(): void
    {
        $uploadData = [
            'name' => 'bank_statement.qfx',
            'tmp_name' => '/tmp/phpXYZ789',
            'size' => 2048,
            'type' => 'application/x-ofx',
            'error' => UPLOAD_ERR_OK
        ];
        
        $fileInfo = FileInfo::fromUpload($uploadData);
        
        $this->assertInstanceOf(FileInfo::class, $fileInfo);
        $this->assertEquals('bank_statement.qfx', $fileInfo->getOriginalFilename());
        $this->assertEquals('/tmp/phpXYZ789', $fileInfo->getTmpPath());
        $this->assertEquals(2048, $fileInfo->getSize());
        $this->assertEquals('application/x-ofx', $fileInfo->getMimeType());
    }
    
    /**
     * Test fromUpload() fails with upload error
     */
    public function testFromUploadThrowsExceptionOnUploadError(): void
    {
        $uploadData = [
            'name' => 'test.qfx',
            'tmp_name' => '',
            'size' => 0,
            'type' => '',
            'error' => UPLOAD_ERR_NO_FILE
        ];
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No file uploaded');
        
        FileInfo::fromUpload($uploadData);
    }
    
    /**
     * Test fromUpload() fails with file too large error
     */
    public function testFromUploadThrowsExceptionOnFileTooLarge(): void
    {
        $uploadData = [
            'name' => 'huge.qfx',
            'tmp_name' => '',
            'size' => 0,
            'type' => '',
            'error' => UPLOAD_ERR_INI_SIZE
        ];
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds maximum size');
        
        FileInfo::fromUpload($uploadData);
    }
    
    /**
     * Test fromUpload() fails with partial upload error
     */
    public function testFromUploadThrowsExceptionOnPartialUpload(): void
    {
        $uploadData = [
            'name' => 'test.qfx',
            'tmp_name' => '/tmp/partial',
            'size' => 100,
            'type' => 'application/x-ofx',
            'error' => UPLOAD_ERR_PARTIAL
        ];
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('partially uploaded');
        
        FileInfo::fromUpload($uploadData);
    }
    
    /**
     * Test immutability - value object should not have setters
     */
    public function testImmutability(): void
    {
        $fileInfo = new FileInfo('test.qfx', '/tmp/test', 1024, 'application/x-ofx');
        
        // Should not have any public setter methods
        $reflection = new \ReflectionClass($fileInfo);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $this->assertStringStartsNotWith('set', $method->getName(), 
                'Value object should not have setter methods');
        }
    }
    
    /**
     * Test that properties are private and cannot be modified
     */
    public function testPropertiesArePrivate(): void
    {
        $reflection = new \ReflectionClass(FileInfo::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $this->assertCount(0, $properties, 'Value object should not have public properties');
    }
}
