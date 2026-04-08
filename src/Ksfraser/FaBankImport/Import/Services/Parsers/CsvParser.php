<?php

namespace Ksfraser\FaBankImport\Import\Services\Parsers;

use Ksfraser\FaBankImport\Import\Services\ParserInterface;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * Configurable CSV Parser for bank statements
 *
 * Supports multiple CSV formats through flexible column mapping:
 * - Header-based mapping (WMMC pattern): Maps variable column names to standard fields
 * - Index-based mapping (BCR pattern): Uses fixed column positions
 * - Synonym support: Handles alternate column name variations
 *
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Import\Services\Parsers
 * @since 2.2.1
 */
class CsvParser implements ParserInterface
{
    /**
     * Synonym resolver service
     *
     * @var SynonymResolver
     */
    private SynonymResolver $synonymResolver;

    /**
     * Predefined column mappings for known bank formats
     *
     * Maps bank identifiers to standard field names
     *
     * @var array<string, array<string, string>>
     */
    private array $bankMappings = [
        'ro_wmmc' => [
            'date' => 'Date',
            'amount' => 'Amount',
            'merchant' => 'Merchant Name',
            'category' => 'Merchant Category',
            'description' => 'Activity Type',
            'reference' => 'Reference Number',
        ],
        'ro_bcr' => [
            'currency' => 6,
            'account' => 6,
            'startBalance' => 9,
            'amount' => 14,
            'endBalance' => 18,
            'reference' => 13,
        ],
        'ro_ing' => [
            'date' => 'Date',
            'amount' => 'Amount',
            'merchant' => 'Beneficiary',
            'description' => 'Description',
        ],
    ];

    /**
     * Constructor
     *
     * @param SynonymResolver|null $synonymResolver Optional custom synonym resolver
     */
    public function __construct(?SynonymResolver $synonymResolver = null)
    {
        $this->synonymResolver = $synonymResolver ?? new SynonymResolver();
    }

    /**
     * Parse a CSV bank statement file
     *
     * Supports flexible column mapping through:
     * 1. Explicit columnMapping in options parameter
     * 2. Header-based detection with synonym support
     * 3. Configuration by bank identifier
     *
     * @param string $filePath Path to CSV file
     * @param array<string, mixed> $options Parser-specific options
     *        - columnMapping: explicit field name mapping
     *        - bankIdentifier: predefined mapping (ro_wmmc, ro_bcr, etc.)
     *        - encoding: expected file encoding (defaults to auto-detect)
     *        - customSynonyms: runtime custom synonyms array
     *        - synonymConfigFile: path to JSON synonym config file
     * @return array<int, ParsedStatementDTO> Array of parsed statements
     *
     * @throws FileNotFoundException If file does not exist or is unreadable
     * @throws UnsupportedFileTypeException If file is not a valid CSV
     * @throws ParsingFailedException If CSV parsing fails
     * @throws EncodingMismatchException If encoding cannot be auto-detected
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
            throw ParsingFailedException::create('Failed to read CSV file', 0, $e);
        }

        // Detect encoding
        $encoding = $this->detectEncoding($content, $options);
        if ($encoding && $encoding !== 'UTF-8') {
            // Try to convert to UTF-8
            $converted = @iconv($encoding, 'UTF-8', $content);
            if ($converted === false) {
                throw EncodingMismatchException::create($encoding, 'UTF-8');
            }
            $content = $converted;
        }

        // Split into lines
        try {
            $lines = explode("\n", $content);
        } catch (\Exception $e) {
            throw ParsingFailedException::create('Failed to split CSV into lines', 0, $e);
        }

        if (empty($lines)) {
            throw ParsingFailedException::create('CSV file is empty');
        }

        // Extract header
        $headerLine = array_shift($lines);
        $headerValues = str_getcsv($headerLine);

        if (empty($headerValues)) {
            throw ParsingFailedException::create('CSV header row is empty or malformed', 1);
        }

        // Apply runtime customizations to synonym resolver
        $this->applySynonymOptions($options);

        // Get column mapping
        $columnMap = $this->getColumnMapping($headerValues, $options);

        // Parse data rows
        $statements = [];
        $lineNumber = 2;

        foreach ($lines as $line) {
            // Skip empty lines
            if (empty(trim($line))) {
                $lineNumber++;
                continue;
            }

            // Parse CSV row
            try {
                $values = str_getcsv($line);
            } catch (\Exception $e) {
                throw ParsingFailedException::withLineContent(
                    'Failed to parse CSV row',
                    $lineNumber,
                    $line
                );
            }

            // Map values to fields
            try {
                $row = $this->mapRowToFields($values, $columnMap);
                if (!empty($row)) {
                    $statements = $this->addTransactionToStatements($statements, $row);
                }
            } catch (\Exception $e) {
                throw ParsingFailedException::withLineContent(
                    'Failed to map CSV row to fields: ' . $e->getMessage(),
                    $lineNumber,
                    $line
                );
            }

            $lineNumber++;
        }

        // Convert statement arrays to ParsedStatementDTO objects
        $result = [];
        foreach ($statements as $statement) {
            $result[] = $this->createParsedStatementDTO($statement);
        }
        return $result;
    }

    /**
     * Get supported MIME types for CSV files
     *
     * @return array<int, string>
     */
    public function getSupportedTypes(): array
    {
        return ['text/csv', 'application/csv', 'text/plain'];
    }

