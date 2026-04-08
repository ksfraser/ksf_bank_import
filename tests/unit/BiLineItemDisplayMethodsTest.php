<?php

/**
 * Test for bi_lineitem Display Methods - RTDD Phase 2
 * 
 * RTDD (Refactor Test-Driven Development):
 * PHASE 2: Write tests for NEW methods (get*Html)
 * - These tests will FAIL because methods don't exist yet
 * - Then we implement the methods (Phase 3)
 * - Then tests pass
 *
 * @package    KsfBankImport
 * @subpackage Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test bi_lineitem Display Method Signatures
 *
 * Tests method existence using reflection to avoid complex dependencies.
 */
class BiLineItemDisplayMethodsTest extends TestCase
{
    private string $classFile;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->classFile = __DIR__ . '/../../class.bi_lineitem.php';
    }
    
    /**
     * Test that class file exists and is readable
     *
     * @test
     */
    public function class_file_exists(): void
    {
        $this->assertFileExists($this->classFile);
        $this->assertFileIsReadable($this->classFile);
    }
    
    /**
     * Test that class file has no syntax errors
     *
     * @test
     */
    public function class_file_has_no_syntax_errors(): void
    {
        $output = shell_exec('php -l "' . $this->classFile . '" 2>&1');
        
        $this->assertStringContainsString(
            'No syntax errors',
            $output,
            'class.bi_lineitem.php must have no syntax errors'
        );
    }
    
    /**
     * Test that display() method exists
     *
     * EXISTING METHOD - should already exist in production
     *
     * @test
     */
    public function display_method_exists(): void
    {
        $content = file_get_contents($this->classFile);
        
        $this->assertStringContainsString(
            'function display(',
            $content,
            'display() method must exist'
        );
    }
    
    /**
     * Test that display_left() method exists
     *
     * EXISTING METHOD - should already exist in production
     *
     * @test
     */
    public function display_left_method_exists(): void
    {
        $content = file_get_contents($this->classFile);
        
        $this->assertStringContainsString(
            'function display_left(',
            $content,
            'display_left() method must exist'
        );
    }
    
    /**
     * Test that display_right() method exists
     *
     * EXISTING METHOD - should already exist in production
     *
     * @test
     */
    public function display_right_method_exists(): void
    {
        $content = file_get_contents($this->classFile);
        
        $this->assertStringContainsString(
            'function display_right(',
            $content,
            'display_right() method must exist'
        );
    }
    
    /**
     * Test that getHtml() method exists
     *
     * NEW METHOD - will FAIL until we implement it (RTDD Phase 3)
     *
     * @test
     */
    public function getHtml_method_exists(): void
    {
        $content = file_get_contents($this->classFile);
        
        $this->assertStringContainsString(
            'function getHtml(',
            $content,
            'getHtml() method must exist - this is the NEW method we need to implement'
        );
    }
    
    /**
     * Test that getLeftHtml() method exists
     *
     * NEW METHOD - will FAIL until we implement it (RTDD Phase 3)
     *
     * @test
     */
    public function getLeftHtml_method_exists(): void
    {
        $content = file_get_contents($this->classFile);
        
        $this->assertStringContainsString(
            'function getLeftHtml(',
            $content,
            'getLeftHtml() method must exist - this is the NEW method we need to implement'
        );
    }
    
    /**
     * Test that getRightHtml() method exists
     *
     * NEW METHOD - will FAIL until we implement it (RTDD Phase 3)
     *
     * @test
     */
    public function getRightHtml_method_exists(): void
    {
        $content = file_get_contents($this->classFile);
        
        $this->assertStringContainsString(
            'function getRightHtml(',
            $content,
            'getRightHtml() method must exist - this is the NEW method we need to implement'
        );
    }
    
    /**
     * Count how many times display() is defined (should be 1)
     *
     * @test
     */
    public function display_method_not_duplicated(): void
    {
        $content = file_get_contents($this->classFile);
        $count = substr_count($content, 'function display()');
        
        $this->assertSame(
            1,
            $count,
            'display() method should be defined exactly once (no duplicates)'
        );
    }
    
    /**
     * Count how many times display_left() is defined (should be 1)
     *
     * @test
     */
    public function display_left_method_not_duplicated(): void
    {
        $content = file_get_contents($this->classFile);
        $count = substr_count($content, 'function display_left()');
        
        $this->assertSame(
            1,
            $count,
            'display_left() method should be defined exactly once (no duplicates)'
        );
    }
    
    /**
     * Count how many times display_right() is defined (should be 1)
     *
     * @test
     */
    public function display_right_method_not_duplicated(): void
    {
        $content = file_get_contents($this->classFile);
        $count = substr_count($content, 'function display_right()');
        
        $this->assertSame(
            1,
            $count,
            'display_right() method should be defined exactly once (no duplicates)'
        );
    }
    
    /**
     * Verify file size is reasonable (production has ~1050 lines)
     *
     * @test
     */
    public function file_size_is_reasonable(): void
    {
        $lineCount = count(file($this->classFile));
        
        $this->assertGreaterThan(
            900,
            $lineCount,
            'File should have at least 900 lines (production baseline)'
        );
        
        $this->assertLessThan(
            1200,
            $lineCount,
            'File should have less than 1200 lines (no major duplication)'
        );
    }
}
