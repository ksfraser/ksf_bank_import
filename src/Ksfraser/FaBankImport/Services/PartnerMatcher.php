<?php
namespace Ksfraser\FaBankImport\Services;

/**
 * PartnerMatcher - Service for matching partners by bank account
 * 
 * Encapsulates the logic for searching partners by bank account.
 * Single Responsibility: Match bank accounts to partners in the system.
 * 
 * Uses instance methods for testability (can be mocked) with static wrappers
 * for backward compatibility.
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250422
 */
class PartnerMatcher
{
    /**
     * Search for a partner by bank account string (instance method for testability)
     * 
     * @param string $partnerType The type of partner (PT_SUPPLIER, PT_CUSTOMER, ST_BANKTRANSFER)
     * @param string $bankAccount The bank account string to search for
     * @return array Partner data array or empty array if not found
     */
    public function searchByBankAccount(string $partnerType, string $bankAccount): array
    {
        if (empty($bankAccount)) {
            return [];
        }
        
        $result = search_partner_by_bank_account($partnerType, $bankAccount);
        return $result ?? [];
    }
    
    /**
     * Check if a partner match exists (instance method for testability)
     * 
     * @param array $match The match result from searchByBankAccount
     * @return bool True if match exists and is not empty
     */
    public function hasMatch(array $match): bool
    {
        return !empty($match);
    }
    
    /**
     * Extract partner ID from match result (instance method for testability)
     * 
     * @param array $match The match result
     * @return int|null The partner ID or null if not found
     */
    public function getPartnerId(array $match): ?int
    {
        return $match['partner_id'] ?? null;
    }
    
    /**
     * Extract partner detail ID from match result (instance method for testability)
     * 
     * @param array $match The match result
     * @return int|null The partner detail ID or null if not found
     */
    public function getPartnerDetailId(array $match): ?int
    {
        return $match['partner_detail_id'] ?? null;
    }
    
    /**
     * Static wrapper for backward compatibility
     * 
     * @param string $partnerType The type of partner
     * @param string $bankAccount The bank account string to search for
     * @return array Partner data array or empty array if not found
     * @deprecated Use instance methods instead for testability
     */
    public static function searchByBankAccountStatic(string $partnerType, string $bankAccount): array
    {
        return (new self())->searchByBankAccount($partnerType, $bankAccount);
    }
    
    /**
     * Static wrapper for backward compatibility
     * 
     * @param array $match The match result
     * @return bool True if match exists and is not empty
     * @deprecated Use instance methods instead for testability
     */
    public static function hasMatchStatic(array $match): bool
    {
        return (new self())->hasMatch($match);
    }
    
    /**
     * Static wrapper for backward compatibility
     * 
     * @param array $match The match result
     * @return int|null The partner ID or null if not found
     * @deprecated Use instance methods instead for testability
     */
    public static function getPartnerIdStatic(array $match): ?int
    {
        return (new self())->getPartnerId($match);
    }
    
    /**
     * Static wrapper for backward compatibility
     * 
     * @param array $match The match result
     * @return int|null The partner detail ID or null if not found
     * @deprecated Use instance methods instead for testability
     */
    public static function getPartnerDetailIdStatic(array $match): ?int
    {
        return (new self())->getPartnerDetailId($match);
    }
}