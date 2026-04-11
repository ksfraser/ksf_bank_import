<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../views/SubmitButton.php';
require_once __DIR__ . '/../../views/SubmitButtonRow.php';
require_once __DIR__ . '/../../views/ProcessTransactionButtonRow.php';

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
