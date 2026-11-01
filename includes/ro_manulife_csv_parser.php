<?php

/**
 * Manulife Bank CSV Parser
 * 
 * Parses CSV exports from Manulife Bank (Advantage Account, etc.)
 * Uses intelligent field mapping and template system from GenericCsvParser.
 * 
 * Manulife CSV Format:
 * "Advantage Account 1518404",01/01/2025,131.01,"Transfer From 1524001"
 * 
 * Four columns (no header row in sample):
 * 1. Account Name/Number
 * 2. Date (MM/DD/YYYY)
 * 3. Amount (negative for debits, positive for credits)
 * 4. Description
 * 
 * @author Kevin Fraser / GitHub Copilot
 * @since 20260112
 * @version 1.0.0
 */

if (!class_exists('GenericCsvParser')) {
    require_once(__DIR__ . '/GenericCsvParser.php');
}

class ro_manulife_csv_parser extends GenericCsvParser {
    
    /**
     * Get bank identifier
     * 
     * @return string
     */
    protected function getBankName() {
        return 'manulife';
    }
    
    /**
     * Parse CSV content
     * 
     * Overridden to handle Manulife's headerless format
     * 
     * @param string $content CSV file content
     * @param array $static_data Static data (account, currency, etc.)
     * @param bool $debug Enable debug output
     * @return array Array of statement objects
     */
    public function parse($content, $static_data = array(), $debug = true) {
        // Split into lines
        $lines = explode("\n", $content);
        if (empty($lines)) {
            throw new Exception("Empty CSV file");
        }
        
        // Check if first line looks like a header or data
        $firstLine = trim($lines[0]);
        $hasHeader = $this->detectHeader($firstLine);
        
        if ($hasHeader) {
            if ($debug) {
                echo "Detected header row\n";
            }
            // Use parent implementation
            return parent::parse($content, $static_data, $debug);
        }
        
        // No header - Manulife's format
        if ($debug) {
            echo "No header detected - using Manulife default format\n";
        }
        
        // Define expected columns
        $csvHeaders = ['Account', 'Date', 'Amount', 'Description'];
        
        // Create default mapping
        $this->mapping = [
            'Account' => 'account',
            'Date' => 'date',
            'Amount' => 'amount',
            'Description' => 'description'
        ];
        
        // Parse rows
        $statements = [];
        
        foreach ($lines as $lineNum => $line) {
            if (strlen(trim($line)) == 0) {
                continue;
            }
            
            $fields = $this->parseCsvLine($line);
            if (count($fields) !== 4) {
                if ($debug) {
                    echo "Warning: Line " . ($lineNum + 1) . " has " . count($fields) . " fields, expected 4\n";
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
            
            if ($debug) {
                echo "Parsed transaction: {$transaction->valueTimestamp} | {$transaction->amount} | {$transaction->memo}\n";
            }
        }
        
        if ($debug) {
            echo "Total statements created: " . count($statements) . "\n";
            $totalTransactions = 0;
            foreach ($statements as $stmt) {
                $totalTransactions += count($stmt->transactions);
            }
            echo "Total transactions: $totalTransactions\n";
        }
        
        return $statements;
    }
    
    /**
     * Detect if first line is a header
     * 
     * Manulife exports may or may not have headers
     * 
     * @param string $line First line of CSV
     * @return bool True if header detected
     */
    protected function detectHeader($line) {
        $fields = $this->parseCsvLine($line);
        
        if (count($fields) < 2) {
            return false;
        }
        
        // Check if second field looks like a date
        // Manulife uses MM/DD/YYYY format
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fields[1])) {
            return false; // Data row
        }
        
        // Check if first field looks like account name with number
        if (preg_match('/Advantage Account \d+/', $fields[0])) {
            return false; // Data row
        }
        
        // Likely a header
        return true;
    }
    
    /**
     * Normalize date from Manulife's MM/DD/YYYY format
     * 
     * @param string $dateStr Date string
     * @return string YYYY-MM-DD
     */
    protected function normalizeDate($dateStr) {
        // Manulife uses MM/DD/YYYY format
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $matches)) {
            $month = $matches[1];
            $day = $matches[2];
            $year = $matches[3];
            return "$year-$month-$day";
        }
        
        // Fallback to parent
        return parent::normalizeDate($dateStr);
    }
    
    /**
     * Create statement with Manulife-specific defaults
     * 
     * @param array $mappedRow First row of statement
     * @param array $static_data Static data
     * @return statement Statement object
     */
    protected function createStatement($mappedRow, $static_data) {
        $stmt = parent::createStatement($mappedRow, $static_data);
        
        // Override bank name
        $stmt->bank = 'Manulife Bank';
        
        // Use account from CSV if not in static_data
        if (empty($stmt->account) || $stmt->account === 'UNKNOWN') {
            if (isset($mappedRow['account'])) {
                // Extract account number from "Advantage Account 1518404"
                if (preg_match('/(\d+)$/', $mappedRow['account'], $matches)) {
                    $stmt->account = $matches[1];
                } else {
                    $stmt->account = $mappedRow['account'];
                }
            }
        }
        
        return $stmt;
    }
    
    /**
     * Extract payee name from Manulife's description format
     * 
     * Examples:
     * - "Transfer From 1524001" -> "Transfer From"
     * - "BPY AIRDRIE Utility" -> "BPY AIRDRIE"
     * - "POS SQ AIRDRIE CURLING CL SQ02W2VH" -> "AIRDRIE CURLING CL"
     * - "Interest Deposit" -> "Interest Deposit"
     * 
     * @param string $memo Transaction memo
     * @return string Payee name
     */
    protected function extractPayeeName($memo) {
        // Remove transaction type prefixes
        $cleaned = preg_replace('/^(Transfer From|Transfer To|BPY|TAX|POS SQ|Pay|Mobile Deposit)\s+/', '', $memo);
        
        // Remove trailing transaction IDs and reference numbers
        $cleaned = preg_replace('/\s+[A-Z0-9]{6,}$/', '', $cleaned);
        
        // Take first meaningful part
        $parts = explode(' ', trim($cleaned));
        $name = implode(' ', array_slice($parts, 0, 3));
        
        return empty($name) ? $memo : $name;
    }
    
    /**
     * Create transaction with Manulife-specific logic
     * 
     * @param array $mappedRow Mapped data row
     * @param array $static_data Static data
     * @return transaction Transaction object
     */
    protected function createTransaction($mappedRow, $static_data) {
        $trx = parent::createTransaction($mappedRow, $static_data);
        
        // Manulife's amount is signed (negative = debit, positive = credit)
        // This is already handled by parent, but we can categorize
        if ($trx->amount < 0) {
            $trx->transactionType = 'DEBIT';
        } else {
            $trx->transactionType = 'CREDIT';
        }
        
        // Extract better payee name
        $trx->name = $this->extractPayeeName($trx->memo);
        
        return $trx;
    }
}
