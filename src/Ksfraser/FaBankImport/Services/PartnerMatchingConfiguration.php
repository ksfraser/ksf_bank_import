<?php

/**
 * Partner Matching Configuration
 * 
 * Unified configuration for partner matching across supplier and customer types.
 * Contains shared settings and thresholds used by matching services.
 * 
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250409
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

/**
 * PartnerMatchingConfiguration
 * 
 * Provides configuration values for partner matching operations.
 * This class centralizes matching thresholds and behavior settings
 * that are shared between customer and supplier matching services.
 */
class PartnerMatchingConfiguration
{
    /**
     * Minimum score threshold for considering a match valid
     * 
     * Matches with scores below this threshold are ignored.
     * Value between 0-100 where higher means stricter matching.
     * 
     * @var int
     */
    public const MIN_MATCH_SCORE = 50;

    /**
     * Maximum number of matches to return for display
     * 
     * Limits the number of potential matches shown to the user
     * for manual review when automatic matching isn't possible.
     * 
     * @var int
     */
    public const MAX_DISPLAY_MATCHES = 5;

    /**
     * Enable fuzzy matching for transaction titles
     * 
     * When true, uses similarity algorithms (like Levenshtein distance)
     * to match transaction titles that aren't exact matches.
     * 
     * @var bool
     */
    public const ENABLE_FUZZY_TITLE_MATCHING = true;

    /**
     * Fuzzy matching threshold percentage
     * 
     * Minimum similarity percentage (0-100) for fuzzy title matches
     * to be considered valid when ENABLE_FUZZY_TITLE_MATCHING is true.
     * 
     * @var int
     */
    public const FUZZY_MATCH_THRESHOLD = 70;

    /**
     * Weight for amount matching in scoring algorithm
     * 
     * Relative importance of amount matching compared to other factors.
     * Higher values increase the impact of amount similarity on final score.
     * 
     * @var int
     */
    public const AMOUNT_WEIGHT = 30;

    /**
     * Weight for date proximity in scoring algorithm
     * 
     * Relative importance of date closeness compared to other factors.
     * Higher values increase the impact of date similarity on final score.
     * 
     * @var int
     */
    public const DATE_WEIGHT = 25;

    /**
     * Weight for reference number matching in scoring algorithm
     * 
     * Relative importance of reference number matching compared to other factors.
     * Higher values increase the impact of reference similarity on final score.
     * 
     * @var int
     */
    public const REFERENCE_WEIGHT = 25;

    /**
     * Weight for description/title matching in scoring algorithm
     * 
     * Relative importance of description/title matching compared to other factors.
     * Higher values increase the impact of title similarity on final score.
     * 
     * @var int
     */
    public const DESCRIPTION_WEIGHT = 20;
}