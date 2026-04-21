<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;

/**
 * Contract for extracting plain text from a PDF file.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr
 * @author  Kevin Fraser
 */
interface PdfTextExtractorInterface
{
    /**
     * Extract all text content from a PDF, preserving page breaks as "\n\n".
     *
     * @param string $pdfPath  Absolute filesystem path to the PDF file.
     * @return string          Extracted plain text (may be empty for image-only PDFs).
     * @throws StatementOcrException if the file cannot be read or parsed.
     */
    public function extractText(string $pdfPath): string;
}
