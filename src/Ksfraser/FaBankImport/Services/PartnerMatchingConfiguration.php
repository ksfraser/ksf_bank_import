<?php
/**
 * Partner Matching Configuration (Stub)
 * 
 * Minimal stub for partner matching configuration. Required by class.bi_lineitem.php
 * but not currently used in the code. Can be implemented later if needed.
 * 
 * @package Ksfraser\FaBankImport\Services
 * @since   2025-01-01
 */

namespace Ksfraser\FaBankImport\Services;

final class PartnerMatchingConfiguration
{
    /**
     * Get default matching threshold
     * 
     * @return float Matching threshold (0.0-100.0)
     */
    public static function getMatchingThreshold(): float
    {
        return 50.0; // Default 50% matching threshold
    }
    
    /**
     * Get maximum number of suggestions
     * 
     * @return int Maximum suggestions to return
     */
    public static function getMaxSuggestions(): int
    {
        return 5; // Return top 5 suggestions by default
    }
}
