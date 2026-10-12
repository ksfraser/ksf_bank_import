<?php
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\HtmlAttribute;

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