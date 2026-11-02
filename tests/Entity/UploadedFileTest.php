<?php

namespace Ksfraser\FaBankImport\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Entity\UploadedFile;

/**
 * Unit tests for UploadedFile entity
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class UploadedFileTest extends TestCase
{
    /**
     * Test entity creation with all properties
     */
    public function testConstructorWithAllProperties(): void
    {
        $uploadDate = new \DateTime('2025-01-15 10:30:00');
        
        $file = new UploadedFile(
            123,
            'stored_file.qfx',
            'original_file.qfx',
            '/path/to/stored_file.qfx',
            2048,
            'application/x-ofx',
            $uploadDate,
            'testuser',
            'qfx',
            5,
            3,
            'Q1 2025 import'
        );
        
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals(123, $file->getId());
        $this->assertEquals('stored_file.qfx', $file->getFilename());
        $this->assertEquals('original_file.qfx', $file->getOriginalFilename());
        $this->assertEquals('/path/to/stored_file.qfx', $file->getFilePath());
        $this->assertEquals(2048, $file->getFileSize());
        $this->assertEquals('application/x-ofx', $file->getFileType());
        $this->assertSame($uploadDate, $file->getUploadDate());
        $this->assertEquals('testuser', $file->getUploadUser());
        $this->assertEquals('qfx', $file->getParserType());
        $this->assertEquals(5, $file->getBankAccountId());
        $this->assertEquals(3, $file->getStatementCount());
        $this->assertEquals('Q1 2025 import', $file->getNotes());
    }
    
    /**
     * Test entity creation with minimal properties (nullables)
     */
    public function testConstructorWithMinimalProperties(): void
    {
        $uploadDate = new \DateTime();
        
        $file = new UploadedFile(
            null, // No ID yet (new entity)
            'file.qfx',
            'original.qfx',
            '/path/file.qfx',
            1024,
            'application/x-ofx',
            $uploadDate,
            'user',
            'qfx',
            null, // No bank account
            0,
            null  // No notes
        );
        
        $this->assertNull($file->getId());
        $this->assertNull($file->getBankAccountId());
        $this->assertNull($file->getNotes());
        $this->assertEquals(0, $file->getStatementCount());
    }
    
    /**
     * Test setId() method
     */
    public function testSetId(): void
    {
        $file = new UploadedFile(
            null,
            'file.qfx',
            'original.qfx',
            '/path/file.qfx',
            1024,
            'application/x-ofx',
            new \DateTime(),
            'user',
            'qfx'
        );
        
        $this->assertNull($file->getId());
        
        $file->setId(999);
        $this->assertEquals(999, $file->getId());
    }
    
    /**
     * Test setStatementCount() method
     */
    public function testSetStatementCount(): void
    {
        $file = new UploadedFile(
            1,
            'file.qfx',
            'original.qfx',
            '/path/file.qfx',
            1024,
            'application/x-ofx',
            new \DateTime(),
            'user',
            'qfx',
            null,
            0
        );
        
        $this->assertEquals(0, $file->getStatementCount());
        
        $file->setStatementCount(5);
        $this->assertEquals(5, $file->getStatementCount());
    }
    
    /**
     * Test getFormattedSize() method
     */
    public function testGetFormattedSize(): void
    {
        // Test bytes
        $file1 = new UploadedFile(1, 'f', 'f', '/f', 512, 'text/plain', 
            new \DateTime(), 'user', 'csv');
        $this->assertEquals('512 B', $file1->getFormattedSize());
        
        // Test kilobytes
        $file2 = new UploadedFile(2, 'f', 'f', '/f', 1024, 'text/plain',
            new \DateTime(), 'user', 'csv');
        $this->assertEquals('1.00 KB', $file2->getFormattedSize());
        
        // Test megabytes
        $file3 = new UploadedFile(3, 'f', 'f', '/f', 1048576, 'text/plain',
            new \DateTime(), 'user', 'csv');
        $this->assertEquals('1.00 MB', $file3->getFormattedSize());
        
        // Test larger megabytes
        $file4 = new UploadedFile(4, 'f', 'f', '/f', 5242880, 'text/plain',
            new \DateTime(), 'user', 'csv');
        $this->assertEquals('5.00 MB', $file4->getFormattedSize());
        
        // Test fractional
        $file5 = new UploadedFile(5, 'f', 'f', '/f', 1536, 'text/plain',
            new \DateTime(), 'user', 'csv');
        $this->assertEquals('1.50 KB', $file5->getFormattedSize());
    }
    
    /**
     * Test exists() method with real file
     */
    public function testExistsWithRealFile(): void
    {
        // Create temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'test content');
        
        $file = new UploadedFile(
            1, 'file.qfx', 'original.qfx', $tmpFile, 1024,
            'text/plain', new \DateTime(), 'user', 'qfx'
        );
        
        $this->assertTrue($file->exists());
        
        // Delete file and test again
        unlink($tmpFile);
        $this->assertFalse($file->exists());
    }
    
    /**
     * Test exists() method with non-existent file
     */
    public function testExistsWithNonExistentFile(): void
    {
        $file = new UploadedFile(
            1, 'file.qfx', 'original.qfx', '/non/existent/file.qfx', 1024,
            'text/plain', new \DateTime(), 'user', 'qfx'
        );
        
        $this->assertFalse($file->exists());
    }
    
    /**
     * Test that entity has identity (ID)
     */
    public function testEntityHasIdentity(): void
    {
        $file1 = new UploadedFile(
            1, 'file1.qfx', 'orig1.qfx', '/path1', 1024,
            'text/plain', new \DateTime(), 'user', 'qfx'
        );
        
        $file2 = new UploadedFile(
            2, 'file1.qfx', 'orig1.qfx', '/path1', 1024,
            'text/plain', new \DateTime(), 'user', 'qfx'
        );
        
        // Different IDs = different entities (even if all other properties same)
        $this->assertNotEquals($file1->getId(), $file2->getId());
    }
    
    /**
     * Test that entity is NOT a value object (has setters)
     */
    public function testEntityHasSetters(): void
    {
        $reflection = new \ReflectionClass(UploadedFile::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $setterCount = 0;
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'set') === 0) {
                $setterCount++;
            }
        }
        
        $this->assertGreaterThan(0, $setterCount, 
            'Entity should have setter methods (unlike value objects)');
    }
}
