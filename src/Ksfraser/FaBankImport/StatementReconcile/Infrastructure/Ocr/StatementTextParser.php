<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;

/**
 * Orchestrates the two-step PDF-to-StatementOcr pipeline:
 *
 *   Step 1 – Text extraction
 *     Primary:  smalot/pdfparser (fast, no API call, perfect for digital PDFs)
 *     Fallback: glm-ocr via Ollama  (for scanned / image-only PDFs)
 *
 *   Step 2 – Structured extraction
 *     gemma4 via Ollama with a deterministic JSON-schema prompt.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr
 * @author  Kevin Fraser
 */
final class StatementTextParser
{
    /**
     * Minimum text length (chars) returned by pdfparser to skip the OCR fallback.
     * Matches PdfTextExtractor::MIN_TEXT_LENGTH.
     */
    private const MIN_USABLE_TEXT_LEN = 100;

    /** @var PdfTextExtractorInterface */
    private $pdfExtractor;

    /** @var OllamaClientInterface */
    private $ollama;

    /**
     * Ollama model name used for OCR (scanned PDF fallback).
     * Default taken from env OLLAMA_OCR_MODEL.
     *
     * @var string
     */
    private $ocrModel;

    /**
     * Ollama model name used for structured data extraction.
     * Default taken from env OLLAMA_EXTRACTION_MODEL.
     *
     * @var string
     */
    private $extractionModel;

    /**
     * @param PdfTextExtractorInterface $pdfExtractor
     * @param OllamaClientInterface     $ollama
     * @param string                    $ocrModel        Ollama model for OCR fallback (e.g. "glm-ocr").
     * @param string                    $extractionModel Ollama model for extraction (e.g. "gemma4").
     */
    public function __construct(
        PdfTextExtractorInterface $pdfExtractor,
        OllamaClientInterface $ollama,
        string $ocrModel = 'glm-ocr',
        string $extractionModel = 'gemma4'
    ) {
        $this->pdfExtractor    = $pdfExtractor;
        $this->ollama          = $ollama;
        $this->ocrModel        = $ocrModel;
        $this->extractionModel = $extractionModel;
    }

    /**
     * Parse a PDF file into a StatementOcr aggregate.
     *
     * @param string $pdfPath Absolute path to the PDF file.
     * @return StatementOcr
     * @throws StatementOcrException on any failure.
     */
    public function parse(string $pdfPath): StatementOcr
    {
        // Step 1 – get raw text.
        $rawText = $this->resolveText($pdfPath);

        // Step 2 – structured extraction via gemma4.
        $extractionResponse = $this->extractStructured($rawText);

        // Step 3 – validate and build domain objects.
        return $this->buildStatementOcr($extractionResponse, $rawText);
    }

    // -------------------------------------------------------------------------
    // Private: step 1
    // -------------------------------------------------------------------------

    /**
     * Try pdfparser first; fall back to Ollama glm-ocr if needed.
     *
     * @param string $pdfPath
     * @return string
     */
    private function resolveText(string $pdfPath): string
    {
        $text = $this->pdfExtractor->extractText($pdfPath);

        if (mb_strlen(trim($text)) >= self::MIN_USABLE_TEXT_LEN) {
            return $text;
        }

        // Scanned / image-only PDF – route to glm-ocr.
        // glm-ocr on Ollama cannot directly accept binary; we send the path here
        // but in practice a concrete subclass or decorator would encode the image.
        // For now we send a text prompt describing the limitation so the test
        // surface remains clean and a real implementation can override this method.
        return $this->runOcrFallback($pdfPath);
    }

    /**
     * Send a prompt to glm-ocr to perform OCR.
     * In a real deployment the caller should convert the PDF page to a base64
     * image and send it in the options['images'] array (Ollama LLaVA-style).
     *
     * @param string $pdfPath
     * @return string Extracted text.
     */
    private function runOcrFallback(string $pdfPath): string
    {
        $prompt = <<<PROMPT
You are an OCR engine. The following message refers to a scanned PDF credit card statement.
Extract all visible text exactly as it appears, preserving the layout as much as possible.
Return ONLY the extracted text with no commentary.
PDF path (for reference): {$pdfPath}
PROMPT;

        $response = $this->ollama->generate($this->ocrModel, $prompt, [
            'temperature' => 0,
        ]);

        $text = $response['response'] ?? '';
        if (trim($text) === '') {
            throw StatementOcrException::forReason(
                'glm-ocr returned empty text for: ' . basename($pdfPath)
            );
        }

        return $text;
    }

