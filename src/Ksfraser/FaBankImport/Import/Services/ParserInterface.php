<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * Contract for parsing import files
 *
 * Parsers handle different file formats:
 * - CSV files
 * - Excel files (.xls, .xlsx)
 * - OFX/QFX bank files
 * - Custom formats
 *
 * Output is normalized ParsedStatementDTO for pipeline.
 */
interface ParserInterface
{
    /**
     * Parse import file
     *
     * @param string $filePath Path to import file
     * @param array<string, mixed> $options Parser-specific options
     * @return array<string, mixed> Parsed statement data
     *
     * @throws FileNotFoundException If file does not exist
     * @throws UnsupportedFileTypeException If file type not supported
     * @throws ParsingFailedException If file parsing fails
     * @throws EncodingMismatchException If file encoding is incorrect
     */
    public function parse(string $filePath, array $options = []): array;

    /**
     * Get supported file types
     *
     * @return array<int, string> MIME types supported by parser
     */
    public function getSupportedTypes(): array;

    /**
     * Get parser name
     *
     * @return string
     */
    public function getName(): string;
}
