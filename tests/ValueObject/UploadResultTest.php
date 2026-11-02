<?php

namespace Ksfraser\FaBankImport\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\ValueObject\UploadResult;

/**
 * Unit tests for UploadResult value object
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class UploadResultTest extends TestCase
{
    /**
     * Test success() factory method
     */
    public function testSuccess(): void
    {
        $result = UploadResult::success(123, 'unique_file.qfx', 'Upload successful');
        
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Upload successful', $result->getMessage());
        $this->assertEquals(123, $result->getFileId());
        $this->assertEquals('unique_file.qfx', $result->getFilename());
        $this->assertEquals('success', $result->getType());
        $this->assertFalse($result->isDuplicate());
        $this->assertFalse($result->isReused());
        $this->assertFalse($result->allowForce());
    }
    
    /**
     * Test error() factory method
     */
    public function testError(): void
    {
        $result = UploadResult::error('Upload failed: disk full');
        
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Upload failed: disk full', $result->getMessage());
        $this->assertNull($result->getFileId());
        $this->assertNull($result->getFilename());
        $this->assertEquals('error', $result->getType());
        $this->assertFalse($result->isDuplicate());
        $this->assertFalse($result->isReused());
        $this->assertFalse($result->allowForce());
    }
    
    /**
     * Test duplicate() factory method with force allowed
     */
    public function testDuplicateWithForceAllowed(): void
    {
        $result = UploadResult::duplicate('Duplicate detected', true);
        
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Duplicate detected', $result->getMessage());
        $this->assertNull($result->getFileId());
        $this->assertNull($result->getFilename());
        $this->assertEquals('duplicate', $result->getType());
        $this->assertTrue($result->isDuplicate());
        $this->assertFalse($result->isReused());
        $this->assertTrue($result->allowForce());
    }
    
    /**
     * Test duplicate() factory method with force not allowed
     */
    public function testDuplicateWithForceNotAllowed(): void
    {
        $result = UploadResult::duplicate('Duplicate blocked', false);
        
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Duplicate blocked', $result->getMessage());
        $this->assertNull($result->getFileId());
        $this->assertNull($result->getFilename());
        $this->assertEquals('duplicate', $result->getType());
        $this->assertTrue($result->isDuplicate());
        $this->assertFalse($result->isReused());
        $this->assertFalse($result->allowForce());
    }
    
    /**
     * Test reused() factory method
     */
    public function testReused(): void
    {
        $result = UploadResult::reused(456, 'Using existing file');
        
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Using existing file', $result->getMessage());
        $this->assertEquals(456, $result->getFileId());
        $this->assertNull($result->getFilename());
        $this->assertEquals('reused', $result->getType());
        $this->assertFalse($result->isDuplicate());
        $this->assertTrue($result->isReused());
        $this->assertFalse($result->allowForce());
    }
    
    /**
     * Test toArray() method for success result
     */
    public function testToArraySuccess(): void
    {
        $result = UploadResult::success(789, 'test.qfx', 'Success message');
        $array = $result->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals([
            'success' => true,
            'message' => 'Success message',
            'type' => 'success',
            'fileId' => 789,
            'filename' => 'test.qfx',
            'allowForce' => false
        ], $array);
    }
    
    /**
     * Test toArray() method for error result
     */
    public function testToArrayError(): void
    {
        $result = UploadResult::error('Error message');
        $array = $result->toArray();
        
        $this->assertEquals([
            'success' => false,
            'message' => 'Error message',
            'type' => 'error',
            'fileId' => null,
            'filename' => null,
            'allowForce' => false
        ], $array);
    }
    
    /**
     * Test toArray() method for duplicate result
     */
    public function testToArrayDuplicate(): void
    {
        $result = UploadResult::duplicate('Duplicate warning', true);
        $array = $result->toArray();
        
        $this->assertEquals([
            'success' => false,
            'message' => 'Duplicate warning',
            'type' => 'duplicate',
            'fileId' => null,
            'filename' => null,
            'allowForce' => true
        ], $array);
    }
    
    /**
     * Test that each result type has appropriate success flag
     */
    public function testSuccessFlagConsistency(): void
    {
        // Success type should have success=true
        $successResult = UploadResult::success(1, 'file.qfx', 'msg');
        $this->assertTrue($successResult->isSuccess());
        
        // Reused type should have success=true (file available)
        $reusedResult = UploadResult::reused(1, 'msg');
        $this->assertTrue($reusedResult->isSuccess());
        
        // Error type should have success=false
        $errorResult = UploadResult::error('msg');
        $this->assertFalse($errorResult->isSuccess());
        
        // Duplicate type should have success=false (needs user action)
        $duplicateResult = UploadResult::duplicate('msg', true);
        $this->assertFalse($duplicateResult->isSuccess());
    }
    
    /**
     * Test immutability - no setters
     */
    public function testImmutability(): void
    {
        $result = UploadResult::success(1, 'file.qfx', 'msg');
        
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
        $reflection = new \ReflectionClass(UploadResult::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $this->assertCount(0, $properties, 'Value object should not have public properties');
    }
    
    /**
     * Test constructor is private
     */
    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(UploadResult::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertTrue($constructor->isPrivate(),
            'Constructor should be private to enforce factory methods');
    }
}
