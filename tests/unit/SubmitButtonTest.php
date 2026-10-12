<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

class SubmitButtonTest extends TestCase
{
    public function testRendersNameAndLabel()
    {
        $btn = new SubmitButton('TestName', 'Click Me');
        $html = $btn->getHtml();
        $this->assertStringContainsString('name="TestName"', $html);
        $this->assertStringContainsString('Click Me', $html);
    }
}
