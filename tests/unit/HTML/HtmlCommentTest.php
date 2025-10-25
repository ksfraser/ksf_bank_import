<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\HtmlComment;

/**
 * HtmlCommentTest
 *
 * Tests for HtmlComment class.
 *
 * @package    Ksfraser\Tests\Unit\HTML
 * @author     Claude AI Assistant
 * @since      20251019
 */
class HtmlCommentTest extends TestCase
{
    public function testConstruction(): void
    {
        $comment = new HtmlComment('Test comment');
        $this->assertInstanceOf(HtmlComment::class, $comment);
    }

    public function testGetHtmlReturnsComment(): void
    {
        $comment = new HtmlComment('Test comment');
        $html = $comment->getHtml();

        $this->assertEquals('<!-- Test comment -->', $html);
    }

    public function testGetHtmlWithEmptyString(): void
    {
        $comment = new HtmlComment('');
        $html = $comment->getHtml();

        $this->assertEquals('<!--  -->', $html);
    }

    public function testGetHtmlWithSpecialCharacters(): void
    {
        $comment = new HtmlComment('Test <tag> & "quotes" \'apostrophe\'');
        $html = $comment->getHtml();

        // Comments don't escape special characters
        $this->assertEquals('<!-- Test <tag> & "quotes" \'apostrophe\' -->', $html);
    }

    public function testGetHtmlWithMultipleLines(): void
    {
        $text = "Line 1\nLine 2\nLine 3";
        $comment = new HtmlComment($text);
        $html = $comment->getHtml();

        $this->assertEquals("<!-- Line 1\nLine 2\nLine 3 -->", $html);
    }

    public function testGetTextReturnsOriginalText(): void
    {
        $originalText = 'Test comment';
        $comment = new HtmlComment($originalText);

        $this->assertEquals($originalText, $comment->getText());
    }

    public function testSetTextUpdatesText(): void
    {
        $comment = new HtmlComment('Initial');
        $comment->setText('Updated');

        $this->assertEquals('Updated', $comment->getText());
        $this->assertEquals('<!-- Updated -->', $comment->getHtml());
    }

    public function testSetTextFluentInterface(): void
    {
        $comment = new HtmlComment('Initial');
        $result = $comment->setText('Updated');

        $this->assertSame($comment, $result);
    }

    public function testToHtmlOutputsComment(): void
    {
        $comment = new HtmlComment('Test output');

        ob_start();
        $comment->toHtml();
        $output = ob_get_clean();

        $this->assertEquals('<!-- Test output -->', $output);
    }

    public function testFaFunctionPlaceholder(): void
    {
        $comment = new HtmlComment('supplier_list("partnerId_123", null)');
        $html = $comment->getHtml();

        $this->assertStringContainsString('supplier_list', $html);
        $this->assertStringContainsString('partnerId_123', $html);
    }

    public function testMultiLineComment(): void
    {
        $text = "<select name='field'>\n  <option>Option 1</option>\n</select>";
        $comment = new HtmlComment($text);
        $html = $comment->getHtml();

        $this->assertStringStartsWith('<!-- ', $html);
        $this->assertStringEndsWith(' -->', $html);
        $this->assertStringContainsString("<select name='field'>", $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function testCanBeReused(): void
    {
        $comment = new HtmlComment('Reusable');

        $html1 = $comment->getHtml();
        $html2 = $comment->getHtml();

        $this->assertEquals($html1, $html2);
    }

    public function testCanBeModified(): void
    {
        $comment = new HtmlComment('Original');
        $original = $comment->getHtml();

        $comment->setText('Modified');
        $modified = $comment->getHtml();

        $this->assertEquals('<!-- Original -->', $original);
        $this->assertEquals('<!-- Modified -->', $modified);
        $this->assertNotEquals($original, $modified);
    }
}
