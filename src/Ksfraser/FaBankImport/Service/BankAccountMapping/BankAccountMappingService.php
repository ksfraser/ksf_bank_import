<?php

namespace Ksfraser\FaBankImport\Service\BankAccountMapping;

use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;

/**
 * BankAccountMappingService - delegates bank account mapping queries to repository
 *
 * Responsibility: Provide consistent interface for bank account mapping queries,
 * with exception handling and default values
 *
 * Changes when: Bank account mapping query logic changes
 *
 * @package Ksfraser\FaBankImport\Service\BankAccountMapping
 * @author Kevin Fraser
 * @since 20260402
 */
class BankAccountMappingService
{
    /**
     * @var BankAccountMappingRepository
     */
    private $bankAccountMappingRepository;

    /**
     * Constructor
     *
     * @param BankAccountMappingRepository|null $bankAccountMappingRepository Repository instance
     */
    public function __construct(?BankAccountMappingRepository $bankAccountMappingRepository = null)
    {
        $this->bankAccountMappingRepository = $bankAccountMappingRepository ?? new BankAccountMappingRepository();
    }

    /**
     * Get mapping by OFX identifiers using Repository
     *
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intuit_bid Intuit BID
     * @return \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping|null
     */
    public function getBankAccountMappingByOFXIdentifiers(?string $bankid, ?string $acctid, ?string $intuit_bid)
    {
        try {
            return $this->bankAccountMappingRepository->findByOFXIdentifiers($bankid, $acctid, $intuit_bid);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all mappings for a FA bank account using Repository
     *
     * @param int $faAccountId The FA bank account ID
     * @return array Array of BankAccountMapping entities
     */
    public function getMappingsForFABankAccount(int $faAccountId): array
    {
        try {
            return $this->bankAccountMappingRepository->findByFABankAccountId($faAccountId);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Count total bank account mappings
     *
     * @return int Total count of mappings
     */
    public function countBankAccountMappings(): int
    {
        try {
            return $this->bankAccountMappingRepository->countAll();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
