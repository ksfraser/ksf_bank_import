<?php

/**
 * Partner Matcher Service Class
 * 
 * Encapsulates the logic for searching partners by bank account.
 * Single Responsibility: Match bank accounts to partners in the system.
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250422
 */

class PartnerMatcher
{
    /**
     * Search for a partner by bank account string
     * 
     * @param string $partnerType The type of partner (PT_SUPPLIER, PT_CUSTOMER, ST_BANKTRANSFER)
     * @param string $bankAccount The bank account string to search for
     * @return array Partner data array or empty array if not found
     */
    public static function searchByBankAccount(string $partnerType, string $bankAccount): array
    {
        if (empty($bankAccount)) {
            return [];
        }
        
        $result = search_partner_by_bank_account($partnerType, $bankAccount);
        return $result ?? [];
    }
    
    /**
     * Check if a partner match exists
     * 
     * @param array $match The match result from searchByBankAccount
     * @return bool True if match exists and is not empty
     */
    public static function hasMatch(array $match): bool
    {
        return !empty($match);
    }
    
    /**
     * Extract partner ID from match result
     * 
     * @param array $match The match result
     * @return int|null The partner ID or null if not found
     */
    public static function getPartnerId(array $match): ?int
    {
        return $match['partner_id'] ?? null;
    }
    
    /**
     * Extract partner detail ID from match result
     * 
     * @param array $match The match result
     * @return int|null The partner detail ID or null if not found
     */
    public static function getPartnerDetailId(array $match): ?int
    {
        return $match['partner_detail_id'] ?? null;
    }
}
