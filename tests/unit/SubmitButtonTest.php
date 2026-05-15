<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Views/SubmitButton.php';

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
