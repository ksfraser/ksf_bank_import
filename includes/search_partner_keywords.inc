<?php
/**
 * Search Partner Data by Keywords with Scoring
 *
 * Enhanced version of search_partner_data_by_needle() that uses keyword-based
 * scoring with occurrence counts and co-occurrence clustering bonus.
 *
 * This function can be included in pdata.inc or used standalone.
 *
 * @package    KSF Bank Import
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

// Configuration: Clustering bonus multiplier per additional keyword
// Values: 0.1 (conservative), 0.2 (balanced), 0.3 (aggressive)
// Now loaded from database configuration (module_config.php)
if (!defined('KEYWORD_CLUSTERING_FACTOR')) {
    // Try to load from config system
    $clustering_factor = 0.2; // Default fallback
    
    if (function_exists('get_company_pref')) {
        // FrontAccounting company preferences (future integration)
        $pref_value = get_company_pref('bi_keyword_clustering_factor');
        if ($pref_value !== null && $pref_value !== false) {
            $clustering_factor = (float)$pref_value;
        }
    }
    
    // Try ConfigService if available
    if (class_exists('\Ksfraser\FaBankImport\Config\ConfigService')) {
        try {
            $configService = \Ksfraser\FaBankImport\Config\ConfigService::getInstance();
            $clustering_factor = (float)$configService->get('pattern_matching.keyword_clustering_factor', $clustering_factor);
        } catch (\Exception $e) {
            // Config service not available, use default
        }
    }
    
    define('KEYWORD_CLUSTERING_FACTOR', $clustering_factor);
}

/**
 * Search partner data by keywords with scoring
 *
 * Tokenizes the search text, searches for each keyword, and scores results
 * based on:
 * 1. Number of matching keywords (more matches = higher score)
 * 2. Occurrence count (frequently matched patterns score higher)
 *
 * Example:
 *   search_text: "Internet Domain Registration"
 *   
 *   Results:
 *   - Partner A (QE-Business): keywords: internet(50), domain(45), registration(12) = Score 107
 *   - Partner B (BT-Transfer): keywords: internet(30), transfer(25) = Score 55
 *   
 *   Returns Partner A as top match
 *
 * @param int $partner_type Filter by partner type (PT_SUPPLIER, PT_CUSTOMER, etc.)
 * @param string $search_text Transaction text to search (memo, title, account, etc.)
 * @param int $limit Maximum number of results to return
 * @return array Array of matches with scores, sorted by score DESC
 *               Format: [
 *                   'partner_id' => int,
 *                   'partner_detail_id' => int,
 *                   'partner_type' => int,
 *                   'score' => int,
 *                   'matched_keywords' => array,
 *                   'total_occurrences' => int
 *               ]
 */
function search_partner_by_keywords($partner_type, $search_text, $limit = 10) {
    if (empty($search_text)) {
        return array();
    }
    
    // Extract keywords from search text (same function as build script)
    $keywords = extract_keywords_for_search($search_text);
    
    if (empty($keywords)) {
        return array();
    }
    
    // Build SQL to search for any of the keywords
    $keyword_conditions = array();
    foreach ($keywords as $keyword) {
        $keyword_conditions[] = "data = " . db_escape($keyword);
    }
    
    $where_clause = "(" . implode(" OR ", $keyword_conditions) . ")";
    
    if ($partner_type) {
        $where_clause = "partner_type = " . db_escape($partner_type) . " AND " . $where_clause;
    }
    
    // Query to get all matching keyword records
    $sql = "SELECT 
                partner_id,
                partner_detail_id, 
                partner_type,
                data as keyword,
                occurrence_count
            FROM " . TB_PREF . "bi_partners_data
            WHERE " . $where_clause . "
            ORDER BY occurrence_count DESC";
    
    $result = db_query($sql, "Could not search partner data");
    
    // Aggregate results by partner and calculate scores
    $partner_scores = array();
    
    while ($row = db_fetch($result)) {
        $partner_key = sprintf(
            "%d-%d-%d",
            $row['partner_id'],
            $row['partner_detail_id'],
            $row['partner_type']
        );
        
        if (!isset($partner_scores[$partner_key])) {
            $partner_scores[$partner_key] = array(
                'partner_id' => $row['partner_id'],
                'partner_detail_id' => $row['partner_detail_id'],
                'partner_type' => $row['partner_type'],
                'score' => 0,
                'matched_keywords' => array(),
                'total_occurrences' => 0,
                'keyword_match_count' => 0
            );
        }
        
        // Add to base score (pure occurrence count)
        // Score represents: how many times has this partner been matched with this keyword
        $partner_scores[$partner_key]['score'] += $row['occurrence_count'];
        $partner_scores[$partner_key]['matched_keywords'][] = $row['keyword'];
        $partner_scores[$partner_key]['total_occurrences'] += $row['occurrence_count'];
        $partner_scores[$partner_key]['keyword_match_count'] = count($partner_scores[$partner_key]['matched_keywords']);
    }
    
    // Apply co-occurrence clustering bonus
    // When multiple keywords match the same partner, this is stronger evidence
    // Bonus formula: base_score * (1 + (keyword_match_count - 1) * CLUSTERING_FACTOR)
    // Default CLUSTERING_FACTOR = 0.2 (tunable via constant)
    // Examples with FACTOR=0.2:
    //   1 keyword:  score * 1.0 (no bonus)
    //   2 keywords: score * 1.2 (20% boost)
    //   3 keywords: score * 1.4 (40% boost)
    //   5 keywords: score * 1.8 (80% boost)
    foreach ($partner_scores as $partner_key => &$partner) {
        $base_score = $partner['score'];
        $clustering_multiplier = 1 + (($partner['keyword_match_count'] - 1) * KEYWORD_CLUSTERING_FACTOR);
        $partner['score'] = round($base_score * $clustering_multiplier);
        $partner['base_score'] = $base_score; // Keep original for debugging
        $partner['clustering_bonus'] = round($base_score * ($clustering_multiplier - 1));
    }
    unset($partner); // Break reference
    
    // Sort by:
    // 1. Number of keywords matched (more is better)
    // 2. Total score (occurrence count sum)
    usort($partner_scores, function($a, $b) {
        // First compare keyword match count
        if ($a['keyword_match_count'] != $b['keyword_match_count']) {
            return $b['keyword_match_count'] - $a['keyword_match_count'];
        }
        // Then compare total score
        return $b['score'] - $a['score'];
    });
    
    // Calculate confidence scores (multi-factor)
    // Based on: 1) keyword coverage (% of search keywords matched)
    //           2) score strength (% of top score)
    if (!empty($partner_scores)) {
        $top_score = $partner_scores[0]['score'];
        $search_keyword_count = count($keywords);
        
        foreach ($partner_scores as &$partner) {
            $keyword_coverage = $search_keyword_count > 0
                ? ($partner['keyword_match_count'] / $search_keyword_count) * 100
                : 0;
            
            $score_strength = $top_score > 0 
                ? ($partner['score'] / $top_score) * 100
                : 0;
            
            // Weighted average: 60% keyword coverage, 40% score strength
            // This prevents high-occurrence single-keyword matches from dominating
            $partner['confidence'] = round(
                ($keyword_coverage * 0.6) + ($score_strength * 0.4),
                1
            );
        }
    }
    
    // Limit results
    return array_slice($partner_scores, 0, $limit);
}

