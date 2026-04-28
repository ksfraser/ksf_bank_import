<?php

/**
 * CSV Mapping Template Storage
 * 
 * Stores and retrieves CSV field mapping templates.
 * Templates are stored as JSON files in csv_mappings/ directory.
 * Auto-detects matching templates based on header fingerprint.
 * 
 * @author Kevin Fraser / GitHub Copilot
 * @since 20260112
 * @version 1.0.0
 */
class CsvMappingTemplate {
    
    /** @var string Directory where templates are stored */
    private $templateDir;
    
    /**
     * Constructor
     * 
     * @param string $templateDir Directory path for templates (default: csv_mappings/)
     */
    public function __construct($templateDir = null) {
        if ($templateDir === null) {
            $templateDir = __DIR__ . '/../csv_mappings';
        }
        
        $this->templateDir = rtrim($templateDir, '/\\');
        
        // Ensure directory exists
        if (!file_exists($this->templateDir)) {
            mkdir($this->templateDir, 0755, true);
        }
    }
    
    /**
     * Save a mapping template
     * 
     * @param string $bankName Bank identifier (e.g., 'manulife', 'wmmc')
     * @param array $csvHeaders The CSV headers
     * @param array $mapping The field mapping [csv_header => our_field]
     * @param array $metadata Optional metadata (description, created_by, etc.)
     * @return bool Success
     */
    public function saveTemplate($bankName, $csvHeaders, $mapping, $metadata = []) {
        $template = [
            'bank_name' => $bankName,
            'version' => '1.0',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s'),
            'header_fingerprint' => $this->generateFingerprint($csvHeaders),
            'csv_headers' => $csvHeaders,
            'mapping' => $mapping,
            'metadata' => $metadata
        ];
        
        $filename = $this->getTemplateFilename($bankName);
        $filepath = $this->templateDir . '/' . $filename;
        
        $json = json_encode($template, JSON_PRETTY_PRINT);
        $result = file_put_contents($filepath, $json);
        
        return $result !== false;
    }
    
    /**
     * Find matching template for CSV headers
     * 
     * @param array $csvHeaders The CSV headers to match
     * @param string $bankName Optional bank name to filter by
     * @return array|null Template data or null if no match
     */
    public function findMatchingTemplate($csvHeaders, $bankName = null) {
        $fingerprint = $this->generateFingerprint($csvHeaders);
        
        // If bank name specified, check that template first
        if ($bankName !== null) {
            $template = $this->loadTemplate($bankName);
            if ($template !== null && $template['header_fingerprint'] === $fingerprint) {
                return $template;
            }
        }
        
        // Search all templates
        $templates = $this->listTemplates();
        foreach ($templates as $templateFile) {
            $template = $this->loadTemplateFile($templateFile);
            if ($template !== null && $template['header_fingerprint'] === $fingerprint) {
                return $template;
            }
        }
        
        // Try fuzzy matching (headers might have minor differences)
        $bestMatch = $this->findFuzzyMatch($csvHeaders, $templates);
        return $bestMatch;
    }
    
    /**
     * Load template by bank name
     * 
     * @param string $bankName Bank identifier
     * @return array|null Template data or null if not found
     */
    public function loadTemplate($bankName) {
        $filename = $this->getTemplateFilename($bankName);
        return $this->loadTemplateFile($filename);
    }
    
    /**
     * Load template from file
     * 
     * @param string $filename Template filename
     * @return array|null Template data or null if error
     */
    private function loadTemplateFile($filename) {
        $filepath = $this->templateDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return null;
        }
        
        $json = file_get_contents($filepath);
        if ($json === false) {
            return null;
        }
        
        $template = json_decode($json, true);
        if ($template === null) {
            return null;
        }
        
