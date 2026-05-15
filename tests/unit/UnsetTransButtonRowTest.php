<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';
require_once __DIR__ . '/../../Views/SubmitButtonRow.php';
require_once __DIR__ . '/../../Views/UnsetTransButtonRow.php';

class UnsetTransButtonRowTest extends TestCase
{
    public function testUnsetTransButtonRowRendersLabelAndName()
    {
        $row = new UnsetTransButtonRow(99, 12345);
        $html = $row->getHtml();
        $this->assertStringContainsString('name="UnsetTrans[99]"', $html);
        $this->assertStringContainsString('Unset Transaction 12345', $html);
        $this->assertStringContainsString('Unset Transaction Association', $html);
    }
}