/**
 * Extract keywords from text for searching
 *
 * Same logic as in build_partner_keyword_data.php
 *
 * @param string $text Text to tokenize
 * @return array Array of unique keywords
 */
function extract_keywords_for_search($text) {
    if (empty($text)) {
        return array();
    }
    
    // Normalize
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    // Filter stopwords
    $stopwords = array(
        'the', 'and', 'or', 'for', 'to', 'from', 'in', 'on', 'at', 'by',
        'with', 'of', 'as', 'is', 'was', 'be', 'are', 'were', 'been',
        'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'this', 'that', 'these', 'those', 'it', 'its', 'an', 'a',
        'payment', 'transaction', 'transfer', 'deposit', 'withdrawal'
    );
    
    $keywords = array();
    foreach ($words as $word) {
        if (strlen($word) >= 3 && !in_array($word, $stopwords)) {
            $keywords[$word] = true;
        }
    }
    
    return array_keys($keywords);
}

/**
 * Get suggested partner for transaction text
 *
 * Convenience wrapper that returns the top-scoring partner suggestion
 *
 * @param int $partner_type Filter by partner type
 * @param string $search_text Transaction text
 * @return array|null Top match or null if no matches
 */
function get_suggested_partner($partner_type, $search_text) {
    $results = search_partner_by_keywords($partner_type, $search_text, 1);
    
    return !empty($results) ? $results[0] : null;
}

/**
 * Example usage / testing
 */
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    // This allows testing the function directly
    $path_to_root = "../..";
    include_once($path_to_root . "/includes/session.inc");
    include_once($path_to_root . "/includes/ui.inc");
    
    page(_($help_context = "Test Keyword Search"));
    
    // Test searches
    $test_searches = array(
        "Internet Transfer",
        "Internet Domain Registration",
        "SHOPPERS DRUG MART",
        "EPCOR UTILITIES",
        "Bell Mobility"
    );
    
    echo "<h2>Keyword Search Test</h2>";
    
    foreach ($test_searches as $search_text) {
        echo "<h3>Search: " . $search_text . "</h3>";
        
        $keywords = extract_keywords_for_search($search_text);
        echo "<p><strong>Keywords:</strong> " . implode(', ', $keywords) . "</p>";
        
        $results = search_partner_by_keywords(0, $search_text, 5);
        
        if (empty($results)) {
            echo "<p><em>No matches found</em></p>";
        } else {
            echo "<table class='tablestyle'>";
            echo "<tr><th>Rank</th><th>Partner</th><th>Type</th><th>Keywords</th><th>Base Score</th><th>Cluster Bonus</th><th>Final Score</th><th>Confidence</th></tr>";
            
            $rank = 1;
            foreach ($results as $result) {
                echo "<tr>";
                echo "<td>" . $rank++ . "</td>";
                echo "<td>" . $result['partner_id'] . "</td>";
                echo "<td>" . $result['partner_type'] . "</td>";
                echo "<td>" . implode(', ', $result['matched_keywords']) . " <em>(" . $result['keyword_match_count'] . ")</em></td>";
                echo "<td>" . $result['base_score'] . "</td>";
                echo "<td>+" . $result['clustering_bonus'] . "</td>";
                echo "<td><strong>" . $result['score'] . "</strong></td>";
                echo "<td>" . $result['confidence'] . "%</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        echo "<hr>";
    }
    
    end_page();
}
