<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Infrastructure\Ocr;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClientInterface;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\PdfTextExtractorInterface;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\StatementTextParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\StatementTextParser
 */
class StatementTextParserTest extends TestCase
{
    /** Minimal valid extraction JSON gemma4 would return. */
    private function validExtractionJson(array $overrides = []): string
    {
        $base = [
            'account_identifier'   => '9999',
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '1200.50',
            'due_date'             => '2026-04-20',
            'transactions'         => [
                [
                    'line_id'     => 'L001',
                    'date'        => '2026-03-15',
                    'description' => 'Amazon Prime',
                    'amount'      => '14.99',
                    'type'        => 'debit',
                    'raw_text'    => '15 MAR AMAZON 14.99',
                ],
            ],
        ];

        return json_encode(array_merge($base, $overrides));
    }

    private function makePdfExtractor(string $extractedText): PdfTextExtractorInterface
    {
        $mock = $this->createMock(PdfTextExtractorInterface::class);
        $mock->method('extractText')->willReturn($extractedText);
        return $mock;
    }

    private function makeOllama(string $responseJson): OllamaClientInterface
    {
        $mock = $this->createMock(OllamaClientInterface::class);
        $mock->method('generate')->willReturn(['response' => $responseJson]);
        return $mock;
    }

    public function testParseReturnsStatementOcrOnValidInput(): void
    {
        $pdf    = $this->makePdfExtractor(str_repeat('STATEMENT TEXT ', 20));
        $ollama = $this->makeOllama($this->validExtractionJson());

        $parser = new StatementTextParser($pdf, $ollama, 'glm-ocr', 'gemma4');
        $tmpPdf = tempnam(sys_get_temp_dir(), 'stmt_') . '.pdf';
        file_put_contents($tmpPdf, '%PDF fake');

        try {
            $ocr = $parser->parse($tmpPdf);
            $this->assertSame('9999', $ocr->getMetadata()->getAccountIdentifier());
            $this->assertSame(1, $ocr->getLineCount());
        } finally {
            @unlink($tmpPdf);
        }
    }

    public function testParseThrowsWhenExtractionMissingRequiredField(): void
    {
        $pdf    = $this->makePdfExtractor(str_repeat('X', 200));
        $ollama = $this->makeOllama($this->validExtractionJson(['statement_start_date' => '']));

        $parser = new StatementTextParser($pdf, $ollama);
        $tmpPdf = tempnam(sys_get_temp_dir(), 'stmt_') . '.pdf';
        file_put_contents($tmpPdf, '%PDF fake');

        try {
            $this->expectException(StatementOcrException::class);
            $parser->parse($tmpPdf);
        } finally {
            @unlink($tmpPdf);
        }
    }

    public function testParseThrowsWhenExtractionModelReturnsEmptyResponse(): void
    {
        $pdf    = $this->makePdfExtractor(str_repeat('X', 200));
        $ollama = $this->createMock(OllamaClientInterface::class);
        $ollama->method('generate')->willReturn(['response' => '']);

        $parser = new StatementTextParser($pdf, $ollama);
        $tmpPdf = tempnam(sys_get_temp_dir(), 'stmt_') . '.pdf';
        file_put_contents($tmpPdf, '%PDF fake');

        try {
            $this->expectException(StatementOcrException::class);
            $parser->parse($tmpPdf);
        } finally {
            @unlink($tmpPdf);
        }
    }

    public function testParseWithZeroTransactionsStillValid(): void
    {
        $pdf    = $this->makePdfExtractor(str_repeat('STATEMENT TEXT ', 20));
        $ollama = $this->makeOllama($this->validExtractionJson(['transactions' => []]));

        $parser = new StatementTextParser($pdf, $ollama);
        $tmpPdf = tempnam(sys_get_temp_dir(), 'stmt_') . '.pdf';
        file_put_contents($tmpPdf, '%PDF fake');

        try {
            $ocr = $parser->parse($tmpPdf);
            $this->assertSame(0, $ocr->getLineCount());
        } finally {
            @unlink($tmpPdf);
        }
    }

    public function testFallbackToOcrModelWhenTextTooShort(): void
    {
        // pdfparser returns short text → should call glm-ocr, then gemma4.
        $pdf = $this->createMock(PdfTextExtractorInterface::class);
        $pdf->method('extractText')->willReturn('short');

        $callLog = [];
        $ollama  = $this->createMock(OllamaClientInterface::class);
        $ollama->method('generate')
            ->willReturnCallback(static function (string $model, string $prompt) use (&$callLog): array {
                $callLog[] = $model;
                if ($model === 'glm-ocr') {
                    return ['response' => str_repeat('OCR TEXT ', 30)];
                }
                // gemma4 extraction call
                return ['response' => json_encode([
                    'account_identifier'   => null,
                    'statement_start_date' => '2026-01-01',
                    'statement_end_date'   => '2026-01-31',
                    'opening_balance'      => '0',
                    'closing_balance'      => '0',
                    'transactions'         => [],
                ])];
            });

        $parser = new StatementTextParser($pdf, $ollama, 'glm-ocr', 'gemma4');
        $tmpPdf = tempnam(sys_get_temp_dir(), 'stmt_') . '.pdf';
        file_put_contents($tmpPdf, '%PDF fake');

        try {
            $parser->parse($tmpPdf);
            $this->assertContains('glm-ocr', $callLog, 'Should have called glm-ocr for fallback OCR');
            $this->assertContains('gemma4', $callLog, 'Should have called gemma4 for structured extraction');
        } finally {
            @unlink($tmpPdf);
        }
    }
}
