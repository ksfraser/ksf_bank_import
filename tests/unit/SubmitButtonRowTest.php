<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';
require_once __DIR__ . '/../../Views/SubmitButtonRow.php';

class SubmitButtonRowTest extends TestCase
{
    public function testRowRendersButtonNameAndLabel()
    {
        $row = new SubmitButtonRow('ProcessTransaction[1]', 'Process');
        $html = $row->getHtml();
        $this->assertStringContainsString('name="ProcessTransaction[1]"', $html);
        $this->assertStringContainsString('Process', $html);
    }
}
