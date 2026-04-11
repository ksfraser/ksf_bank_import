<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../views/SubmitButton.php';
require_once __DIR__ . '/../../views/SubmitButtonRow.php';
require_once __DIR__ . '/../../views/UnsetTransButtonRow.php';

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
