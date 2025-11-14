<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Composites\LabelRowBase;
use Exception;

/**
 * Test LabelRowBase validation logic
 * 
 * @package Tests\Unit\HTML
 * @since 20251019
 */
class LabelRowBaseTest extends TestCase
{
    /**
     * Test that abstract class cannot be instantiated directly
     */
    public function testCannotBeInstantiatedDirectly(): void
    {
        // This test verifies the class is abstract
        // We can't test it directly in PHP 7.4, but we can document it
        $reflection = new \ReflectionClass(LabelRowBase::class);
        $this->assertTrue($reflection->isAbstract(), 'LabelRowBase should be abstract');
    }
    
    /**
     * Test that exception is thrown when data property not set
     */
    public function testThrowsExceptionWhenDataNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('data MUST be set by inheriting class!');
        
        // Create anonymous class that extends LabelRowBase but doesn't set $data
        $badClass = new class("") extends LabelRowBase {
            public function __construct($bi_lineitem) {
                // Only set label, not data
                $this->label = "Test Label:";
                parent::__construct($bi_lineitem);
            }
        };
    }
    
    /**
     * Test that exception is thrown when label property not set
     */
    public function testThrowsExceptionWhenLabelNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('label MUST be set by inheriting class!');
        
        // Create anonymous class that extends LabelRowBase but doesn't set $label
        $badClass = new class("") extends LabelRowBase {
            public function __construct($bi_lineitem) {
                // Only set data, not label
                $this->data = "Test Data";
                parent::__construct($bi_lineitem);
            }
        };
    }
    
    /**
     * Test that exception is thrown when neither property is set
     */
    public function testThrowsExceptionWhenNeitherPropertySet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('data MUST be set by inheriting class!');
        
        // Create anonymous class that extends LabelRowBase but sets nothing
        $badClass = new class("") extends LabelRowBase {
            public function __construct($bi_lineitem) {
                // Don't set anything - should throw data exception first
                parent::__construct($bi_lineitem);
            }
        };
    }
    
    /**
     * Test that valid class works correctly
     */
    public function testWorksWhenBothPropertiesSet(): void
    {
        // Create anonymous class that properly extends LabelRowBase
        $goodClass = new class("") extends LabelRowBase {
            public function __construct($bi_lineitem) {
                $this->label = "Test Label:";
                $this->data = "Test Data";
                parent::__construct($bi_lineitem);
            }
        };
        
        $html = $goodClass->getHtml();
        
        $this->assertStringContainsString('Test Label:', $html);
        $this->assertStringContainsString('Test Data', $html);
    }
}
