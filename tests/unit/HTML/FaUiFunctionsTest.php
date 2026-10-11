<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\FaUiFunctions;

class FaUiFunctionsTest extends TestCase
{
    public function testLabelRowFallback(): void
    {
        ob_start();
        FaUiFunctions::label_row('Test Label', 'test content', 'width="50"');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('<td class=\'label\'>Test Label</td>', $output);
        $this->assertStringContainsString('<td width="50">test content</td>', $output);
        $this->assertStringContainsString('</tr>', $output);
    }

    public function testStartTableFallback(): void
    {
        ob_start();
        FaUiFunctions::start_table(FaUiFunctions::TABLESTYLE2, 'width="100%"');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('class=\'tablestyle2\'', $output);
        $this->assertStringContainsString('width="100%"', $output);
    }

    public function testEndTableFallback(): void
    {
        ob_start();
        FaUiFunctions::end_table();
        $output = ob_get_clean();
        
        $this->assertEquals("</table>\n", $output);
    }

    public function testTableStyle2Constant(): void
    {
        $this->assertEquals(2, FaUiFunctions::TABLESTYLE2);
    }
}