<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlAttribute;

class HtmlTableRowTest extends TestCase
{
    private HtmlString $content;
    private HtmlTableRow $row;

    protected function setUp(): void
    {
        $this->content = new HtmlString('test content');
        $this->row = new HtmlTableRow($this->content);
    }

    public function testGetHtml(): void
    {
        $html = $this->row->getHtml();
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('test content', $html);
        $this->assertStringContainsString('</tr>', $html);
    }

    public function testToHtml(): void
    {
        ob_start();
        $this->row->toHtml();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('test content', $output);
        $this->assertStringContainsString('</tr>', $output);
    }

    public function testAddAttribute(): void
    {
        $this->row->addAttribute(new HtmlAttribute('class', 'test-class'));
        $html = $this->row->getHtml();
        
        $this->assertStringContainsString('class="test-class"', $html);
    }

    public function testMultipleAttributes(): void
    {
        $this->row->addAttribute(new HtmlAttribute('class', 'test-class'));
        $this->row->addAttribute(new HtmlAttribute('id', 'test-id'));
        $html = $this->row->getHtml();
        
        $this->assertStringContainsString('class="test-class"', $html);
        $this->assertStringContainsString('id="test-id"', $html);
    }
}