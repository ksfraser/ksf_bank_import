<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HTML_ROW_LABEL;
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
        
        // Check that width and class attributes are set
        $this->assertStringContainsString('width=\'30\'', $html);
        $this->assertStringContainsString('class=\'custom-class\'', $html);
    }

    public function testGetHtmlContainsLabelAndData(): void
    {
        ob_start();
        $this->labelRow->getHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test Label', $output);
        $this->assertStringContainsString('test data', $output);
    }

    public function testToHtmlOutputsCorrectly(): void
    {
        ob_start();
        $this->labelRow->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test Label', $output);
        $this->assertStringContainsString('test data', $output);
        $this->assertStringContainsString('width=\'25\'', $output); // Default width
        $this->assertStringContainsString('class=\'label\'', $output); // Default class
    }
}