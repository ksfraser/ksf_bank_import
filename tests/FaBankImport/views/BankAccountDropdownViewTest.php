<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\BankAccountDropdownView;

class BankAccountDropdownViewTest extends TestCase
{
    public function testGetHtmlContainsOptions()
    {
        $view = new BankAccountDropdownView(['a1' => 'Account 1', 'a2' => 'Account 2'], 'a2');
        $html = $view->getHtml();
        $this->assertStringContainsString('option', $html);
        $this->assertStringContainsString('a2', $html);
    }
}