    /**
     * Get parser name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'CSV Parser';
    }

    /**
     * Detect file encoding
     *
     * @param string $content File content
     * @param array<string, mixed> $options Parser options
     * @return string Detected encoding or empty string if unknown
     */
    private function detectEncoding(string $content, array $options): string
    {
        // Check if encoding specified in options
        if (!empty($options['encoding'])) {
            return $options['encoding'];
        }

        // Try to detect encoding using mb_detect_encoding
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

        // Check for BOM markers
        if (strpos($content, "\xEF\xBB\xBF") === 0) {
            return 'UTF-8';
        }
        if (strpos($content, "\xFF\xFE") === 0 || strpos($content, "\xFE\xFF") === 0) {
            return 'UTF-16';
        }

        // Default to UTF-8
        return 'UTF-8';
    }

    /**
     * Apply synonym configuration options to resolver
     *
     * @param array<string, mixed> $options Parser options
     * @return void
     */
    private function applySynonymOptions(array $options): void
    {
        // Load from config file if provided
        if (!empty($options['synonymConfigFile'])) {
            try {
                $this->synonymResolver->loadConfigFile($options['synonymConfigFile']);
            } catch (\Exception $e) {
                // Silently fall back to defaults
            }
        }

        // Apply custom runtime synonyms if provided
        if (!empty($options['customSynonyms']) && is_array($options['customSynonyms'])) {
            $this->synonymResolver->setRuntimeSynonyms($options['customSynonyms']);
        }
    }

    /**
     * Build column mapping from header row
     *
     * Supports three approaches:
     * 1. Explicit mapping in options['columnMapping']
     * 2. Bank identifier in options['bankIdentifier']
     * 3. Header-based detection with synonym matching
     *
     * @param array<int, string> $headerValues Values from header row
     * @param array<string, mixed> $options Parser options
     * @return array<int, string> Mapping of column index to standard field name
     */
    private function getColumnMapping(array $headerValues, array $options): array
    {
        // Check for explicit column mapping
        if (!empty($options['columnMapping']) && is_array($options['columnMapping'])) {
            return $this->buildExplicitMapping($headerValues, $options['columnMapping']);
        }

        // Check for bank identifier mapping
        if (!empty($options['bankIdentifier']) && isset($this->bankMappings[$options['bankIdentifier']])) {
            $mapping = $this->bankMappings[$options['bankIdentifier']];
            return $this->buildMappingFromBank($headerValues, $mapping);
        }

        // Auto-detect using header values and synonyms
        return $this->buildHeaderBasedMapping($headerValues);
    }

    /**
     * Build mapping from explicit column mapping configuration
     *
     * Maps user-provided field names to column indices
     *
     * @param array<int, string> $headerValues Values from CSV header
     * @param array<string, string> $columnMapping Field → Column Name mapping
     * @return array<int, string> Column index → Field name mapping
     */
    private function buildExplicitMapping(array $headerValues, array $columnMapping): array
    {
        $headerLower = array_map('strtolower', $headerValues);
        $mapping = [];

        foreach ($columnMapping as $fieldName => $headerName) {
            $index = array_search(strtolower($headerName), $headerLower);
            if ($index !== false) {
                $mapping[$index] = $fieldName;
            }
        }

        return $mapping;
    }

