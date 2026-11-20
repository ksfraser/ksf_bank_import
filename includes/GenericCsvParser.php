<?php

/**
 * Generic CSV Parser with Intelligent Field Mapping
 * 
 * Abstract base class for CSV parsers that uses templates and intelligent field mapping.
 * Handles common CSV parsing logic and allows bank-specific customization.
 * 
 * Workflow:
 * 1. Parse CSV file
 * 2. Try to find existing template matching the headers
 * 3. If no template found, suggest mappings and show review screen
 * 4. Use mapping (from template or manual) to parse transactions
 * 5. Generate statements compatible with FrontAccounting bank import
 * 
 * @author Kevin Fraser / GitHub Copilot
 * @since 20260112
 * @version 1.0.0
 */

require_once(__DIR__ . '/CsvFieldMapper.php');
require_once(__DIR__ . '/CsvMappingTemplate.php');
require_once(__DIR__ . '/csv_mapping_review.php');
if (!class_exists('parser')) {
    include_once(__DIR__ . '/../parser.php');
}
if (!class_exists('statement')) {
    @include_once(__DIR__ . '/../includes.inc');
}

abstract class GenericCsvParser extends parser {
    
    /** @var string Bank identifier (e.g., 'manulife', 'wmmc') */
    protected $bankName;
    
    /** @var CsvFieldMapper */
    protected $fieldMapper;
    
    /** @var CsvMappingTemplate */
    protected $templateMgr;
    
    /** @var array Current field mapping */
    protected $mapping;
    
