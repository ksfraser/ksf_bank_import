<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

/**
 * BankAccountMappingRepositoryInterface - Abstract interface for OFX to FA account mapping
 * 
 * Maps OFX identifiers (bankid, acctid, intu_bid) to FA bank account IDs.
 * Used across modules for consistent FA account resolution.
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface BankAccountMappingRepositoryInterface
{
    /**
     * Find FA bank account ID by OFX identifiers
     * 
     * @param string $bankId OFX bank identifier
     * @param string $acctId OFX account identifier
     * @param string $intuBid Intuit business ID
     * 
     * @return ?int FA bank account ID, or null if not found
     */
    public function findFABankAccountId(string $bankId, string $acctId, string $intuBid): ?int;

    /**
     * Find all OFX mappings for FA bank account
     * 
     * @return array<int, array> Array of mappings with bankid, acctid, intu_bid
     */
    public function findMappingsForFAAccount(int $faAccountId): array;

    /**
     * Create or update OFX to FA mapping
     */
    public function createMapping(int $faAccountId, string $bankId, string $acctId, string $intuBid): void;

    /**
     * Check if mapping exists
     */
    public function mappingExists(string $bankId, string $acctId, string $intuBid): bool;

    /**
     * Get all mappings
     * 
     * @return array<int, array> Associative array of all mappings
     */
    public function getAllMappings(): array;
}
