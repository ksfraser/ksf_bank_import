<?php

namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;

/**
 * Repository for BankAccountMapping operations
 *
 * Provides interface for bank account mapping queries.
 * Instance methods support dependency injection and testing.
 */
class BankAccountMappingRepository
{
    /**
     * Find a mapping by OFX identifiers
     * 
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intu_bid Intuit BID
     * @return BankAccountMapping|null
     */
    public function findByOFXIdentifiers(
        ?string $bankid = null,
        ?string $acctid = null,
        ?string $intu_bid = null
    ): ?BankAccountMapping {
        return null;
    }

    /**
     * Find all mappings for a specific FA bank account
     * 
     * @param int $bankAccountId FA bank account ID
     * @return BankAccountMapping[]
     */
    public function findByFABankAccountId(int $bankAccountId): array
    {
        return [];
    }

    /**
     * Count total bank account mappings
     * 
     * @return int
     */
    public function countAll(): int
    {
        return 0;
    }
}
