<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Exception thrown when parser fails to process file
 *
 * Indicates parsing errors such as:
 * - Invalid file format or structure
 * - Unsupported file type
 * - Corrupted data in file
 * - Encoding issues
 */
class ParserException extends ImportException
{
    /**
     * Create exception for unsupported file type
     *
     * @param string $fileType The unsupported MIME type or extension
     * @param array<string> $supported List of supported types
     * @return self
     */
    public static function unsupportedFileType(string $fileType, array $supported): self
    {
        return new self(
            sprintf(
                'Unsupported file type: %s. Supported types: %s',
                $fileType,
                implode(', ', $supported)
            )
        );
    }

    /**
     * Create exception for parsing failure
     *
     * @param string $reason The reason for parsing failure
     * @param int $line Optional line number where error occurred
     * @return self
     */
    public static function parsingFailed(string $reason, int $line = 0): self
    {
        $message = 'Failed to parse file: ' . $reason;
        if ($line > 0) {
            $message .= " (line {$line})";
        }
        return new self($message);
    }

    /**
     * Create exception for missing required file
     *
     * @param string $filePath The missing file path
     * @return self
     */
    public static function fileNotFound(string $filePath): self
    {
        return new self("File not found: {$filePath}");
    }

    /**
     * Create exception for encoding issues
     *
     * @param string $detectedEncoding The detected encoding
     * @param string $expectedEncoding The expected encoding
     * @return self
     */
    public static function encodingMismatch(string $detectedEncoding, string $expectedEncoding): self
    {
        return new self(
            "Encoding mismatch: detected {$detectedEncoding}, expected {$expectedEncoding}"
        );
    }
}
