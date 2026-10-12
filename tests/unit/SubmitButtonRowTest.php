<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';
require_once __DIR__ . '/../../Views/SubmitButtonRow.php';
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
