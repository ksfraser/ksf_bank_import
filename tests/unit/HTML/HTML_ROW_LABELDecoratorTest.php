<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HTML_ROW_LABELDecorator;

class HTML_ROW_LABELDecoratorTest extends TestCase
{
    private HTML_ROW_LABELDecorator $decorator;

    protected function setUp(): void
    {
        $this->decorator = new HTML_ROW_LABELDecorator('test data', 'Test Label');
    }

    public function testGetHtmlDelegatesCorrectly(): void
    {
        $html = $this->decorator->getHtml();
        $this->assertStringContainsString('Test Label', $html);
        $this->assertStringContainsString('test data', $html);
        $this->assertStringContainsString('width=\'25\'', $html); // Default width
        $this->assertStringContainsString('class=\'label\'', $html); // Default class
    }

    public function testToHtmlDelegatesCorrectly(): void
    {
        ob_start();
        $this->decorator->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test Label', $output);
        $this->assertStringContainsString('test data', $output);
    }

    public function testCustomAttributesArePassed(): void
    {
        $decorator = new HTML_ROW_LABELDecorator('test data', 'Test Label', 40, 'custom-class');
        $html = $decorator->getHtml();
        
        $this->assertStringContainsString('width=\'40\'', $html);
        $this->assertStringContainsString('class=\'custom-class\'', $html);
    }
}