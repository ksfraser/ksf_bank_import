<?php

namespace Ksfraser\FaBankImport\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\ValueObject\DuplicateResult;
use Ksfraser\FaBankImport\Entity\UploadedFile;

/**
 * Unit tests for DuplicateResult value object
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DuplicateResultTest extends TestCase
{
    /**
     * Helper to create a mock UploadedFile
     */
    private function createMockUploadedFile(): UploadedFile
    {
        return new UploadedFile(
            1,
            'stored_file.qfx',
            'original_file.qfx',
            '/path/to/stored_file.qfx',
            1024,
            'application/x-ofx',
            new \DateTime('2025-01-15 10:30:00'),
            'testuser',
            'qfx',
            1,
            3,
            'Test notes'
        );
    }
    
    /**
     * Test notDuplicate() factory method
     */
    public function testNotDuplicate(): void
    {
        $result = DuplicateResult::notDuplicate();
        
        $this->assertInstanceOf(DuplicateResult::class, $result);
        $this->assertFalse($result->isDuplicate());
        $this->assertFalse($result->shouldBlock());
        $this->assertFalse($result->shouldWarn());
        $this->assertTrue($result->shouldAllow());
        $this->assertNull($result->getExistingFile());
        $this->assertEquals('none', $result->getAction());
    }
    
    /**
     * Test allowDuplicate() factory method
     */
    public function testAllowDuplicate(): void
    {
        $existingFile = $this->createMockUploadedFile();
        $result = DuplicateResult::allowDuplicate($existingFile);
        
        $this->assertInstanceOf(DuplicateResult::class, $result);
        $this->assertTrue($result->isDuplicate());
        $this->assertFalse($result->shouldBlock());
        $this->assertFalse($result->shouldWarn());
        $this->assertTrue($result->shouldAllow());
        $this->assertSame($existingFile, $result->getExistingFile());
        $this->assertEquals('allow', $result->getAction());
    }
    
    /**
     * Test warnDuplicate() factory method
     */
    public function testWarnDuplicate(): void
    {
        $existingFile = $this->createMockUploadedFile();
        $result = DuplicateResult::warnDuplicate($existingFile);
        
        $this->assertInstanceOf(DuplicateResult::class, $result);
        $this->assertTrue($result->isDuplicate());
        $this->assertFalse($result->shouldBlock());
        $this->assertTrue($result->shouldWarn());
        $this->assertFalse($result->shouldAllow());
        $this->assertSame($existingFile, $result->getExistingFile());
        $this->assertEquals('warn', $result->getAction());
    }
    
    /**
     * Test blockDuplicate() factory method
     */
    public function testBlockDuplicate(): void
    {
        $existingFile = $this->createMockUploadedFile();
        $result = DuplicateResult::blockDuplicate($existingFile);
        
        $this->assertInstanceOf(DuplicateResult::class, $result);
        $this->assertTrue($result->isDuplicate());
        $this->assertTrue($result->shouldBlock());
        $this->assertFalse($result->shouldWarn());
        $this->assertFalse($result->shouldAllow());
        $this->assertSame($existingFile, $result->getExistingFile());
        $this->assertEquals('block', $result->getAction());
    }
    
    /**
     * Test that only one action flag is true at a time
     */
    public function testMutualExclusiveActions(): void
    {
        $existingFile = $this->createMockUploadedFile();
        
        // Test each result type
        $results = [
            DuplicateResult::notDuplicate(),
            DuplicateResult::allowDuplicate($existingFile),
            DuplicateResult::warnDuplicate($existingFile),
            DuplicateResult::blockDuplicate($existingFile),
        ];
        
        foreach ($results as $result) {
            $trueCount = 0;
            if ($result->shouldAllow()) $trueCount++;
            if ($result->shouldWarn()) $trueCount++;
            if ($result->shouldBlock()) $trueCount++;
            
            $this->assertEquals(1, $trueCount, 
                'Exactly one action should be true for action: ' . $result->getAction());
        }
    }
    
    /**
     * Test immutability - no setters
     */
    public function testImmutability(): void
    {
        $result = DuplicateResult::notDuplicate();
        
        $reflection = new \ReflectionClass($result);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $this->assertStringStartsNotWith('set', $method->getName(),
                'Value object should not have setter methods');
        }
    }
    
    /**
     * Test properties are private
     */
    public function testPropertiesArePrivate(): void
    {
        $reflection = new \ReflectionClass(DuplicateResult::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $this->assertCount(0, $properties, 'Value object should not have public properties');
    }
    
    /**
     * Test constructor is private (only factory methods allowed)
     */
    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(DuplicateResult::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertTrue($constructor->isPrivate(), 
            'Constructor should be private to enforce factory methods');
    }
}
