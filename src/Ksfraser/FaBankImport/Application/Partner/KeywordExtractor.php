<?php

namespace Ksfraser\FaBankImport\Application\Partner;

/**
 * Keyword Extractor Service
 * 
 * Extracts meaningful keywords from transaction descriptions.
 * Used by both partner search and data builder.
 * Provides consistent keyword extraction across the application.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class KeywordExtractor
{
    /**
     * Multi-word phrases that should be recognized as atomic units
     */
    private const KNOWN_PHRASES = [
        'Pre-Auth',
        'Credit Card',
        'Debit Card',
        'Bank Transfer',
        'E-Transfer',
        'Wire Transfer',
        'Square Up',
        'Group Benefit',
        'Interest Paid',
        'Interest Earned',
        'Bank of Montreal',
        'Royal Bank',
        'Toronto Dominion',
    ];
    
    /**
     * Common English stopwords to filter out
     */
    private const STOPWORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'is', 'was', 'are', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should',
        'could', 'may', 'might', 'can', 'as', 'if', 'than', 'then', 'that',
        'this', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they'
    ];
    
    /**
     * Minimum length for a keyword to be included (after filtering)
     */
    private const MIN_KEYWORD_LENGTH = 3;
    
    /**
     * Extract keywords from transaction description
     * 
     * Process:
     * 1. Extract known phrases (multi-word patterns)
     * 2. Split text into words
     * 3. Remove punctuation and filter stopwords
     * 4. Remove short words (< 3 chars)
     * 5. Return deduplicated keyword array (phrases + individual words)
     * 
     * @param string $text Transaction description to extract from
     * @return string[] Array of extracted keywords (deduplicated, no order)
     */
    public function extract(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }
        
        $keywords = [];
        
        // Step 1: Extract known phrases (but keep them in text too for word extraction)
        foreach (self::KNOWN_PHRASES as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $keywords[$phrase] = true;
            }
        }
        
        // Step 2: Split text into words (don't remove phrases, extract individual words too)
        $words = preg_split('/[\s,;:()]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (!$words) {
            return array_keys($keywords);
        }
        
        // Step 3: Process each word
        foreach ($words as $word) {
            // Remove punctuation and normalize
            $clean = preg_replace('/[^\w-]/u', '', $word);
            
            // Skip if too short
            if (strlen($clean) < self::MIN_KEYWORD_LENGTH) {
                continue;
            }
            
            // Skip stopwords (case-insensitive)
            if (in_array(strtolower($clean), self::STOPWORDS, true)) {
                continue;
            }
            
            // Add to keywords (using array to deduplicate)
            $keywords[$clean] = true;
        }
        
        // Return deduped list, preserve original case
        return array_keys($keywords);
    }
    
    /**
     * Get list of known phrases (for configuration)
     */
    public function knownPhrases(): array
    {
        return self::KNOWN_PHRASES;
    }
    
    /**
     * Get list of stopwords (for configuration)
     */
    public function stopwords(): array
    {
        return self::STOPWORDS;
    }
}
