<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\ParserException;

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
     * @throws ParserException If parsing fails
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
