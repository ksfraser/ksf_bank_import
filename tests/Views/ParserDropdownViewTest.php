<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\ParserDropdownView;

class ParserDropdownViewTest extends TestCase
{
    public function testGetHtmlWithValidSelectedParser()
    {
        $parsers = ['QFX' => 'QFX Format', 'CSV' => 'CSV Format'];
        $view = new ParserDropdownView($parsers, 'QFX');
        $html = $view->getHtml();
        $this->assertStringContainsString("<option value='QFX' selected>QFX Format</option>", $html);
        $this->assertStringContainsString("<option value='CSV' >CSV Format</option>", $html);
    }

    public function testGetHtmlWithNoSelectedParser()
    {
        $parsers = ['QFX' => 'QFX Format', 'CSV' => 'CSV Format'];
        $view = new ParserDropdownView($parsers);
        $html = $view->getHtml();
        $this->assertStringContainsString("<option value='QFX' >QFX Format</option>", $html);
        $this->assertStringContainsString("<option value='CSV' >CSV Format</option>", $html);
    }

    public function testSetSelectedParserValid()
    {
        $parsers = ['QFX' => 'QFX Format', 'CSV' => 'CSV Format'];
        $view = new ParserDropdownView($parsers);
        $view->setSelectedParser('CSV');
        $html = $view->getHtml();
        $this->assertStringContainsString("<option value='CSV' selected>CSV Format</option>", $html);
    }

    public function testSetSelectedParserInvalidThrows()
    {
        $parsers = ['QFX' => 'QFX Format', 'CSV' => 'CSV Format'];
        $view = new ParserDropdownView($parsers);
        $this->expectException(InvalidArgumentException::class);
        $view->setSelectedParser('XML');
    }
}
