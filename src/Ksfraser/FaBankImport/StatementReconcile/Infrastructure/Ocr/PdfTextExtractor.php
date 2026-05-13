<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr;

use Smalot\PdfParser\Parser;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;

/**
 * Uses smalot/pdfparser to extract plain text from a PDF file.
 *
 * This works well for text-layer PDFs (e.g. digital bank statements).
 * For scanned / image-only PDFs where this returns empty or very short text,
 * the StatementTextParser will route the file to the Ollama vision fallback.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr
 * @author  Kevin Fraser
 */
final class PdfTextExtractor implements PdfTextExtractorInterface
{
    /** Minimum extracted characters before we consider the PDF text-layer usable. */
    public const MIN_TEXT_LENGTH = 100;

    /** @var Parser */
    private $parser;

    /**
     * @param Parser $parser Injected smalot/pdfparser Parser instance.
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function extractText(string $pdfPath): string
    {
        if (!is_file($pdfPath)) {
            throw StatementOcrException::forReason(
                "PDF file not found or is not a regular file: {$pdfPath}"
            );
        }

        if (!is_readable($pdfPath)) {
            throw StatementOcrException::forReason(
                "PDF file is not readable: {$pdfPath}"
            );
        }

        try {
            $pdf  = $this->parser->parseFile($pdfPath);
            $text = $pdf->getText();
        } catch (\Exception $e) {
            throw StatementOcrException::forReason(
                "smalot/pdfparser failed on '{$pdfPath}': " . $e->getMessage()
            );
        }

        // Normalise whitespace: collapse multiple blank lines to a single blank line.
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return (string) $text;
    }

    /**
     * Returns true when the extracted text is long enough to be useful for an
     * LLM extraction pass.  Short texts may indicate a scanned image-only PDF.
     *
     * @param string $text
     * @return bool
     */
    public function isUsableText(string $text): bool
    {
        return mb_strlen(trim($text)) >= self::MIN_TEXT_LENGTH;
    }
}