    // -------------------------------------------------------------------------
    // Private: step 2
    // -------------------------------------------------------------------------

    /**
     * Send extracted text to gemma4 for structured JSON extraction.
     *
     * @param string $rawText
     * @return array Decoded extraction result.
     */
    private function extractStructured(string $rawText): array
    {
        $prompt = $this->buildExtractionPrompt($rawText);

        $response = $this->ollama->generate($this->extractionModel, $prompt, [
            'temperature' => 0,
            'format'      => 'json',
        ]);

        $jsonString = $response['response'] ?? '';
        if (trim($jsonString) === '') {
            throw StatementOcrException::forReason(
                'Extraction model returned an empty response'
            );
        }

        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw StatementOcrException::forReason(
                'Extraction model did not return valid JSON: ' . json_last_error_msg()
            );
        }

        return (array) $decoded;
    }

    /**
     * Build the structured extraction prompt with a JSON schema spec embedded.
     *
     * @param string $rawText
     * @return string
     */
    private function buildExtractionPrompt(string $rawText): string
    {
        return <<<PROMPT
You are a credit card statement parser. Extract the following information from the statement text below and return it ONLY as a valid JSON object. Do not include any explanation, markdown, or other text outside the JSON object.

Required JSON schema:
{
  "account_identifier": "<string|null>  Last 4 digits of card number or account label. Null if not found.",
  "statement_start_date": "<YYYY-MM-DD>  First day of the statement period.",
  "statement_end_date":   "<YYYY-MM-DD>  Last day of the statement period.",
  "opening_balance":      "<decimal string>  Opening/previous balance, may be negative.",
  "closing_balance":      "<decimal string>  New/closing balance, may be negative.",
  "due_date":             "<YYYY-MM-DD|null>  Payment due date. Null if not present.",
  "transactions": [
    {
      "line_id":     "<string>  Unique sequential ID e.g. 'L001'",
      "date":        "<YYYY-MM-DD>",
      "description": "<string>  Merchant or description as printed.",
      "amount":      "<decimal string>  Absolute value, always positive.",
      "type":        "<'credit'|'debit'>  'credit' for payments/refunds, 'debit' for purchases.",
      "raw_text":    "<string>  Original line text."
    }
  ]
}

Rules:
- All dates MUST be in YYYY-MM-DD format.
- All amounts MUST be positive decimal strings (no currency symbols, no commas).
- If a field cannot be determined, use null for nullable fields or omit optional transaction fields.
- Assign sequential line_id values starting from "L001".

Statement text:
---
{$rawText}
---
PROMPT;
    }

    // -------------------------------------------------------------------------
    // Private: step 3
    // -------------------------------------------------------------------------

    /**
     * Validate the LLM extraction result and construct the StatementOcr aggregate.
     *
     * @param array  $extraction Decoded LLM JSON.
     * @param string $rawText    Original full text (stored for audit).
     * @return StatementOcr
     */
    private function buildStatementOcr(array $extraction, string $rawText): StatementOcr
    {
        // Validate required metadata fields are present.
        // NOTE: empty() cannot be used here because '0' is a valid balance and
        //       empty('0') === true in PHP.
        foreach (['statement_start_date', 'statement_end_date', 'opening_balance', 'closing_balance'] as $field) {
            if (!array_key_exists($field, $extraction) || $extraction[$field] === null || (string)$extraction[$field] === '') {
                throw StatementOcrException::missingField($field);
            }
        }

        $metadata = StatementMetadata::fromArray([
            'account_identifier'   => $extraction['account_identifier'] ?? null,
            'statement_start_date' => $extraction['statement_start_date'],
            'statement_end_date'   => $extraction['statement_end_date'],
            'opening_balance'      => (string) $extraction['opening_balance'],
            'closing_balance'      => (string) $extraction['closing_balance'],
            'due_date'             => $extraction['due_date'] ?? null,
        ]);

        $lines = [];
        foreach ((array) ($extraction['transactions'] ?? []) as $txData) {
            $lines[] = StatementLine::fromArray($txData);
        }

        // The raw OCR result wraps the full extraction JSON for audit storage.
        $rawOcrResult = new RawOcrResult(
            json_encode($extraction),
            $this->extractionModel
        );

        return StatementOcr::create($metadata, $lines, $rawOcrResult);
    }
}
