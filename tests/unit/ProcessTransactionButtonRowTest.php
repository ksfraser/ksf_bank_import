<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';
require_once __DIR__ . '/../../Views/SubmitButtonRow.php';
require_once __DIR__ . '/../../Views/ProcessTransactionButtonRow.php';

class ProcessTransactionButtonRowTest extends TestCase
{
    public function testProcessButtonRowRenders()
    {
        $row = new ProcessTransactionButtonRow(42);
        $html = $row->getHtml();
        $this->assertStringContainsString('name="ProcessTransaction[42]"', $html);
        $this->assertStringContainsString('Process', $html);
    }
}
