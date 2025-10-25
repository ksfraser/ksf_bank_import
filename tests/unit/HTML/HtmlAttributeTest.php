<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlAttribute;

class HtmlAttributeTest extends TestCase
{
    private HtmlAttribute $attribute;

    protected function setUp(): void
    {
        $this->attribute = new HtmlAttribute('class', 'test-class');
    }

    public function testGetName(): void
    {
        $this->assertEquals('class', $this->attribute->getName());
    }

    public function testGetValue(): void
    {
        $this->assertEquals('test-class', $this->attribute->getValue());
    }

    public function testConstructorWithSpecialCharacters(): void
    {
        $attribute = new HtmlAttribute('data-test', 'value & more');
        $this->assertEquals('data-test', $attribute->getName());
        $this->assertEquals('value & more', $attribute->getValue());
    }
}