    /**
     * Build mapping from bank-specific configuration
     *
     * @param array<int, string> $headerValues Header values
     * @param array<string|int, string|int> $bankMapping Bank mapping config
     * @return array<int, string> Column index → Field name mapping
     */
    private function buildMappingFromBank(array $headerValues, array $bankMapping): array
    {
        $mapping = [];

        foreach ($bankMapping as $fieldName => $columnIdentifier) {
            // If identifier is numeric, use as direct index
            if (is_int($columnIdentifier)) {
                if (isset($headerValues[$columnIdentifier])) {
                    $mapping[$columnIdentifier] = $fieldName;
                }
            } else {
                // Find column by name
                $headerLower = array_map('strtolower', $headerValues);
                $index = array_search(strtolower($columnIdentifier), $headerLower);
                if ($index !== false) {
                    $mapping[$index] = $fieldName;
                }
            }
        }

        return $mapping;
    }

    /**
     * Build mapping by detecting headers and matching against synonyms
     *
     * Uses SynonymResolver to find standard field names by matching headers
     * against known synonyms with full support for custom and config-file synonyms
     *
     * @param array<int, string> $headerValues Header values from CSV
     * @return array<int, string> Column index → Field name mapping
     */
    private function buildHeaderBasedMapping(array $headerValues): array
    {
        $mapping = [];

        foreach ($headerValues as $index => $headerValue) {
            $fieldName = $this->synonymResolver->getFieldNameForHeader($headerValue, 'csv');
            if ($fieldName !== null) {
                $mapping[$index] = $fieldName;
            }
        }

        return $mapping;
    }

    /**
     * Map a row of CSV values to standard field names
     *
     * @param array<int, string|null> $values Row values
     * @param array<int, string> $columnMap Column index → Field name mapping
     * @return array<string, mixed> Mapped row data
     */
    private function mapRowToFields(array $values, array $columnMap): array
    {
        $row = [];

        foreach ($columnMap as $columnIndex => $fieldName) {
            if (isset($values[$columnIndex])) {
                $value = trim($values[$columnIndex], " \t\n\r\0\x0B\"");
                if ($value !== '') {
                    $row[$fieldName] = $value;
                }
            }
        }

        return $row;
    }

    /**
     * Add a transaction row to the appropriate statement
     *
     * Groups transactions by statement date or account
     *
     * @param array<string, mixed> $statements Current statements
     * @param array<string, mixed> $row Transaction row data
     * @return array<string, mixed> Updated statements
     */
    private function addTransactionToStatements(array $statements, array $row): array
    {
        // Use transaction date as statement key, or account if date unavailable
        $statementKey = $row['transactionDate'] ?? $row['account'] ?? 'default';

        if (!isset($statements[$statementKey])) {
            $statements[$statementKey] = [
                'date' => $row['transactionDate'] ?? '',
                'account' => $row['account'] ?? '',
                'currency' => $row['currency'] ?? '',
                'transactions' => [],
            ];
        }

        $statements[$statementKey]['transactions'][] = $row;

        return $statements;
    }

    /**
     * Create ParsedStatementDTO from collected statement data
     *
     * @param array<string, mixed> $statement Statement data
     * @return ParsedStatementDTO
     */
    private function createParsedStatementDTO(array $statement): ParsedStatementDTO
    {
        // Calculate opening/closing balances from transactions
        $openingBalance = 0.0;
        $closingBalance = 0.0;

        // Map transactions to expected format
        $transactions = [];
        foreach ($statement['transactions'] as $txn) {
            $amount = (float)($txn['amount'] ?? 0);
            $dc = strtoupper($txn['transactionDC'] ?? 'C');

            // Build transaction record with required fields
            $transaction = [
                'date' => $txn['transactionDate'] ?? '',
                'amount' => $amount,
                'dc' => $dc, // Debit/Credit indicator
                'description' => $txn['description'] ?? '',
                'merchant' => $txn['merchant'] ?? '',
                'reference' => $txn['reference'] ?? '',
            ];

            // Add optional fields if present
            if (isset($txn['category'])) {
                $transaction['category'] = $txn['category'];
            }
            if (isset($txn['account'])) {
                $transaction['counterparty_account'] = $txn['account'];
            }

            $transactions[] = $transaction;

            // Update running balance
            if ($dc === 'D') {
                $closingBalance -= $amount;
            } else {
                $closingBalance += $amount;
            }
        }

        return ParsedStatementDTO::create([
            'statementDate' => $statement['date'] ?? date('Y-m-d'),
            'accountReference' => $statement['account'] ?? 'UNKNOWN',
            'currency' => $statement['currency'] ?? 'USD',
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'transactions' => $transactions,
            'parserType' => 'csv',
            'metadata' => [
                'parser' => self::class,
                'parsed_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
