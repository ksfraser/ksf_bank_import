<?php

namespace Ksfraser\FaBankImport\Import\Services\Parsers;

use Ksfraser\FaBankImport\Import\Services\ParserInterface;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * QIF (Quicken Interchange Format) Bank Statement Parser
 *
 * Parses QIF files using the ksfraser/qifparser library.
 *
 * QIF format is used by Quicken and QuickBooks for importing/exporting
 * financial data. While largely superseded by OFX, many institutions still support it.
 *
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Import\Services\Parsers
 * @since 2.2.1
 */
class QIFParser implements ParserInterface
{
    /**
     * Parse a QIF bank statement file
     *
     * Uses the ksfraser/qifparser library to parse QIF format
     * and normalizes output to ParsedStatementDTO objects.
     *
     * Supported options:
     * - bankId (string): Bank/Account identifier for vendor library (default: 'GENERIC')
     * - accountId (string): Account number for vendor library (default: '0000')
     * - currency (string): Currency code for parsing (default: 'USD')
     * - dateFormat (string): Date format ('MDY' or 'DMY', default: 'MDY')
     *
     * @param string $filePath Path to QIF file
     * @param array<string, mixed> $options Parser-specific options
     * @return array<int, ParsedStatementDTO> Array of parsed statements
     *
     * @throws FileNotFoundException If file does not exist or is unreadable
     * @throws UnsupportedFileTypeException If file is not QIF format
     * @throws ParsingFailedException If QIF parsing fails
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
            throw ParsingFailedException::create('Failed to read QIF file', 0, $e);
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

        // Validate it looks like QIF
        if (!$this->isValidQIFContent($content)) {
            throw UnsupportedFileTypeException::create('text/x-qif', $this->getSupportedTypes());
        }

        // Parse using QIF parser library
        try {
            // Extract vendor library parameters from options
            $bankId = $options['bankId'] ?? 'GENERIC';
            $accountId = $options['accountId'] ?? '0000';
            $currency = $options['currency'] ?? 'CAD';
            $dateFormat = $options['dateFormat'] ?? 'MDY';

            $qifParser = new \Ksfraser\QifParser\QifParser($bankId, $accountId, $currency, $dateFormat);
            $qifData = $qifParser->parse($content);
        } catch (\Exception $e) {
            throw ParsingFailedException::create(
                'Failed to parse QIF content: ' . $e->getMessage(),
                0,
                $e
            );
        }

        // Convert QIF data to ParsedStatementDTO objects
        return $this->convertQIFToDTO($qifData);
    }

    /**
     * Get supported MIME types for QIF files
     *
     * @return array<int, string>
     */
    public function getSupportedTypes(): array
    {
        return ['application/x-qif', 'text/x-qif', 'text/plain'];
    }

