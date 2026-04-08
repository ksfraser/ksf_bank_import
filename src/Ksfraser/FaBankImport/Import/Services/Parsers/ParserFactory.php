<?php

namespace Ksfraser\FaBankImport\Import\Services\Parsers;

use Ksfraser\FaBankImport\Import\Services\ParserInterface;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;

/**
 * Parser Factory - Detects file type and returns appropriate parser
 *
 * Uses MIME type detection (finfo) with extension fallback to determine
 * which parser to instantiate for a given file.
 *
 * Supported formats:
 * - CSV (Comma-Separated Values)
 * - OFX (Open Financial Exchange)
 * - QFX (Quicken Financial Exchange - OFX variant)
 * - QIF (Quicken Interchange Format)
 * - MT940 (ISO 20022 SWIFT standard)
 *
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Import\Services\Parsers
 * @since 2.2.1
 */
class ParserFactory
{
    /**
     * MIME type to parser mapping
     *
     * @var array<string, string>
     */
    private const MIME_TO_PARSER = [
        // CSV formats
        'text/csv' => CsvParser::class,
        'text/plain' => CsvParser::class,
        'application/vnd.ms-excel' => CsvParser::class,

        // OFX formats
        'application/x-ofx' => OFXParser::class,
        'text/x-ofx' => OFXParser::class,
        'application/vnd.intu.qbo' => OFXParser::class,

        // QFX format (OFX variant)
        'application/x-qfx' => OFXParser::class,
        'application/vnd.intu.qfx' => OFXParser::class,

        // QIF formats
        'application/x-qif' => QIFParser::class,
        'text/x-qif' => QIFParser::class,

        // MT940
        'application/x-mt940' => CsvParser::class, // Placeholder for future MT940Parser
        'text/x-mt940' => CsvParser::class,
    ];

    /**
     * File extension to parser mapping (fallback)
     *
     * @var array<string, string>
     */
    private const EXTENSION_TO_PARSER = [
        'csv' => CsvParser::class,
        'txt' => CsvParser::class,

        'ofx' => OFXParser::class,
        'qfx' => OFXParser::class,

        'qif' => QIFParser::class,

        'mt940' => CsvParser::class,
    ];

    /**
     * Supported file extensions (user-friendly)
     *
     * @var array<int, string>
     */
    private const SUPPORTED_EXTENSIONS = [
        'csv', 'txt', 'ofx', 'qfx', 'qif', 'mt940',
    ];

    /**
     * Create appropriate parser for file
     *
     * Detects file type and returns instantiated parser.
     *
     * @param string $filePath Path to file to parse
     * @return ParserInterface Parser instance suitable for file
     *
     * @throws FileNotFoundException If file does not exist
     * @throws UnsupportedFileTypeException If file type is not supported
     */
    public function create(string $filePath): ParserInterface
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            throw FileNotFoundException::create($filePath);
        }

        // Detect file type
        $mimeType = $this->detectFileType($filePath);

        // Map MIME type to parser
        if (isset(self::MIME_TO_PARSER[$mimeType])) {
            $parserClass = self::MIME_TO_PARSER[$mimeType];
            return new $parserClass();
        }

        // Try extension fallback
        $extension = $this->getExtension($filePath);
        if (isset(self::EXTENSION_TO_PARSER[$extension])) {
            $parserClass = self::EXTENSION_TO_PARSER[$extension];
            return new $parserClass();
        }

        // Unknown format
        throw UnsupportedFileTypeException::create(
            $mimeType,
            self::SUPPORTED_EXTENSIONS
        );
    }

    /**
     * Detect file MIME type
     *
     * Uses finfo_file() for reliable detection with extension fallback.
     *
     * @param string $filePath Path to file
     * @return string MIME type
     */
    private function detectFileType(string $filePath): string
    {
        // Try finfo detection
        if (function_exists('finfo_file')) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $mimeType = finfo_file($finfo, $filePath);
                    finfo_close($finfo);

                    if ($mimeType !== false) {
                        return $mimeType;
                    }
                }
            } catch (\Exception $e) {
                // Fall through to extension-based detection
            }
        }

        // Extension-based fallback
        return $this->getMimeTypeFromExtension($this->getExtension($filePath));
    }

    /**
     * Get file extension (lowercase)
     *
     * @param string $filePath Path to file
     * @return string Extension without leading dot, lowercase
     */
    private function getExtension(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return strtolower($extension ?? '');
    }

    /**
     * Get MIME type from file extension
     *
     * @param string $extension File extension (without dot)
     * @return string MIME type or application/octet-stream if unknown
     */
    private function getMimeTypeFromExtension(string $extension): string
    {
        $mimeMap = [
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'ofx' => 'text/x-ofx',
            'qfx' => 'application/x-qfx',
            'qif' => 'application/x-qif',
            'mt940' => 'application/x-mt940',
        ];

        return $mimeMap[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Get list of available parsers
     *
     * @return array<string, array<string, mixed>> Parser information
     */
    public function getAvailableParsers(): array
    {
        return [
            'csv' => [
                'name' => 'CSV Parser',
                'class' => CsvParser::class,
                'extensions' => ['csv', 'txt'],
                'mimeTypes' => ['text/csv', 'text/plain', 'application/vnd.ms-excel'],
                'description' => 'Comma-Separated Values bank statements',
            ],
            'ofx' => [
                'name' => 'OFX Parser',
                'class' => OFXParser::class,
                'extensions' => ['ofx', 'qfx'],
                'mimeTypes' => ['text/x-ofx', 'application/x-ofx', 'application/x-qfx', 'application/vnd.intu.qbo'],
                'description' => 'Open Financial Exchange format (OFX 1.0, OFX 2.0, QFX)',
            ],
            'qif' => [
                'name' => 'QIF Parser',
                'class' => QIFParser::class,
                'extensions' => ['qif'],
                'mimeTypes' => ['application/x-qif', 'text/x-qif'],
                'description' => 'Quicken Interchange Format statements',
            ],
        ];
    }

    /**
     * Check if file type is supported
     *
     * @param string $filePath Path to file
     * @return bool True if file type is supported
     */
    public function isSupported(string $filePath): bool
    {
        try {
            $this->create($filePath);
            return true;
        } catch (UnsupportedFileTypeException $e) {
            return false;
        }
    }

    /**
     * Get list of supported file extensions
     *
     * @return array<int, string> Supported extensions (without dots)
     */
    public function getSupportedExtensions(): array
    {
        return self::SUPPORTED_EXTENSIONS;
    }
}
