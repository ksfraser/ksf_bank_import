<?php

/**
 * CSV Field Mapper - Intelligent field mapping with fuzzy matching
 * 
 * Analyzes CSV headers and suggests best matches to our expected fields.
 * Uses multiple matching strategies: exact match, fuzzy match, synonym match, and pattern match.
 * 
 * @author Kevin Fraser / GitHub Copilot
 * @since 20260112
 * @version 1.0.0
 */
class CsvFieldMapper {
    
    /**
     * Expected field definitions with synonyms and patterns
     * Each field has:
     * - name: The internal field name we use
     * - required: Whether this field is mandatory
     * - synonyms: Alternative names/patterns to match against
     * - pattern: Regex pattern for content validation
     */
    private $fieldDefinitions = [
        'date' => [
            'name' => 'date',
            'required' => true,
            'synonyms' => ['date', 'transaction date', 'trans date', 'posted date', 'posting date', 'value date', 'transaction_date', 'trans_date'],
            'pattern' => '/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$|^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/' // mm/dd/yyyy or yyyy-mm-dd
        ],
        'description' => [
            'name' => 'description',
            'required' => true,
            'synonyms' => ['description', 'merchant', 'merchant name', 'payee', 'memo', 'details', 'transaction details', 'narrative', 'particulars'],
            'pattern' => null // Any text
        ],
        'amount' => [
            'name' => 'amount',
            'required' => true,
            'synonyms' => ['amount', 'value', 'transaction amount', 'debit', 'credit'],
            'pattern' => '/^[\-\+]?\$?\d+[\d,]*\.?\d*$/' // $123.45 or -123.45
        ],
        'debit' => [
            'name' => 'debit',
            'required' => false,
            'synonyms' => ['debit', 'withdrawal', 'payment', 'debit amount', 'withdrawals'],
            'pattern' => '/^\$?\d+[\d,]*\.?\d*$/'
        ],
        'credit' => [
            'name' => 'credit',
            'required' => false,
            'synonyms' => ['credit', 'deposit', 'credit amount', 'deposits'],
            'pattern' => '/^\$?\d+[\d,]*\.?\d*$/'
        ],
        'balance' => [
            'name' => 'balance',
            'required' => false,
            'synonyms' => ['balance', 'running balance', 'account balance', 'current balance', 'available balance'],
            'pattern' => '/^[\-\+]?\$?\d+[\d,]*\.?\d*$/'
        ],
        'reference' => [
            'name' => 'reference',
            'required' => false,
            'synonyms' => ['reference', 'ref', 'reference number', 'transaction id', 'trans id', 'check number', 'cheque number'],
            'pattern' => '/^[A-Z0-9\-]+$/'
        ],
        'category' => [
            'name' => 'category',
            'required' => false,
            'synonyms' => ['category', 'type', 'transaction type', 'trans type', 'activity type'],
            'pattern' => null
        ],
        'account' => [
            'name' => 'account',
            'required' => false,
            'synonyms' => ['account', 'account number', 'account name', 'acct', 'card number'],
            'pattern' => null
        ]
    ];
    
