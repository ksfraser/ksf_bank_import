<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';
require_once __DIR__ . '/../../Views/SubmitButtonRow.php';
require_once __DIR__ . '/../../Views/ProcessTransactionButtonRow.php';
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