    /**
     * Get parser name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'QIF Parser';
    }

    /**
     * Detect file encoding
     *
     * @param string $content File content
     * @return string Detected encoding
     */
    private function detectEncoding(string $content): string
    {
        // Check for BOM markers
        if (strpos($content, "\xEF\xBB\xBF") === 0) {
            return 'UTF-8';
        }
        if (strpos($content, "\xFF\xFE") === 0 || strpos($content, "\xFE\xFF") === 0) {
            return 'UTF-16';
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
     * Validate content looks like QIF
     *
     * @param string $content File content
     * @return bool True if content appears to be QIF format
     */
    private function isValidQIFContent(string $content): bool
    {
        $content = trim($content);

        // QIF files typically start with !Type: or !Account:
        if (preg_match('/^!(?:Type|Account|Clear):/im', $content)) {
            return true;
        }

        // Check for QIF transaction indicators
        if (preg_match('/^[!D]:/m', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Convert QIF parser output to ParsedStatementDTO objects
     *
     * Maps QIF data structure to our standard DTO format
     *
     * @param mixed $qifData Parsed QIF data from library
     * @return array<int, ParsedStatementDTO> Array of DTOs
     *
     * @throws ParsingFailedException If conversion fails
     */
    private function convertQIFToDTO($qifData): array
    {
        try {
            $statements = [];

            // Handle array of statements or single statement
            if (!is_array($qifData)) {
                if (is_object($qifData) && method_exists($qifData, 'toArray')) {
                    $qifData = [$qifData->toArray()];
                } else {
                    $qifData = [$qifData];
                }
            }

            foreach ($qifData as $statement) {
                // Extract statement-level data
                $accountReference = $this->extractAccountReference($statement);
                $currency = $this->extractCurrency($statement);
                $statementDate = date('Y-m-d'); // QIF doesn't typically have statement date
                $openingBalance = (float)($statement['balance_opening'] ?? 0.0);
                $closingBalance = (float)($statement['balance_closing'] ?? 0.0);

                // Extract transactions
                $transactions = [];
                if (isset($statement['transactions']) && is_array($statement['transactions'])) {
                    $transactions = $this->convertQIFTransactions($statement['transactions']);
                }

                // Create DTO
                $statements[] = ParsedStatementDTO::create([
                    'statementDate' => $statementDate,
                    'accountReference' => $accountReference,
                    'currency' => $currency,
                    'openingBalance' => $openingBalance,
                    'closingBalance' => $closingBalance,
                    'transactions' => $transactions,
                    'parserType' => 'qif',
                    'metadata' => [
                        'parser' => self::class,
                        'parsed_at' => date('Y-m-d H:i:s'),
                        'source_format' => 'QIF',
                    ],
                ]);
            }

            return $statements;
        } catch (\Exception $e) {
            throw ParsingFailedException::create(
                'Failed to convert QIF data to DTOs: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Extract account reference from QIF statement
     *
     * @param array<string, mixed>|object $statement QIF statement data
     * @return string Account reference
     */
    private function extractAccountReference($statement): string
    {
        // Handle both array and object
        $data = is_array($statement) ? $statement : (array)$statement;

        return $data['account_id']
            ?? $data['accountid']
            ?? $data['acctid']
            ?? $data['account']
            ?? 'UNKNOWN';
    }

    /**
     * Extract currency from QIF statement
     *
     * @param array<string, mixed>|object $statement QIF statement data
     * @return string ISO 4217 currency code
     */
    private function extractCurrency($statement): string
    {
        $data = is_array($statement) ? $statement : (array)$statement;

        $currency = $data['currency']
            ?? $data['curdef']
            ?? $data['currency_code']
            ?? 'USD';

        // Ensure 3-letter code
        return strtoupper(substr($currency, 0, 3));
    }

    /**
     * Convert QIF transaction format to standard transaction format
     *
     * QIF transaction format:
     * !Type:Bank (or !Type:CCard)
     * D[date]
     * T[amount] (T = amount) 
     * P[payee]
     * M[memo]
     * C[cleared status]
     * ^
     *
     * @param array<int, array<string, mixed>> $qifTransactions QIF transactions
     * @return array<int, array<string, mixed>> Normalized transactions
     */
    private function convertQIFTransactions(array $qifTransactions): array
    {
        $transactions = [];

        foreach ($qifTransactions as $qifTxn) {
            $transactions[] = $this->normalizeQIFTransaction($qifTxn);
        }

        return $transactions;
    }

    /**
     * Normalize a single QIF transaction to standard format
     *
     * @param array<string, mixed>|object $qifTxn QIF transaction data
     * @return array<string, mixed> Normalized transaction
     */
    private function normalizeQIFTransaction($qifTxn): array
    {
        // Handle both array and object
        $txn = is_array($qifTxn) ? $qifTxn : (array)$qifTxn;

        // Extract and normalize core fields
        $amount = $this->extractTransactionAmount($txn);
        $debitCredit = $amount < 0 ? 'D' : 'C';
        $amountAbs = abs($amount);

        $transaction = [
            'date' => $this->normalizeQIFDate($this->extractField($txn, ['date', 'D'], '')),
            'amount' => $amountAbs,
            'dc' => $debitCredit,
            'description' => $this->extractField($txn, ['memo', 'M', 'desc'], ''),
            'merchant' => $this->extractField($txn, ['payee', 'P', 'name'], ''),
            'reference' => $this->extractField($txn, ['number', 'N', 'reference'], ''),
        ];

        // Add optional fields only if present
        $this->addOptionalField($transaction, $txn, 'category');
        $this->addOptionalField($transaction, $txn, 'cleared');

        return $transaction;
    }

    /**
     * Extract a field from transaction data using multiple possible keys
     *
     * @param array<string, mixed> $data Transaction data
     * @param array<string> $keys Possible field names (in priority order)
     * @param mixed $default Default value if not found
     * @return mixed Field value or default
     */
    private function extractField(array $data, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                return $data[$key];
            }
        }
        return $default;
    }

    /**
     * Extract transaction amount (handles multiple field names)
     *
     * @param array<string, mixed> $txn Transaction data
     * @return float Amount (negative for debit, positive for credit)
     */
    private function extractTransactionAmount(array $txn): float
    {
        return (float)($txn['amount'] ?? $txn['T'] ?? 0.0);
    }

    /**
     * Add optional field to transaction if present in source data
     *
     * @param array<string, mixed> &$transaction Target transaction array (modified in-place)
     * @param array<string, mixed> $source Source data
     * @param string $field Field name to check
     * @return void
     */
    private function addOptionalField(array &$transaction, array $source, string $field): void
    {
        if (isset($source[$field])) {
            $transaction[$field] = $source[$field];
        }
    }

    /**
     * Normalize QIF date format to YYYY-MM-DD
     *
     * QIF dates are typically M/D/Y format, sometimes M/D/YY
     *
     * @param string $dateStr QIF date string
     * @return string Normalized date in YYYY-MM-DD format
     */
    private function normalizeQIFDate(string $dateStr): string
    {
        if (empty($dateStr)) {
            return date('Y-m-d');
        }

        // Try parsing common QIF date formats
        $dateStr = trim($dateStr);

        // Try standard PHP date parsing
        $timestamp = @strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        // Manual parsing for M/D/Y or M/D/YY
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $dateStr, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = (int)$matches[3];

            // Handle 2-digit years
            if ($year < 100) {
                $year += ($year > 50) ? 1900 : 2000;
            }

            return "{$year}-{$month}-{$day}";
        }

        // Fallback to current date
        return date('Y-m-d');
    }
}
