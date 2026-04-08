<?php

namespace Ksfraser\FaBankImport\Import\Services\Parsers;

use Ksfraser\FaBankImport\Import\Services\ParserInterface;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * OFX/QFX Bank Statement Parser
 *
 * Parses OFX (Open Financial Exchange) and QFX (Quicken Financial Exchange) files
 * using the ksfraser/ksf_ofxparser library.
 *
 * Supports:
 * - OFX 1.0 (text-based format)
 * - OFX 2.0 (XML-based format)
 * - QFX (Intuit's version)
 *
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Import\Services\Parsers
 * @since 2.2.1
 */
class OFXParser implements ParserInterface
{
    /**
     * Parse an OFX/QFX bank statement file
     *
     * Uses the ksfraser/ksf_ofxparser library to parse OFX/QFX formats
     * and normalizes output to ParsedStatementDTO objects.
     *
     * @param string $filePath Path to OFX/QFX file
     * @param array<string, mixed> $options Parser-specific options
     * @return array<int, ParsedStatementDTO> Array of parsed statements
     *
     * @throws FileNotFoundException If file does not exist or is unreadable
     * @throws UnsupportedFileTypeException If file is not OFX/QFX format
     * @throws ParsingFailedException If OFX parsing fails
     * @throws EncodingMismatchException If encoding cannot be detected
     */
    public function parse(string $filePath, array $options = []): array
    {
        // Validate file exists and is readable
        if (!file_exists($filePath)) {
            throw FileNotFoundException::create($filePath);
        }

        if (!is_readable($filePath)) {
            throw FileNotFoundException::withContext($filePath, 'File is not readable');
        }

        // Read file content
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw FileNotFoundException::withContext($filePath, 'Unable to read file');
            }
        } catch (\Exception $e) {
            throw ParsingFailedException::create('Failed to read OFX file', 0, $e);
        }

        // Detect encoding
        $encoding = $this->detectEncoding($content);
        if ($encoding && $encoding !== 'UTF-8') {
            $converted = @iconv($encoding, 'UTF-8', $content);
            if ($converted === false) {
                throw EncodingMismatchException::create($encoding, 'UTF-8');
            }
            $content = $converted;
        }

        // Validate it looks like OFX
        if (!$this->isValidOFXContent($content)) {
            throw UnsupportedFileTypeException::create('text/ofx', $this->getSupportedTypes());
        }

        // Parse using OfxParser library
        try {
            $ofxParser = new \Ksfraser\KsfOfxParser\OfxParser();
            $ofxData = $ofxParser->parse($content);
        } catch (\Exception $e) {
            throw ParsingFailedException::create(
                'Failed to parse OFX content: ' . $e->getMessage(),
                0,
                $e
            );
        }

        // Convert OFX data to ParsedStatementDTO objects
        return $this->convertOFXToDTO($ofxData);
    }

    /**
     * Get supported MIME types for OFX files
     *
     * @return array<int, string>
     */
    public function getSupportedTypes(): array
    {
        return ['application/vnd.intu.qbo', 'application/x-ofx', 'text/x-ofx', 'application/x-qfx'];
    }

    /**
     * Get parser name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'OFX Parser';
    }

    /**
     * Detect file encoding
     *
     * @param string $content File content
     * @return string Detected encoding
     */
    private function detectEncoding(string $content): string
    {
        // Check for OFX encoding declaration (ENCODING:UTF-8)
        if (preg_match('/ENCODING\s*:\s*([^\s\r\n]+)/i', $content, $matches)) {
            return strtoupper($matches[1]);
        }

        // Check for BOM markers
        if (strpos($content, "\xEF\xBB\xBF") === 0) {
            return 'UTF-8';
        }
        if (strpos($content, "\xFF\xFE") === 0 || strpos($content, "\xFE\xFF") === 0) {
            return 'UTF-16';
        }

        // Check for XML encoding declaration (OFX 2.0)
        if (preg_match('/<\?xml\s+.*encoding=["\']([^"\']+)["\']', $content, $matches)) {
            return strtoupper($matches[1]);
        }

        // Try to detect
        if (function_exists('mb_detect_encoding')) {
            $detected = mb_detect_encoding(
                $content,
                ['UTF-8', 'UTF-16', 'ISO-8859-1', 'Windows-1252'],
                true
            );
            if ($detected && $detected !== 'ASCII') {
                return $detected;
            }
        }

        return 'UTF-8';
    }

    /**
     * Validate content looks like OFX
     *
     * @param string $content File content
     * @return bool True if content appears to be OFX format
     */
    private function isValidOFXContent(string $content): bool
    {
        $content = trim($content);

        // Check for OFX 1.0 indicators (OFXHEADER, STMTRS, BANKMSGSRSV1)
        if (preg_match('/OFXHEADER|STMTRS|BANKMSGSRSV1|CREDITCARDMSGSRSV1|\<OFX\>/i', $content)) {
            return true;
        }

        // Check for OFX 2.0 XML indicators
        if (preg_match('/<\?xml[^>]*\?>|<OFX>|<ofx>/i', $content)) {
            return true;
        }

        // Check for QFX indicators
        if (preg_match('/!OFX|OFXHEADER/i', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Convert OFX parser output to ParsedStatementDTO objects
     *
     * Maps OFX data structure to our standard DTO format
     *
     * @param mixed $ofxData Parsed OFX data from library
     * @return array<int, ParsedStatementDTO> Array of DTOs
     *
     * @throws ParsingFailedException If conversion fails
     */
    private function convertOFXToDTO($ofxData): array
    {
        try {
            $statements = [];

            // Handle array of statements or single statement
            if (!is_array($ofxData)) {
                $ofxData = [$ofxData];
            }

            foreach ($ofxData as $statement) {
                // Extract statement-level data
                $accountReference = $this->extractAccountReference($statement);
                $currency = $this->extractCurrency($statement);
                $statementDate = $this->extractStatementDate($statement);
                $openingBalance = (float)($statement['balance_opening'] ?? 0.0);
                $closingBalance = (float)($statement['balance_closing'] ?? 0.0);

                // Extract transactions
                $transactions = [];
                if (isset($statement['transactions']) && is_array($statement['transactions'])) {
                    $transactions = $this->convertOFXTransactions($statement['transactions']);
                }

                // Create DTO
                $statements[] = ParsedStatementDTO::create([
                    'statementDate' => $statementDate,
                    'accountReference' => $accountReference,
                    'currency' => $currency,
                    'openingBalance' => $openingBalance,
                    'closingBalance' => $closingBalance,
                    'transactions' => $transactions,
                    'parserType' => 'ofx',
                    'metadata' => [
                        'parser' => self::class,
                        'parsed_at' => date('Y-m-d H:i:s'),
                        'source_format' => $this->detectOFXVersion($statement),
                    ],
                ]);
            }

            return $statements;
        } catch (\Exception $e) {
            throw ParsingFailedException::create(
                'Failed to convert OFX data to DTOs: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Extract account reference from OFX statement
     *
     * @param array<string, mixed> $statement OFX statement data
     * @return string Account reference
     */
    private function extractAccountReference(array $statement): string
    {
        return $statement['account_id']
            ?? $statement['accountid']
            ?? $statement['acctid']
            ?? 'UNKNOWN';
    }

    /**
     * Extract currency from OFX statement
     *
     * @param array<string, mixed> $statement OFX statement data
     * @return string ISO 4217 currency code
     */
    private function extractCurrency(array $statement): string
    {
        $currency = $statement['currency']
            ?? $statement['curdef']
            ?? $statement['currency_code']
            ?? 'USD';

        // Ensure 3-letter code
        return strtoupper(substr($currency, 0, 3));
    }

    /**
     * Extract statement date from OFX statement
     *
     * @param array<string, mixed> $statement OFX statement data
     * @return string Statement date in YYYY-MM-DD format
     */
    private function extractStatementDate(array $statement): string
    {
        $dateStr = $statement['statement_date']
            ?? $statement['statementdate']
            ?? $statement['dtasof']
            ?? date('Y-m-d');

        return $this->normalizeOFXDate($dateStr);
    }

    /**
     * Convert OFX transaction format to standard transaction format
     *
     * @param array<int, array<string, mixed>> $ofxTransactions OFX transactions
     * @return array<int, array<string, mixed>> Normalized transactions
     */
    private function convertOFXTransactions(array $ofxTransactions): array
    {
        $transactions = [];

        foreach ($ofxTransactions as $ofxTxn) {
            $transaction = [
                'date' => $this->normalizeOFXDate($ofxTxn['date'] ?? $ofxTxn['dtposted'] ?? ''),
                'amount' => (float)($ofxTxn['amount'] ?? 0.0),
                'dc' => $ofxTxn['type'] === 'DEBIT' ? 'D' : 'C',
                'description' => $ofxTxn['name']
                    ?? $ofxTxn['desc']
                    ?? $ofxTxn['memo']
                    ?? '',
                'merchant' => $ofxTxn['payee']
                    ?? $ofxTxn['name']
                    ?? '',
                'reference' => $ofxTxn['fitid']
                    ?? $ofxTxn['id']
                    ?? $ofxTxn['reference']
                    ?? '',
            ];

            // Add optional fields
            if (isset($ofxTxn['checknum'])) {
                $transaction['check_number'] = $ofxTxn['checknum'];
            }
            if (isset($ofxTxn['memo'])) {
                $transaction['memo'] = $ofxTxn['memo'];
            }

            $transactions[] = $transaction;
        }

        return $transactions;
    }

    /**
     * Normalize OFX date format to YYYY-MM-DD
     *
     * OFX dates are typically in format: YYYYMMDD or YYYYMMDDHHMMSS
     *
     * @param string $dateStr OFX date string
     * @return string Normalized date in YYYY-MM-DD format
     */
    private function normalizeOFXDate(string $dateStr): string
    {
        if (empty($dateStr)) {
            return date('Y-m-d');
        }

        // Extract first 8 characters (YYYYMMDD)
        $dateStr = substr($dateStr, 0, 8);

        if (strlen($dateStr) === 8 && is_numeric($dateStr)) {
            $year = substr($dateStr, 0, 4);
            $month = substr($dateStr, 4, 2);
            $day = substr($dateStr, 6, 2);

            return "{$year}-{$month}-{$day}";
        }

        // Fallback to current date
        return date('Y-m-d');
    }

    /**
     * Detect OFX version from statement data
     *
     * @param array<string, mixed> $statement OFX statement data
     * @return string OFX version identifier
     */
    private function detectOFXVersion(array $statement): string
    {
        if (isset($statement['version'])) {
            return 'OFX ' . $statement['version'];
        }

        if (isset($statement['format'])) {
            return strtoupper($statement['format']);
        }

        return 'OFX Unknown';
    }
}
