<?php

namespace Ksfraser\FaBankImport\Tests\Service;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\FileStorageService;
use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * Unit tests for FileStorageService
 * 
 * Tests file system operations with real temporary files
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class FileStorageServiceTest extends TestCase
{
    private string $testDir;
    private FileStorageService $service;
    
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        // Create temporary test directory
        $this->testDir = sys_get_temp_dir() . '/fa_test_' . uniqid();
        mkdir($this->testDir, 0750, true);
        
        // Create service with test directory
        $this->service = new FileStorageService($this->testDir);
    }
    
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Remove test directory and all files
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->testDir);
        }
    }
    
    /**
     * Test getStorageDirectory()
     */
    public function testGetStorageDirectory(): void
    {
        $this->assertEquals($this->testDir, $this->service->getStorageDirectory());
    }
    
    /**
     * Test ensureStorageDirectoryExists() creates directory
     */
    public function testEnsureStorageDirectoryExists(): void
    {
        $newDir = sys_get_temp_dir() . '/fa_test_new_' . uniqid();
        $service = new FileStorageService($newDir);
        
        $this->assertFalse(is_dir($newDir));
        
        $service->ensureStorageDirectoryExists();
        
        $this->assertTrue(is_dir($newDir));
        
        // Cleanup
        @unlink($newDir . '/.htaccess');
        @rmdir($newDir);
    }
    
    /**
     * Test ensureStorageDirectoryExists() creates .htaccess
     */
    public function testEnsureStorageDirectoryCreatesHtaccess(): void
    {
        $newDir = sys_get_temp_dir() . '/fa_test_htaccess_' . uniqid();
        $service = new FileStorageService($newDir);
        
        $service->ensureStorageDirectoryExists();
        
        $htaccessPath = $newDir . '/.htaccess';
        $this->assertTrue(file_exists($htaccessPath));
        
        $content = file_get_contents($htaccessPath);
        $this->assertStringContainsString('Deny from all', $content);
        
        // Cleanup
        @unlink($htaccessPath);
        @rmdir($newDir);
    }
    
    /**
     * Test store() saves file with unique filename
     */
    public function testStoreCreatesUniqueFilename(): void
    {
        // Create temporary upload file
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmpFile, 'Test file content');
        
        $fileInfo = new FileInfo('test_file.qfx', $tmpFile, 17, 'application/x-ofx');
        
        $result = $this->service->store($fileInfo, 'qfx');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('path', $result);
        
        // Check filename format: QFX_BASENAME_TIMESTAMP_RANDOM.qfx
        $filename = $result['filename'];
        $this->assertStringStartsWith('QFX_', $filename);
        $this->assertStringEndsWith('.qfx', $filename);
        
        // Check file exists at path
        $this->assertTrue(file_exists($result['path']));
        $this->assertEquals('Test file content', file_get_contents($result['path']));
        
        // Cleanup
        @unlink($result['path']);
    }
    
    /**
     * Test store() creates storage directory if missing
     */
    public function testStoreCreatesDirectoryIfMissing(): void
    {
        $newDir = sys_get_temp_dir() . '/fa_test_autocreate_' . uniqid();
        $service = new FileStorageService($newDir);
        
        $this->assertFalse(is_dir($newDir));
        
        // Create temp file
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmpFile, 'Test');
        
        $fileInfo = new FileInfo('file.qfx', $tmpFile, 4, 'application/x-ofx');
        $result = $service->store($fileInfo, 'qfx');
        
        $this->assertTrue(is_dir($newDir));
        $this->assertTrue(file_exists($result['path']));
        
        // Cleanup
        @unlink($result['path']);
        @unlink($newDir . '/.htaccess');
        @rmdir($newDir);
    }
    
    /**
     * Test exists() method
     */
    public function testExists(): void
    {
        // Create test file
        $testFile = $this->testDir . '/test_exists.txt';
        file_put_contents($testFile, 'test');
        
        $this->assertTrue($this->service->exists($testFile));
        
        unlink($testFile);
        
        $this->assertFalse($this->service->exists($testFile));
    }
    
    /**
     * Test exists() returns false for directory
     */
    public function testExistsReturnsFalseForDirectory(): void
    {
        $this->assertFalse($this->service->exists($this->testDir));
    }
    
    /**
     * Test delete() method
     */
    public function testDelete(): void
    {
        // Create test file
        $testFile = $this->testDir . '/test_delete.txt';
        file_put_contents($testFile, 'to be deleted');
        
        $this->assertTrue(file_exists($testFile));
        
        $result = $this->service->delete($testFile);
        
        $this->assertTrue($result);
        $this->assertFalse(file_exists($testFile));
    }
    
    /**
     * Test delete() returns false for non-existent file
     */
    public function testDeleteReturnsFalseForNonExistentFile(): void
    {
        $result = $this->service->delete($this->testDir . '/does_not_exist.txt');
        
        $this->assertFalse($result);
    }
    
    /**
     * Test getContents() method
     */
    public function testGetContents(): void
    {
        $testFile = $this->testDir . '/test_content.txt';
        $content = 'Test file contents';
        file_put_contents($testFile, $content);
        
        $result = $this->service->getContents($testFile);
        
        $this->assertEquals($content, $result);
        
        // Cleanup
        unlink($testFile);
    }
    
    /**
     * Test getContents() throws exception for non-existent file
     */
    public function testGetContentsThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');
        
        $this->service->getContents($this->testDir . '/does_not_exist.txt');
    }
    
    /**
     * Test getFileSize() method
     */
    public function testGetFileSize(): void
    {
        $testFile = $this->testDir . '/test_size.txt';
        $content = 'Exactly 25 characters!!';
        file_put_contents($testFile, $content);
        
        $size = $this->service->getFileSize($testFile);
        
        $this->assertEquals(23, $size);
        
        // Cleanup
        unlink($testFile);
    }
    
    /**
     * Test getFileSize() throws exception for non-existent file
     */
    public function testGetFileSizeThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');
        
        $this->service->getFileSize($this->testDir . '/does_not_exist.txt');
    }
    
    /**
     * Test getModificationTime() method
     */
    public function testGetModificationTime(): void
    {
        $testFile = $this->testDir . '/test_mtime.txt';
        file_put_contents($testFile, 'test');
        
        $mtime = $this->service->getModificationTime($testFile);
        
        $this->assertIsInt($mtime);
        $this->assertGreaterThan(0, $mtime);
        
        // Should be recent (within last minute)
        $this->assertGreaterThan(time() - 60, $mtime);
        
        // Cleanup
        unlink($testFile);
    }
    
    /**
     * Test copy() method
     */
    public function testCopy(): void
    {
        $sourceFile = $this->testDir . '/source.txt';
        $destFile = $this->testDir . '/destination.txt';
        
        $content = 'Content to copy';
        file_put_contents($sourceFile, $content);
        
        $result = $this->service->copy($sourceFile, $destFile);
        
        $this->assertTrue($result);
        $this->assertTrue(file_exists($destFile));
        $this->assertEquals($content, file_get_contents($destFile));
        
        // Cleanup
        unlink($sourceFile);
        unlink($destFile);
    }
    
    /**
     * Test copy() returns false for non-existent source
     */
    public function testCopyReturnsFalseForNonExistentSource(): void
    {
        $result = $this->service->copy(
            $this->testDir . '/does_not_exist.txt',
            $this->testDir . '/dest.txt'
        );
        
        $this->assertFalse($result);
    }
    
    /**
     * Test that multiple stores create unique filenames
     */
    public function testMultipleStoresCreateUniqueFilenames(): void
    {
        $filenames = [];
        
        for ($i = 0; $i < 3; $i++) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
            file_put_contents($tmpFile, "Test $i");
            
            $fileInfo = new FileInfo('test.qfx', $tmpFile, 6, 'application/x-ofx');
            $result = $this->service->store($fileInfo, 'qfx');
            
            $filenames[] = $result['filename'];
            
            // Cleanup
            @unlink($result['path']);
        }
        
        // All filenames should be unique
        $this->assertCount(3, array_unique($filenames));
    }
}