        return $template;
    }
    
    /**
     * List all available templates
     * 
     * @return array Array of template filenames
     */
    public function listTemplates() {
        if (!is_dir($this->templateDir)) {
            return [];
        }
        
        $files = scandir($this->templateDir);
        $templates = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $templates[] = $file;
            }
        }
        
        return $templates;
    }
    
    /**
     * Get all templates with metadata
     * 
     * @return array Array of template info
     */
    public function getAllTemplates() {
        $templates = $this->listTemplates();
        $result = [];
        
        foreach ($templates as $filename) {
            $template = $this->loadTemplateFile($filename);
            if ($template !== null) {
                $result[] = [
                    'filename' => $filename,
                    'bank_name' => $template['bank_name'],
                    'created' => $template['created'],
                    'updated' => $template['updated'],
                    'header_count' => count($template['csv_headers']),
                    'mapping_count' => count($template['mapping'])
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Delete a template
     * 
     * @param string $bankName Bank identifier
     * @return bool Success
     */
    public function deleteTemplate($bankName) {
        $filename = $this->getTemplateFilename($bankName);
        $filepath = $this->templateDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        return unlink($filepath);
    }
    
    /**
     * Generate fingerprint from CSV headers
     * 
     * Fingerprint is case-insensitive, whitespace-normalized hash
     * 
     * @param array $csvHeaders CSV headers
     * @return string MD5 hash fingerprint
     */
    private function generateFingerprint($csvHeaders) {
        // Normalize headers: lowercase, trim, remove extra spaces
        $normalized = array_map(function($header) {
            return preg_replace('/\s+/', ' ', strtolower(trim($header)));
        }, $csvHeaders);
        
        // Sort to make order-independent
        sort($normalized);
        
        // Create fingerprint
        return md5(implode('|', $normalized));
    }
    
    /**
     * Find fuzzy match for CSV headers
     * 
     * Checks if headers are similar enough (e.g., 80% match)
     * 
     * @param array $csvHeaders Headers to match
     * @param array $templateFiles Available templates
     * @return array|null Best matching template or null
     */
    private function findFuzzyMatch($csvHeaders, $templateFiles) {
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($templateFiles as $filename) {
            $template = $this->loadTemplateFile($filename);
            if ($template === null) {
                continue;
            }
            
            $score = $this->calculateHeaderSimilarity($csvHeaders, $template['csv_headers']);
            
            // Require at least 80% similarity for fuzzy match
            if ($score >= 0.8 && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $template;
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Calculate similarity between two header sets
     * 
     * @param array $headers1 First header set
     * @param array $headers2 Second header set
     * @return float Similarity score (0-1)
     */
    private function calculateHeaderSimilarity($headers1, $headers2) {
        if (count($headers1) === 0 || count($headers2) === 0) {
            return 0;
        }
        
        $normalized1 = array_map('strtolower', array_map('trim', $headers1));
        $normalized2 = array_map('strtolower', array_map('trim', $headers2));
        
        $intersection = array_intersect($normalized1, $normalized2);
        $union = array_unique(array_merge($normalized1, $normalized2));
        
        // Jaccard similarity coefficient
        return count($intersection) / count($union);
    }
    
    /**
     * Get template filename for bank
     * 
     * @param string $bankName Bank identifier
     * @return string Filename
     */
    private function getTemplateFilename($bankName) {
        // Sanitize bank name for filename
        $safe = preg_replace('/[^a-z0-9_-]/', '_', strtolower($bankName));
        return "csv_mapping_{$safe}.json";
    }
    
    /**
     * Update existing template
     * 
     * @param string $bankName Bank identifier
     * @param array $mapping New mapping
     * @return bool Success
     */
    public function updateTemplate($bankName, $mapping) {
        $template = $this->loadTemplate($bankName);
        if ($template === null) {
            return false;
        }
        
        $template['mapping'] = $mapping;
        $template['updated'] = date('Y-m-d H:i:s');
        
        $filename = $this->getTemplateFilename($bankName);
        $filepath = $this->templateDir . '/' . $filename;
        
        $json = json_encode($template, JSON_PRETTY_PRINT);
        return file_put_contents($filepath, $json) !== false;
    }
}
