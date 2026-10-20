<?php
/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

/**
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/PartnerMatcher.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: searchByBankAccount
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: hasMatch
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerId
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerDetailId
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: searchByBankAccountStatic
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: hasMatchStatic
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerIdStatic
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
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getPartnerDetailIdStatic
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