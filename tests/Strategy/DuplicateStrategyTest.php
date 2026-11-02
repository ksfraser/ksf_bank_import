<?php

namespace Ksfraser\FaBankImport\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Strategy\AllowDuplicateStrategy;
use Ksfraser\FaBankImport\Strategy\WarnDuplicateStrategy;
use Ksfraser\FaBankImport\Strategy\BlockDuplicateStrategy;
use Ksfraser\FaBankImport\Strategy\DuplicateStrategyFactory;
use Ksfraser\FaBankImport\ValueObject\DuplicateResult;
use Ksfraser\FaBankImport\Entity\UploadedFile;

/**
 * Unit tests for Duplicate Strategies
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DuplicateStrategyTest extends TestCase
{
    /**
     * Helper to create mock uploaded file
     */
    private function createMockFile(): UploadedFile
    {
        return new UploadedFile(
            42,
            'stored.qfx',
            'original.qfx',
            '/path/to/stored.qfx',
            1024,
            'application/x-ofx',
            new \DateTime('2025-01-15 10:30:00'),
            'testuser',
            'qfx',
            1,
            3,
            'Test file'
        );
    }
    
    /**
     * Test AllowDuplicateStrategy
     */
    public function testAllowDuplicateStrategy(): void
    {
        $strategy = new AllowDuplicateStrategy();
        
        $this->assertEquals('allow', $strategy->getName());
        
        $existingFile = $this->createMockFile();
        $duplicateResult = DuplicateResult::allowDuplicate($existingFile);
        
        $result = $strategy->handle($duplicateResult);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('reused', $result['action']);
        $this->assertEquals(42, $result['existingFileId']);
        $this->assertStringContainsString('existing file', strtolower($result['message']));
    }
    
    /**
     * Test WarnDuplicateStrategy
     */
    public function testWarnDuplicateStrategy(): void
    {
        $strategy = new WarnDuplicateStrategy();
        
        $this->assertEquals('warn', $strategy->getName());
        
        $existingFile = $this->createMockFile();
        $duplicateResult = DuplicateResult::warnDuplicate($existingFile);
        
        $result = $strategy->handle($duplicateResult);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('warn', $result['action']);
        $this->assertEquals(42, $result['existingFileId']);
        $this->assertTrue($result['allowForce']);
        $this->assertStringContainsString('warning', strtolower($result['message']));
        $this->assertStringContainsString('testuser', $result['message']);
        $this->assertStringContainsString('2025-01-15', $result['message']);
    }
    
    /**
     * Test BlockDuplicateStrategy
     */
    public function testBlockDuplicateStrategy(): void
    {
        $strategy = new BlockDuplicateStrategy();
        
        $this->assertEquals('block', $strategy->getName());
        
        $existingFile = $this->createMockFile();
        $duplicateResult = DuplicateResult::blockDuplicate($existingFile);
        
        $result = $strategy->handle($duplicateResult);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('block', $result['action']);
        $this->assertEquals(42, $result['existingFileId']);
        $this->assertFalse($result['allowForce']);
        $this->assertStringContainsString('error', strtolower($result['message']));
        $this->assertStringContainsString('not allowed', strtolower($result['message']));
    }
    
    /**
     * Test DuplicateStrategyFactory creates correct strategies
     */
    public function testFactoryCreatesAllowStrategy(): void
    {
        $strategy = DuplicateStrategyFactory::create('allow');
        
        $this->assertInstanceOf(AllowDuplicateStrategy::class, $strategy);
    }
    
    public function testFactoryCreatesWarnStrategy(): void
    {
        $strategy = DuplicateStrategyFactory::create('warn');
        
        $this->assertInstanceOf(WarnDuplicateStrategy::class, $strategy);
    }
    
    public function testFactoryCreatesBlockStrategy(): void
    {
        $strategy = DuplicateStrategyFactory::create('block');
        
        $this->assertInstanceOf(BlockDuplicateStrategy::class, $strategy);
    }
    
    /**
     * Test factory throws exception for invalid action
     */
    public function testFactoryThrowsExceptionForInvalidAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid duplicate action');
        
        DuplicateStrategyFactory::create('invalid_action');
    }
    
    /**
     * Test factory getAvailableStrategies()
     */
    public function testFactoryReturnsAvailableStrategies(): void
    {
        $strategies = DuplicateStrategyFactory::getAvailableStrategies();
        
        $this->assertIsArray($strategies);
        $this->assertCount(3, $strategies);
        $this->assertContains('allow', $strategies);
        $this->assertContains('warn', $strategies);
        $this->assertContains('block', $strategies);
    }
    
    /**
     * Test factory isValidAction()
     */
    public function testFactoryValidatesActions(): void
    {
        $this->assertTrue(DuplicateStrategyFactory::isValidAction('allow'));
        $this->assertTrue(DuplicateStrategyFactory::isValidAction('warn'));
        $this->assertTrue(DuplicateStrategyFactory::isValidAction('block'));
        $this->assertFalse(DuplicateStrategyFactory::isValidAction('invalid'));
        $this->assertFalse(DuplicateStrategyFactory::isValidAction(''));
    }
    
    /**
     * Test that all strategies implement the interface
     */
    public function testAllStrategiesImplementInterface(): void
    {
        $strategies = [
            new AllowDuplicateStrategy(),
            new WarnDuplicateStrategy(),
            new BlockDuplicateStrategy(),
        ];
        
        foreach ($strategies as $strategy) {
            $this->assertInstanceOf(
                \Ksfraser\FaBankImport\Strategy\DuplicateStrategyInterface::class,
                $strategy
            );
        }
    }
}
