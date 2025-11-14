<?php
/**
 * Partner Data Backward Compatibility Wrapper
 *
 * This file provides backward compatibility for the legacy pdata.inc API
 * by wrapping the new refactored services (PartnerDataService, KeywordMatchingService).
 *
 * @deprecated All functions in this file are deprecated. Use the new service classes:
 *             - Ksfraser\FaBankImport\Services\PartnerDataService
 *             - Ksfraser\FaBankImport\Services\KeywordMatchingService
 *             - Ksfraser\FaBankImport\Services\KeywordExtractorService
 *
 * @package    KSF Bank Import
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    2.0.0
 * @since      20251020
 */

use Ksfraser\FaBankImport\Services\PartnerDataService;
use Ksfraser\FaBankImport\Services\KeywordMatchingService;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository;
use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;

// Lazy initialization of services
function _get_partner_data_service() {
    static $service = null;
    if ($service === null) {
        $repository = new DatabasePartnerDataRepository();
        $extractor = new KeywordExtractorService();
        $service = new PartnerDataService($repository, $extractor);
    }
    return $service;
}

function _get_keyword_matching_service() {
    static $service = null;
    if ($service === null) {
        $repository = new DatabasePartnerDataRepository();
        $extractor = new KeywordExtractorService();
        $service = new KeywordMatchingService($repository, $extractor);
    }
    return $service;
}

function _get_keyword_extractor_service() {
    static $service = null;
    if ($service === null) {
        $service = new KeywordExtractorService();
    }
    return $service;
}

/**
 * Get the Partner Data
 *
 * @deprecated Use DatabasePartnerDataRepository::find() or PartnerDataService::find()
 *
 * @param int $partner_id Foreign key to Supplier/Customer/Bank tables (From bank)
 * @param int $partner_type Indicate which foreign table
 * @param int $partner_detail_id -1 for supplier. BRANCH id for Customer. TO Bank
 * @return array|null Partner data row or null if not found
 */
function get_partner_data($partner_id, $partner_type, $partner_detail_id) {
    @trigger_error(
        'get_partner_data() is deprecated. Use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository::findByPartner()',
        E_USER_DEPRECATED
    );
    
    $repository = new DatabasePartnerDataRepository();
    $results = $repository->findByPartner($partner_id, $partner_type);
    
    // Filter by detail ID if needed
    foreach ($results as $partnerData) {
        if ($partnerData->getPartnerDetailId() == $partner_detail_id) {
            return [
                'partner_id' => $partnerData->getPartnerId(),
                'partner_type' => $partnerData->getPartnerType(),
                'partner_detail_id' => $partnerData->getPartnerDetailId(),
                'data' => $partnerData->getData(),
                'occurrence_count' => $partnerData->getOccurrenceCount()
            ];
        }
    }
    
    return null;
}

/**
 * Insert BANK partner data. Wrapper on set_partner_data
 *
 * @deprecated Use PartnerDataService::saveKeyword()
 *
 * @param int $from_bank_id From Bank key (Bank table)
 * @param int $partner_type Indicate which foreign table
 * @param int $to_bank_id TO Bank key (Bank table)
 * @param string $data The data sent from the bank
 * @return void
 */
function set_bank_partner_data($from_bank_id, $partner_type = ST_BANKTRANSFER, $to_bank_id, $data) {
    @trigger_error(
        'set_bank_partner_data() is deprecated. Use Ksfraser\FaBankImport\Services\PartnerDataService::saveKeyword()',
        E_USER_DEPRECATED
    );
    
    set_partner_data($from_bank_id, $partner_type, $to_bank_id, $data);
}

/**
 * Insert partner data
 *
 * The key on this table is partner_id-partner_detail_id-partner_type-data
 *
 * The original version of this function would keep adding the same
 * partner data in, so we have partners that are SHOPPERS\nSHOPPERS\n....
 * Added a check for matches.
 *
 * @deprecated Use PartnerDataService::saveKeyword()
 *
 * @param int $partner_id Foreign key to Supplier/Customer/Bank tables (From bank)
 * @param int $partner_type Indicate which foreign table
 * @param int $partner_detail_id -1 for supplier. BRANCH id for Customer. TO Bank
 * @param string $data The data sent from the bank
 * @return void
 */
function set_partner_data($partner_id, $partner_type, $partner_detail_id, $data) {
    @trigger_error(
        'set_partner_data() is deprecated. Use Ksfraser\FaBankImport\Services\PartnerDataService::saveKeyword()',
        E_USER_DEPRECATED
    );
    
    $service = _get_partner_data_service();
    
    // Check if already exists with same data
    $existing = $service->find($partner_id, $partner_type, $partner_detail_id, $data);
    if ($existing !== null) {
        // Already there, no update needed
        return;
    }
    
    // Check if different data exists for same partner
    $allKeywords = $service->getPartnerKeywords($partner_id, $partner_type);
    foreach ($allKeywords as $existingKeyword) {
        if ($existingKeyword->getPartnerDetailId() == $partner_detail_id 
            && $existingKeyword->getData() != $data) {
            // Different data exists - this would require business decision
            // For now, save the new keyword as well (both are valid)
        }
    }
    
    // Save the keyword
    try {
        $service->saveKeyword($partner_id, $partner_type, $partner_detail_id, $data);
    } catch (\InvalidArgumentException $e) {
        // Invalid keyword - skip silently for backward compatibility
        return;
    }
}

/**
 * Search for partner data by needle
 *
 * There could possibly be different partners with the same needle
 *
 * @deprecated Use DatabasePartnerDataRepository::findByKeyword()
 *
 * @param string $needle Search term
 * @return array Array of matching partner data rows
 */
