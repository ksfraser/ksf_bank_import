<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML;

use Ksfraser\HTML\Composites\HTML_ROW;
use Ksfraser\HTML\Elements\Buttons\HtmlButton;
use Ksfraser\HTML\Elements\Form\Input\HtmlInput;
use Ksfraser\HTML\Elements\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML\Support\HtmlChildrenConsumer;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/HtnlAttribute.php';
require_once __DIR__ . '/Support/HtmlChildrenConsumer.php';

/**
 * Coverage for the composite HTML elements and the children trait.
 */
final class HtmlCompositesTest extends TestCase
{
    /**
     * @return void
     */
    public function testChildrenTraitCollectsAndRendersChildren(): void
    {
        $consumer = new HtmlChildrenConsumer();
        $consumer->addChild(new HtmlString('one'));
        $consumer->setChildren([new HtmlString('two')]);

        $this->assertCount(2, $consumer->getChildren());
        $this->assertSame('onetwo', $consumer->renderChildrenMarkup());
    }

    /**
     * @return void
     */
    public function testChildrenTraitRendersStraightToTheOutput(): void
    {
        $consumer = new HtmlChildrenConsumer();
        $consumer->addChild(new HtmlString('one'));

        // The renderer captures the output of the children in its own
        // buffer and hands it back as a string.
        $this->assertSame('one', $consumer->renderChildrenOutput());
    }

    /**
     * @return void
     */
    public function testLabelRowAcceptsAContentCellAttribute(): void
    {
        $row = new HtmlLabelRow(new HtmlString('Label'), new HtmlString('Value'));
        $row->addContentCellAttribute('colspan', '2');

        $this->assertStringContainsString('colspan="2"', $row->getHtml());
    }

    /**
     * @return void
     */
    public function testLegacyRowRendersItsMarkup(): void
    {
        $row = new HTML_ROW('a cell');

        $this->assertStringContainsString('<tr>', $row->getHtml());
        $this->assertStringContainsString('a cell', $row->getHtml());
    }

    /**
     * @return void
     */
    public function testButtonRendersItsTag(): void
    {
        $button = new HtmlButton(new HtmlString('Press'));

        $this->assertSame('<button>Press</button>', $button->getHtml());
    }

    /**
     * @return void
     */
    public function testButtonAcceptsAnOnclickAttribute(): void
    {
        $button = new HtmlButton(new HtmlString('Press'));
        $button->setOnclick(new \Ksfraser\HTML\Elements\Buttons\HtnlAttribute('doIt()'));

        $this->assertStringContainsString('onclick="doIt()"', $button->getHtml());
    }

    /**
     * @return void
     */
    public function testInputAcceptsAPlaceholder(): void
    {
        $input = new HtmlInput('text');
        $input->setPlaceholder('Type here');

        $this->assertStringContainsString('placeholder="Type here"', $input->getHtml());
    }

    /**
     * @return void
     */
    public function testStyleCannotBeDeclared(): void
    {
        // HtmlStyle::getHtml() drops the string return type that
        // HtmlAttribute::getHtml() declares, which PHP rejects with a fatal
        // error, so the class cannot be declared at all.
        $this->assertFalse(
            class_exists(\Ksfraser\HTML\Elements\HtmlStyle::class, false),
            'HtmlStyle is expected not to be declarable.'
        );
    }
}
