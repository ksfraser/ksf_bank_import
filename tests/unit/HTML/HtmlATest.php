<?php

declare(strict_types=1);

namespace Tests\Unit\HTML;

use Ksfraser\HTML\Elements\HtmlA;
use Ksfraser\HTML\Elements\HtmlString;
use PHPUnit\Framework\TestCase;

final class HtmlATest extends TestCase
{
    public function testConstructorWithUrlAndStringContent(): void
    {
        $link = new HtmlA('https://example.com', 'Click here');

        $this->assertInstanceOf(HtmlA::class, $link);
        $html = $link->getHtml();
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('>Click here<', $html);
    }

    public function testConstructorWithUrlOnly(): void
    {
        $link = new HtmlA('https://example.com');

        $this->assertInstanceOf(HtmlA::class, $link);
        $html = $link->getHtml();
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('>https://example.com<', $html);
    }

    public function testConstructorWithHtmlStringContent(): void
    {
        $content = new HtmlString('Test Link');
        $link = new HtmlA('/page', $content);

        $this->assertInstanceOf(HtmlA::class, $link);
        $html = $link->getHtml();
        $this->assertStringContainsString('href="/page"', $html);
        $this->assertStringContainsString('>Test Link<', $html);
    }

    public function testConstructorThrowsExceptionForNestedLink(): void
    {
        $nestedLink = new HtmlA('/nested', 'Nested');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid link content: Cannot nest links inside links');

        new HtmlA('/parent', $nestedLink);
    }

    public function testConstructorThrowsExceptionForInvalidContentType(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid link content type');

        new HtmlA('/page', 123);
    }
}