<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\ParserDropdownView;

class ParserDropdownViewTest extends TestCase
{
    public function testGetHtmlContainsOptions()
    {
        $view = new ParserDropdownView(['p1' => 'Parser 1', 'p2' => 'Parser 2'], 'p2');
        $html = $view->getHtml();
        $this->assertStringContainsString('option', $html);
        $this->assertStringContainsString('p2', $html);
    }
}
