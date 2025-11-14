<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\FaUiFunctions;

class HTML_ROW_LABELTest extends TestCase
{
    private HTML_ROW_LABEL $labelRow;

    protected function setUp(): void
    {
        $this->labelRow = new HTML_ROW_LABEL('test data', 'Test Label');
    }

    public function testConstructorSetsAttributes(): void
    {
        $labelRow = new HTML_ROW_LABEL('test data', 'Test Label', 30, 'custom-class');
        $html = $labelRow->getHtml();
        
        // Check that width and class attributes are set (new format uses double quotes)
        $this->assertStringContainsString('width="30%"', $html);
        $this->assertStringContainsString('class="custom-class"', $html);
        $this->assertStringContainsString('Test Label', $html);
        $this->assertStringContainsString('test data', $html);
    }

    public function testGetHtmlContainsLabelAndData(): void
    {
        // getHtml() returns the HTML string, doesn't output it
        $html = $this->labelRow->getHtml();
        
        $this->assertStringContainsString('Test Label', $html);
        $this->assertStringContainsString('test data', $html);
    }

    public function testToHtmlOutputsCorrectly(): void
    {
        ob_start();
        $this->labelRow->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test Label', $output);
        $this->assertStringContainsString('test data', $output);
        $this->assertStringContainsString('width="25%"', $output); // Default width (new format)
        $this->assertStringContainsString('class="label"', $output); // Default class (new format)
    }
}