    /**
     * Analyze CSV headers and suggest field mappings
     * 
     * @param array $csvHeaders The headers from the CSV file
     * @param array $sampleData Optional: First few rows of data to validate patterns
     * @return array Suggested mappings [csv_column => our_field_name]
     */
    public function suggestMapping($csvHeaders, $sampleData = []) {
        $suggestions = [];
        $usedHeaders = []; // Track which headers we've already mapped
        
        // First pass: Exact and fuzzy matches
        foreach ($this->fieldDefinitions as $fieldName => $fieldDef) {
            $bestMatch = $this->findBestMatch($csvHeaders, $fieldDef, $sampleData, $usedHeaders);
            
            if ($bestMatch !== null) {
                $suggestions[$bestMatch['header']] = $fieldName;
                $usedHeaders[] = $bestMatch['header'];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Find the best matching CSV header for a field definition
     * 
     * @param array $csvHeaders Available CSV headers
     * @param array $fieldDef Field definition with synonyms
     * @param array $sampleData Sample rows to validate patterns
     * @param array $usedHeaders Headers already mapped (to avoid duplicates)
     * @return array|null ['header' => matched_header, 'confidence' => score]
     */
    private function findBestMatch($csvHeaders, $fieldDef, $sampleData, $usedHeaders) {
        $matches = [];
        
        foreach ($csvHeaders as $csvHeader) {
            // Skip already used headers
            if (in_array($csvHeader, $usedHeaders)) {
                continue;
            }
            
            $confidence = $this->calculateConfidence($csvHeader, $fieldDef, $sampleData);
            
            if ($confidence > 0) {
                $matches[] = [
                    'header' => $csvHeader,
                    'confidence' => $confidence
                ];
            }
        }
        
        // Sort by confidence (highest first)
        usort($matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        // Return best match if confidence is reasonable (>30%)
        if (!empty($matches) && $matches[0]['confidence'] > 30) {
            return $matches[0];
        }
        
        return null;
    }
    
    /**
     * Calculate confidence score for a CSV header matching a field
     * 
     * Scoring:
     * - Exact match: 100
     * - Synonym exact match: 95
     * - Fuzzy match (similar_text): 0-80
     * - Pattern match bonus: +20
     * 
     * @param string $csvHeader The CSV header to evaluate
     * @param array $fieldDef Field definition
     * @param array $sampleData Sample rows to test pattern
     * @return int Confidence score (0-100)
     */
    private function calculateConfidence($csvHeader, $fieldDef, $sampleData) {
        $csvHeaderLower = strtolower(trim($csvHeader));
        $confidence = 0;
        
        // Exact match check
        if ($csvHeaderLower === strtolower($fieldDef['name'])) {
            return 100;
        }
        
        // Synonym exact match
        foreach ($fieldDef['synonyms'] as $synonym) {
            if ($csvHeaderLower === strtolower($synonym)) {
                return 95;
            }
        }
        
        // Fuzzy matching using similar_text
        $bestSimilarity = 0;
        foreach ($fieldDef['synonyms'] as $synonym) {
            $similarity = 0;
            similar_text($csvHeaderLower, strtolower($synonym), $similarity);
            $bestSimilarity = max($bestSimilarity, $similarity);
        }
        
        // Adjust similarity to 0-80 range (leaving room for pattern bonus)
        $confidence = intval($bestSimilarity * 0.8);
        
        // Pattern validation bonus
        if ($fieldDef['pattern'] !== null && !empty($sampleData)) {
            $patternMatches = $this->testPattern($csvHeader, $fieldDef['pattern'], $sampleData);
            if ($patternMatches) {
                $confidence += 20;
            }
        }
        
        return $confidence;
    }
    
    /**
     * Test if sample data matches the expected pattern
     * 
     * @param string $csvHeader The header to test
     * @param string $pattern Regex pattern
     * @param array $sampleData Sample rows
     * @return bool True if majority of samples match pattern
     */
    private function testPattern($csvHeader, $pattern, $sampleData) {
        if (empty($sampleData)) {
            return false;
        }
        
        $matchCount = 0;
        $testCount = 0;
        
        foreach ($sampleData as $row) {
            if (!isset($row[$csvHeader])) {
                continue;
            }
            
            $value = trim($row[$csvHeader]);
            if (empty($value)) {
                continue;
            }
            
            $testCount++;
            if (preg_match($pattern, $value)) {
                $matchCount++;
            }
        }
        
        // Require at least 70% match rate
        return $testCount > 0 && ($matchCount / $testCount) >= 0.7;
    }
    
    /**
     * Get mapping quality score
     * 
     * @param array $mapping The suggested mapping
     * @return array ['score' => int, 'missing_required' => array, 'quality' => string]
     */
    public function evaluateMapping($mapping) {
        $mappedFields = array_values($mapping);
        $missingRequired = [];
        
        // Check for required fields
        foreach ($this->fieldDefinitions as $fieldName => $fieldDef) {
            if ($fieldDef['required'] && !in_array($fieldName, $mappedFields)) {
                $missingRequired[] = $fieldName;
            }
        }
        
        // Calculate score
        $totalRequired = count(array_filter($this->fieldDefinitions, function($def) {
            return $def['required'];
        }));
        
        $mappedRequired = $totalRequired - count($missingRequired);
        $score = $totalRequired > 0 ? intval(($mappedRequired / $totalRequired) * 100) : 0;
        
        // Determine quality
        $quality = 'poor';
        if ($score >= 90) {
            $quality = 'excellent';
        } elseif ($score >= 70) {
            $quality = 'good';
        } elseif ($score >= 50) {
            $quality = 'fair';
        }
        
        return [
            'score' => $score,
            'missing_required' => $missingRequired,
            'quality' => $quality,
            'mapped_count' => count($mapping),
            'total_fields' => count($this->fieldDefinitions)
        ];
    }
    
    /**
     * Get field definitions (for UI display)
     * 
     * @return array Field definitions
     */
    public function getFieldDefinitions() {
        return $this->fieldDefinitions;
    }
}
