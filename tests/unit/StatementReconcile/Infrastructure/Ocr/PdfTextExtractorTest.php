<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Infrastructure\Ocr;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\PdfTextExtractor;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\PdfTextExtractor
 */
class PdfTextExtractorTest extends TestCase
{
    public function testExtractTextReturnsParsedText(): void
    {
        $expectedText = "STATEMENT\n\nOpening Balance: $500.00\nCLOSING BALANCE: $1200.50";

        // Mock smalot Document and Parser.
        $mockDocument = $this->createMock(\Smalot\PdfParser\Document::class);
        $mockDocument->method('getText')->willReturn($expectedText);

        $mockParser = $this->createMock(Parser::class);
        $mockParser->method('parseFile')->willReturn($mockDocument);

        // Write a temp file so is_file() and is_readable() pass.
        $tmpFile = tempnam(sys_get_temp_dir(), 'pdf_test_') . '.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake');

        try {
            $extractor = new PdfTextExtractor($mockParser);
            $text      = $extractor->extractText($tmpFile);
            $this->assertStringContainsString('Opening Balance', $text);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testThrowsWhenFileNotFound(): void
    {
        $parser    = $this->createMock(Parser::class);
        $extractor = new PdfTextExtractor($parser);

        $this->expectException(StatementOcrException::class);
        $extractor->extractText('/nonexistent/path/statement.pdf');
    }

    public function testThrowsWhenParserFails(): void
    {
        $mockParser = $this->createMock(Parser::class);
        $mockParser->method('parseFile')->willThrowException(new \Exception('parse error'));

        $tmpFile = tempnam(sys_get_temp_dir(), 'pdf_test_') . '.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake');

        try {
            $extractor = new PdfTextExtractor($mockParser);
            $this->expectException(StatementOcrException::class);
            $extractor->extractText($tmpFile);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testIsUsableTextReturnsTrueWhenLong(): void
    {
        $parser    = $this->createMock(Parser::class);
        $extractor = new PdfTextExtractor($parser);

        $this->assertTrue($extractor->isUsableText(str_repeat('a', 100)));
    }

    public function testIsUsableTextReturnsFalseWhenShort(): void
    {
        $parser    = $this->createMock(Parser::class);
        $extractor = new PdfTextExtractor($parser);

        $this->assertFalse($extractor->isUsableText('short'));
    }
}