    /** @var bool Whether to skip review screen if template found */
    protected $autoApplyTemplate = true;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->fieldMapper = new CsvFieldMapper();
        $this->templateMgr = new CsvMappingTemplate();
        $this->bankName = $this->getBankName();
    }
    
    /**
     * Get bank identifier (must be implemented by subclass)
     * 
     * @return string Bank name (e.g., 'manulife')
     */
    abstract protected function getBankName();
    
    /**
     * Parse CSV content
     * 
     * @param string $content CSV file content
     * @param array $static_data Static data (account, currency, etc.)
     * @param bool $debug Enable debug output
     * @return array Array of statement objects
     */
    public function parse($content, $static_data = array(), $debug = true) {
        // Split into lines (CRLF from Windows exports must not leak a \r into
        // the last column of every row).
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (empty($lines)) {
            throw new Exception("Empty CSV file");
        }
        
        // Parse header
        $headerLine = array_shift($lines);
        $csvHeaders = $this->parseCsvLine($headerLine);
        
        if ($debug) {
            echo "CSV Headers found: " . implode(', ', $csvHeaders) . "\n";
        }
        
        // Parse first few rows as sample data
        $sampleData = $this->parseSampleData($lines, $csvHeaders, 5);
        
        // Try to find or create mapping
        $this->mapping = $this->getOrCreateMapping($csvHeaders, $sampleData, $debug);
        
        if ($this->mapping === null) {
            // Mapping review required - show UI and halt
            $this->showMappingReview($csvHeaders, $sampleData);
            return [];
        }
        
        // Parse all data rows using the mapping
        $statements = $this->parseRows($lines, $csvHeaders, $static_data, $debug);
        
        return $statements;
    }
    
    /**
     * Get or create field mapping
     * 
     * @param array $csvHeaders CSV headers
     * @param array $sampleData Sample data rows
     * @param bool $debug Debug mode
     * @return array|null Mapping or null if review required
     */
    protected function getOrCreateMapping($csvHeaders, $sampleData, $debug) {
        // Check for existing template
        $template = $this->templateMgr->findMatchingTemplate($csvHeaders, $this->bankName);
        
        if ($template !== null) {
            if ($debug) {
                echo "Found existing template: {$template['bank_name']}\n";
                echo "Template created: {$template['created']}\n";
            }
            
            if ($this->autoApplyTemplate) {
                return $template['mapping'];
            }
        }
        
        // No template or not auto-applying - suggest mapping
        $suggestedMapping = $this->fieldMapper->suggestMapping($csvHeaders, $sampleData);
        
        if ($debug) {
            echo "Suggested mapping:\n";
            print_r($suggestedMapping);
        }
        
        // Evaluate mapping quality
        $evaluation = $this->fieldMapper->evaluateMapping($suggestedMapping);
        
        if ($debug) {
            echo "Mapping quality: {$evaluation['quality']} ({$evaluation['score']}%)\n";
        }
        
        // If mapping is excellent and auto-apply is on, use it
        if ($evaluation['quality'] === 'excellent' && $this->autoApplyTemplate) {
            // Save as template for future use
            $this->templateMgr->saveTemplate($this->bankName, $csvHeaders, $suggestedMapping, [
                'auto_created' => true,
                'created_by' => 'system',
                'quality' => $evaluation['quality']
            ]);
            return $suggestedMapping;
        }
        
        // Otherwise, require manual review
        return null;
    }
    
    /**
     * Show mapping review screen
     * 
     * @param array $csvHeaders CSV headers
     * @param array $sampleData Sample data
     */
    protected function showMappingReview($csvHeaders, $sampleData) {
        display_csv_mapping_review($this->bankName, $csvHeaders, $sampleData);
        exit; // Halt execution to show UI
    }
    
    /**
     * Delimiter chosen for the current file; null until the first line is read.
     *
     * @var string|null
     */
    protected $delimiter = null;

    /**
     * Guess the delimiter from the busiest candidate.
     *
     * Gmail/Excel previews frequently hand back tab-separated text while the
     * downloaded file is comma-separated (or the reverse), so the parser must
     * not assume either.
     *
     * @param string $line Representative line (usually the header row).
     * @return string Delimiter character.
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function detectDelimiter($line) {
        $tabs = substr_count($line, "\t");
        $commas = substr_count($line, ',');
        $semicolons = substr_count($line, ';');

        if ($tabs > $commas && $tabs >= $semicolons) {
            return "\t";
        }
        if ($semicolons > $commas) {
            return ';';
        }

        return ',';
    }

    /**
     * Split one CSV line, honouring the file's detected delimiter.
     *
     * @param string $line Line to split.
     * @return array<int,string>
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function parseCsvLine($line) {
        if ($this->delimiter === null) {
            $this->delimiter = $this->detectDelimiter($line);
        }

        $fields = str_getcsv((string) $line, $this->delimiter);

        // A copy/paste can leave a single line in the other delimiter. Only
        // switch when the alternative splits into more fields, so quoted
        // values that merely contain a comma are left alone.
        $alternatives = array_values(array_diff(array(",", "\t", ';'), array($this->delimiter)));
        foreach ($alternatives as $alternative) {
            if (strpos((string) $line, $alternative) === false) {
                continue;
            }
            $candidate = str_getcsv((string) $line, $alternative);
            if (count($candidate) > count($fields)) {
                $fields = $candidate;
            }
        }

        return $fields;
    }
    
    /**
     * Parse sample data rows
     * 
     * @param array $lines All data lines
     * @param array $headers CSV headers
     * @param int $count Number of samples to parse
     * @return array Array of associative arrays
     */
    protected function parseSampleData($lines, $headers, $count = 5) {
        $samples = [];
        $parsed = 0;
        
        foreach ($lines as $line) {
            if ($parsed >= $count) {
                break;
            }
            
            if (strlen(trim($line)) == 0) {
                continue;
            }
            
            $fields = $this->parseCsvLine($line);
            if (count($fields) === count($headers)) {
                $samples[] = array_combine($headers, $fields);
                $parsed++;
            }
        }
        
        return $samples;
    }
    
    /**
     * Parse all rows using mapping
     * 
     * @param array $lines Data lines
     * @param array $csvHeaders CSV headers
     * @param array $static_data Static data
     * @param bool $debug Debug mode
     * @return array Array of statement objects
     */
    protected function parseRows($lines, $csvHeaders, $static_data, $debug) {
        $statements = [];
        
        foreach ($lines as $lineNum => $line) {
            if (strlen(trim($line)) == 0) {
                continue;
            }
            
            $fields = $this->parseCsvLine($line);
            if (count($fields) !== count($csvHeaders)) {
                if ($debug) {
                    echo "Warning: Line " . ($lineNum + 2) . " has " . count($fields) . " fields, expected " . count($csvHeaders) . "\n";
                }
                continue;
            }
            
            // Combine with headers
            $row = array_combine($csvHeaders, $fields);
            
            // Map to our fields
            $mappedRow = $this->applyMapping($row);
            
            // Skip if missing required fields
            if (!$this->validateMappedRow($mappedRow, $debug)) {
                continue;
            }
            
            // Create or update statement
            $statementId = $this->getStatementId($mappedRow);
            
            if (!isset($statements[$statementId])) {
                $statements[$statementId] = $this->createStatement($mappedRow, $static_data);
            }
            
            // Create transaction
            $transaction = $this->createTransaction($mappedRow, $static_data);
            $statements[$statementId]->addTransaction($transaction);
        }
        
        return $statements;
    }
    
    /**
     * Apply mapping to a data row
     * 
     * @param array $row Associative array with CSV headers as keys
     * @return array Associative array with our field names as keys
     */
    protected function applyMapping($row) {
        $mapped = [];
        
        foreach ($this->mapping as $csvHeader => $ourField) {
            if (isset($row[$csvHeader])) {
                $mapped[$ourField] = $row[$csvHeader];
            }
        }
        
        return $mapped;
    }
    
    /**
     * Validate mapped row has required fields
     * 
     * @param array $mappedRow Mapped data row
     * @param bool $debug Debug mode
     * @return bool Valid
     */
    protected function validateMappedRow($mappedRow, $debug) {
        $required = ['date', 'description'];
        
        foreach ($required as $field) {
            if (empty($mappedRow[$field])) {
                if ($debug) {
                    echo "Warning: Missing required field '$field'\n";
                }
                return false;
            }
        }
        
        // Must have either 'amount' or both 'debit' and 'credit'
        if (empty($mappedRow['amount']) && (empty($mappedRow['debit']) && empty($mappedRow['credit']))) {
            if ($debug) {
                echo "Warning: Missing amount information\n";
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Get statement ID from mapped row
     * 
     * Default implementation uses date, can be overridden
     * 
     * @param array $mappedRow Mapped data row
     * @return string Statement ID
     */
    protected function getStatementId($mappedRow) {
        $date = $this->normalizeDate($mappedRow['date']);
        return $date;
    }
    
    /**
     * Create statement object
     * 
     * @param array $mappedRow First row of statement
     * @param array $static_data Static data
     * @return statement Statement object
     */
    protected function createStatement($mappedRow, $static_data) {
        $stmt = new statement();
        $stmt->bank = $static_data['bank_name'] ?? $this->bankName;
        $stmt->account = $static_data['account'] ?? 'UNKNOWN';
        $stmt->currency = $static_data['currency'] ?? 'CAD';
        $stmt->timestamp = $this->normalizeDate($mappedRow['date']);
        $stmt->startBalance = '0';
        $stmt->endBalance = '0';
        $stmt->number = '00000';
        $stmt->sequence = '0';
        $stmt->statementId = "{$stmt->timestamp}-{$stmt->number}-{$stmt->sequence}";
        
        return $stmt;
    }
    
    /**
     * Create transaction object from mapped row
     * 
     * @param array $mappedRow Mapped data row
     * @param array $static_data Static data
     * @return transaction Transaction object
     */
    protected function createTransaction($mappedRow, $static_data) {
        $trx = new transaction();

        $valueTimestamp = $this->normalizeDate($mappedRow['date']);
        $trx->valueTimestamp = $valueTimestamp;
        $trx->entryTimestamp = !empty($mappedRow['posted_date'])
            ? $this->normalizeDate($mappedRow['posted_date'])
            : $valueTimestamp;

        // Amount + direction. The importer reads transactionAmount (always
        // unsigned) and transactionDC (D debit, C credit); the legacy
        // $trx->amount property does not exist on the FA transaction class.
        $amount = 0.0;
        $isDebit = true;
        if (isset($mappedRow['amount']) && $mappedRow['amount'] !== '') {
            $amount = (float) $this->normalizeAmount($mappedRow['amount']);
            $isDebit = $amount < 0;
            $amount = abs($amount);
        } elseif (!empty($mappedRow['debit'])) {
            $amount = abs((float) $this->normalizeAmount($mappedRow['debit']));
        } elseif (!empty($mappedRow['credit'])) {
            $amount = abs((float) $this->normalizeAmount($mappedRow['credit']));
            $isDebit = false;
        }

        $trx->transactionAmount = $amount;
        $trx->transactionDC = $isDebit ? 'D' : 'C';
        $trx->transactionType = 'TRF';

        // Description/Memo
        $memo = isset($mappedRow['description']) ? (string) $mappedRow['description'] : '';
        $trx->memo = $memo;
        $trx->account = $this->extractPayeeName($memo);
        $trx->accountName = $trx->account;
        $trx->merchant = $trx->account;
        $trx->transactionTitle1 = $trx->account;
        $trx->transactionTitle = $trx->account;

        // The reference identifies the transaction for duplicate detection,
        // so it must not be empty when the bank did not supply one.
        $reference = isset($mappedRow['reference']) ? trim((string) $mappedRow['reference']) : '';
        if ($reference === '') {
            $reference = $valueTimestamp . '-' . substr(sha1($memo . '|' . $amount . '|' . $trx->transactionDC), 0, 10);
        }
        $trx->reference = $reference;
        $trx->transactionCode = $reference;
        // checknumber is an integer column: only a genuine cheque number fits.
        if (ctype_digit($reference)) {
            $trx->checknumber = $reference;
        }

        if (isset($mappedRow['category'])) {
            $trx->category = (string) $mappedRow['category'];
        }

        // Optional detail columns. A template may name any of the usual
        // spellings; absent columns stay empty.
        $detail = array(
            'city' => array('city'),
            'province' => array('province', 'state', 'province_state'),
            'country' => array('country'),
            'postalcode' => array('postalcode', 'postal_code', 'postal', 'zip'),
            'address' => array('address'),
            'rewards' => array('rewards'),
            'cardnumber' => array('cardnumber', 'card_number'),
            'nameoncard' => array('nameoncard', 'name_on_card', 'cardholder'),
        );
        foreach ($detail as $property => $keys) {
            foreach ($keys as $key) {
                if (isset($mappedRow[$key]) && (string) $mappedRow[$key] !== '') {
                    $trx->$property = (string) $mappedRow[$key];
                    break;
                }
            }
        }

        if ($trx->address === '' && ($trx->city !== '' || $trx->postalcode !== '')) {
            $trx->address = trim($trx->city . "\n" . $trx->province . "\n" . $trx->country . "\n" . $trx->postalcode);
        }

        return $trx;
    }
    
    /**
     * Normalize date to YYYY-MM-DD format
     * 
     * Can be overridden for bank-specific date formats
     * 
     * @param string $dateStr Date string
     * @return string Normalized date
     */
    protected function normalizeDate($dateStr) {
        // Try to parse with strtotime
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        // Fallback
        return $dateStr;
    }
    
    /**
     * Normalize amount to decimal number
     * 
     * Removes currency symbols, commas, etc.
     * 
     * @param string $amountStr Amount string
     * @return string Normalized amount
     */
    protected function normalizeAmount($amountStr) {
        // Remove currency symbols and commas
        $clean = preg_replace('/[^\d.\-+]/', '', $amountStr);
        return $clean;
    }
    
    /**
     * Extract payee name from memo
     * 
     * Can be overridden for bank-specific parsing
     * 
     * @param string $memo Transaction memo
     * @return string Payee name
     */
    protected function extractPayeeName($memo) {
        // Simple default: use first 50 chars
        return substr($memo, 0, 50);
    }
}
