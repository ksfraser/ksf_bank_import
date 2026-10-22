<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\HTML;

use Ksfraser\HTML\Elements\HtmlString;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the plain HTML element classes.
 *
 * Every one of these classes is a thin wrapper that gives the shared
 * HtmlElement base class a tag name. They had never been instantiated, so
 * neither their constructor nor their tag assignment was covered.
 *
 * The classes that extend HtmlFormatting are deliberately absent: that base
 * class cannot be declared (its newAttributeList() override drops the
 * void return type the parent declares, which is a fatal error), so every
 * one of its subclasses is equally undeclaraable.
 */
final class HtmlSimpleElementsTest extends TestCase
{
    /**
     * The element classes and the tag each of them renders.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function elementProvider(): array
    {
        return [
            'body' => [\Ksfraser\HTML\Elements\HtmlBody::class, 'body'],
            'div' => [\Ksfraser\HTML\Elements\HtmlDiv::class, 'div'],
            'head' => [\Ksfraser\HTML\Elements\HtmlHead::class, 'head'],
            'html' => [\Ksfraser\HTML\Elements\HtmlHtml::class, 'html'],
            'table body' => [\Ksfraser\HTML\Elements\HtmlTableBody::class, 'tbody'],
            'table caption' => [\Ksfraser\HTML\Elements\HtmlTableCaption::class, 'caption'],
            'table col' => [\Ksfraser\HTML\Elements\HtmlTableCol::class, 'col'],
            'table col group' => [\Ksfraser\HTML\Elements\HtmlTableColGroup::class, 'colgroup'],
            'table foot' => [\Ksfraser\HTML\Elements\HtmlTableFoot::class, 'tfoot'],
            'table head' => [\Ksfraser\HTML\Elements\HtmlTableHead::class, 'thead'],
            'table header cell' => [\Ksfraser\HTML\Elements\HtmlTableHeaderCell::class, 'th'],
            'description description' => [\Ksfraser\HTML\Elements\HtmlDescriptionDescription::class, 'dd'],
            'description list' => [\Ksfraser\HTML\Elements\HtmlDescriptionList::class, 'dl'],
            'description term' => [\Ksfraser\HTML\Elements\HtmlDescriptionTerm::class, 'dt'],
            'list item' => [\Ksfraser\HTML\Elements\HtmlListItem::class, 'li'],
            'ordered list' => [\Ksfraser\HTML\Elements\HtmlOrderedList::class, 'ol'],
            'unordered list' => [\Ksfraser\HTML\Elements\HtmlUnorderedList::class, 'ul'],
            'paragraph' => [\Ksfraser\HTML\Elements\HtmlParagraph::class, 'p'],
            'preformatted' => [\Ksfraser\HTML\Elements\HtmlPreformatted::class, 'pre'],
            'span' => [\Ksfraser\HTML\Elements\HtmlSpan::class, 'span'],
            'title' => [\Ksfraser\HTML\Elements\HtmlTitle::class, 'title'],
            'heading 1' => [\Ksfraser\HTML\Elements\HtmlHeading1::class, 'h1'],
            'heading 2' => [\Ksfraser\HTML\Elements\HtmlHeading2::class, 'h2'],
            'heading 3' => [\Ksfraser\HTML\Elements\HtmlHeading3::class, 'h3'],
            'heading 4' => [\Ksfraser\HTML\Elements\HtmlHeading4::class, 'h4'],
            'heading 5' => [\Ksfraser\HTML\Elements\HtmlHeading5::class, 'h5'],
            'heading 6' => [\Ksfraser\HTML\Elements\HtmlHeading6::class, 'h6'],
            'form' => [\Ksfraser\HTML\Elements\HtmlForm::class, 'form'],
        ];
    }

    /**
     * @dataProvider elementProvider
     * @param string $class The element class.
     * @param string $tag The tag the element renders.
     * @return void
     */
    public function testElementRendersItsTag(string $class, string $tag): void
    {
        $element = new $class(new HtmlString('content'));

        $this->assertSame('<' . $tag . '>content</' . $tag . '>', $element->getHtml());
    }

    /**
     * The empty elements carry no content and no closing tag.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function emptyElementProvider(): array
    {
        return [
            'break' => [\Ksfraser\HTML\Elements\HtmlBr::class, 'br'],
            'horizontal rule' => [\Ksfraser\HTML\Elements\HtmlHr::class, 'hr'],
        ];
    }

    /**
     * @dataProvider emptyElementProvider
     * @param string $class The element class.
     * @param string $tag The tag the element renders.
     * @return void
     */
    public function testEmptyElementRendersWithoutClosingTag(string $class, string $tag): void
    {
        $element = new $class();

        $this->assertSame('<' . $tag . '>', $element->getHtml());
    }

    /**
     * The element classes that extend HtmlFormatting cannot be declared.
     *
     * HtmlFormatting::newAttributeList() drops the void return type that
     * HtmlElement::newAttributeList() declares, which PHP rejects with a
     * fatal error, so the whole family is undeclaraable.
     *
     * @return array<string, array{0: string}>
     */
    public function undeclaraableFormattingProvider(): array
    {
        return [
            'deleted' => [\Ksfraser\HTML\Elements\HtmlDeleted::class],
            'emphasize' => [\Ksfraser\HTML\Elements\HtmlEmphasize::class],
            'inserted' => [\Ksfraser\HTML\Elements\HtmlInserted::class],
            'italic' => [\Ksfraser\HTML\Elements\HtmlItalic::class],
            'mark' => [\Ksfraser\HTML\Elements\HtmlMark::class],
            'small' => [\Ksfraser\HTML\Elements\HtmlSmall::class],
            'strong' => [\Ksfraser\HTML\Elements\HtmlStrong::class],
            'subscript' => [\Ksfraser\HTML\Elements\HtmlSubscript::class],
            'superscript' => [\Ksfraser\HTML\Elements\HtmlSuperscript::class],
        ];
    }

    /**
     * @dataProvider undeclaraableFormattingProvider
     * @param string $class The element class.
     * @return void
     */
    public function testFormattingElementsCannotBeDeclared(string $class): void
    {
        $this->assertFalse(
            class_exists($class, false),
            $class . ' is expected not to be declarable, because its base class is not.'
        );
    }

    /**
     * The style list cannot be declared either.
     *
     * HtmlStyleList::getHtml() drops the string return type that
     * HtmlElementInterface::getHtml() declares, and its child
     * HtmlInternalCSSList inherits the broken declaration. The image
     * element builds its size with a style list, so that method is
     * unreachable as well.
     *
     * @return array<string, array{0: string}>
     */
    public function undeclaraableStyleProvider(): array
    {
        return [
            'style list' => [\Ksfraser\HTML\Elements\HtmlStyleList::class],
            'internal css list' => [\Ksfraser\HTML\Elements\HtmlInternalCSSList::class],
            'style' => [\Ksfraser\HTML\Elements\HtmlStyle::class],
        ];
    }

    /**
     * @dataProvider undeclaraableStyleProvider
     * @param string $class The class.
     * @return void
     */
    public function testTheStyleClassesCannotBeDeclared(string $class): void
    {
        $this->assertFalse(
            class_exists($class, false),
            $class . ' is expected not to be declarable.'
        );
    }
}
