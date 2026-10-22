<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML;

use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlAttributeList;
use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlFragment;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the building blocks the HTML elements are made of.
 *
 * These classes were used by the views but their less common members (the
 * attribute list setters, the fragment helpers, the link helpers) had never
 * been called.
 */
final class HtmlBuildingBlocksTest extends TestCase
{
    /**
     * @return void
     */
    public function testElementTagCanBeSet(): void
    {
        $element = new HtmlElement(new HtmlString('body'));

        $element->setTag('SECTION');

        $this->assertSame('<section>body</section>', $element->getHtml());
    }

    /**
     * @return void
     */
    public function testElementAttributeListCanBeReplaced(): void
    {
        $element = new HtmlElement(new HtmlString('body'));
        $element->setTag('div');

        $element->setAttributeList(new HtmlAttributeList(new HtmlAttribute('id', 'main')));

        $this->assertSame('<div id="main">body</div>', $element->getHtml());
    }

    /**
     * @return void
     */
    public function testElementRendersItself(): void
    {
        $element = new HtmlElement(new HtmlString('body'));
        $element->setTag('div');

        ob_start();
        $element->toHtml();
        $output = (string) ob_get_clean();

        $this->assertSame('<div>body</div>', $output);
    }

    /**
     * @return void
     */
    public function testAttributeRendersItself(): void
    {
        $attribute = new HtmlAttribute('class', 'label');

        ob_start();
        $attribute->toHtml();
        $output = (string) ob_get_clean();

        $this->assertSame('class="label"', $output);
    }

    /**
     * @return void
     */
    public function testAttributeListRendersItself(): void
    {
        $list = new HtmlAttributeList(new HtmlAttribute('class', 'label'));

        ob_start();
        $list->toHtml();
        $output = (string) ob_get_clean();

        $this->assertSame('class="label" ', $output);
    }

    /**
     * @return void
     */
    public function testFragmentAcceptsItsChildrenInTheConstructor(): void
    {
        $fragment = new HtmlFragment([new HtmlString('a'), new HtmlString('b')]);

        $this->assertSame(2, $fragment->getChildCount());
        $this->assertFalse($fragment->isEmpty());
        $this->assertSame('ab', $fragment->getHtml());
    }

    /**
     * @return void
     */
    public function testEmptyFragmentReportsItselfAsEmpty(): void
    {
        $fragment = new HtmlFragment();

        $this->assertTrue($fragment->isEmpty());
        $this->assertSame(0, $fragment->getChildCount());
    }
}