function search_partner_data_by_needle($needle) {
    @trigger_error(
        'search_partner_data_by_needle() is deprecated. Use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository::findByKeyword()',
        E_USER_DEPRECATED
    );
    
    if (empty($needle)) {
        return array();
    }
    
    $repository = new DatabasePartnerDataRepository();
    $results = $repository->findByKeyword($needle);
    
    // Convert to legacy array format
    $arr = array();
    foreach ($results as $partnerData) {
        $arr[] = [
            'partner_id' => $partnerData->getPartnerId(),
            'partner_type' => $partnerData->getPartnerType(),
            'partner_detail_id' => $partnerData->getPartnerDetailId(),
            'data' => $partnerData->getData(),
            'occurrence_count' => $partnerData->getOccurrenceCount()
        ];
    }
    
    return $arr;
}

/**
 * Search partner by bank account
 *
 * @deprecated Use DatabasePartnerDataRepository::findByKeyword()
 *
 * @param int $partner_type Partner type filter
 * @param string $needle Search term
 * @return array|null First matching partner data row
 */
function search_partner_by_bank_account($partner_type, $needle) {
    @trigger_error(
        'search_partner_by_bank_account() is deprecated. Use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository::findByKeyword()',
        E_USER_DEPRECATED
    );
    
    if (empty($needle)) {
        return array();
    }
    
    $repository = new DatabasePartnerDataRepository();
    $results = $repository->findByKeyword($needle, $partner_type);
    
    if (empty($results)) {
        return null;
    }
    
    $partnerData = $results[0];
    return [
        'partner_id' => $partnerData->getPartnerId(),
        'partner_type' => $partnerData->getPartnerType(),
        'partner_detail_id' => $partnerData->getPartnerDetailId(),
        'data' => $partnerData->getData(),
        'occurrence_count' => $partnerData->getOccurrenceCount()
    ];
}

/**
 * Update partner data
 *
 * @deprecated Use PartnerDataService::incrementKeywordOccurrence()
 *
 * @param int $partner_id Partner ID
 * @param int $partner_type Partner type
 * @param int $partner_detail_id Detail ID
 * @param string $data Keyword data
 * @return void
 */
function update_partner_data($partner_id, $partner_type, $partner_detail_id, $data) {
    @trigger_error(
        'update_partner_data() is deprecated. Use Ksfraser\FaBankImport\Services\PartnerDataService::saveKeyword() or incrementKeywordOccurrence()',
        E_USER_DEPRECATED
    );
    
    $service = _get_partner_data_service();
    
    try {
        // Try to increment occurrence count
        $service->incrementKeywordOccurrence($partner_id, $partner_type, $partner_detail_id, $data);
    } catch (\Exception $e) {
        // If fails, try to save as new
        try {
            $service->saveKeyword($partner_id, $partner_type, $partner_detail_id, $data);
        } catch (\InvalidArgumentException $e2) {
            // Invalid keyword - skip silently for backward compatibility
            return;
        }
    }
}

/**
 * Search partner data by keywords with scoring
 *
 * @deprecated Use KeywordMatchingService::search()
 *
 * @param int $partner_type Filter by partner type
 * @param string $search_text Transaction text to search
 * @param int $limit Maximum number of results
 * @return array Array of matches with scores
 */
function search_partner_by_keywords($partner_type, $search_text, $limit = 10) {
    @trigger_error(
        'search_partner_by_keywords() is deprecated. Use Ksfraser\FaBankImport\Services\KeywordMatchingService::search()',
        E_USER_DEPRECATED
    );
    
    if (empty($search_text)) {
        return array();
    }
    
    $service = _get_keyword_matching_service();
    $matches = $service->search($search_text, $partner_type, $limit);
    
    // Convert to legacy array format
    $results = array();
    foreach ($matches as $match) {
        $keywords = array_map(fn($k) => $k->getText(), $match->getMatchedKeywords());
        
        $results[] = [
            'partner_id' => $match->getPartnerId(),
            'partner_detail_id' => $match->getPartnerDetailId(),
            'partner_type' => $match->getPartnerType(),
            'score' => (int)$match->getFinalScore(),
            'base_score' => (int)$match->getRawScore(),
            'clustering_bonus' => (int)$match->getClusteringBonus(),
            'matched_keywords' => $keywords,
            'keyword_match_count' => $match->getMatchedKeywordCount(),
            'total_occurrences' => (int)$match->getRawScore(), // Approximation
            'confidence' => $match->getConfidence()->getPercentage()
        ];
    }
    
    return $results;
}

/**
 * Extract keywords from text for searching
 *
 * @deprecated Use KeywordExtractorService::extractAsStrings()
 *
 * @param string $text Text to tokenize
 * @return array Array of unique keywords
 */
function extract_keywords_for_search($text) {
    @trigger_error(
        'extract_keywords_for_search() is deprecated. Use Ksfraser\FaBankImport\Services\KeywordExtractorService::extractAsStrings()',
        E_USER_DEPRECATED
    );
    
    if (empty($text)) {
        return array();
    }
    
    $service = _get_keyword_extractor_service();
    return $service->extractAsStrings($text);
}

/**
 * Get suggested partner for transaction text
 *
 * @deprecated Use KeywordMatchingService::getTopMatch()
 *
 * @param int $partner_type Filter by partner type
 * @param string $search_text Transaction text
 * @return array|null Top match or null if no matches
 */
function get_suggested_partner($partner_type, $search_text) {
    @trigger_error(
        'get_suggested_partner() is deprecated. Use Ksfraser\FaBankImport\Services\KeywordMatchingService::getTopMatch()',
        E_USER_DEPRECATED
    );
    
    $results = search_partner_by_keywords($partner_type, $search_text, 1);
    
    return !empty($results) ? $results[0] : null;
}
