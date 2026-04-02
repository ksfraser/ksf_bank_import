<?php

namespace Ksfraser\FaBankImport\Service\Schema;

use Ksfraser\FaBankImport\Service\BankAccountMapping\BankAccountMappingService;

/**
 * Centralized module-level schema maintenance (Facade).
 *
 * Delegates to focused classes following Single Responsibility Principle:
 * - ModuleSchemaInstaller: Schema initialization and data migration
 * - BankAccountMappingService: Bank account mapping queries
 * 
 * This class maintains backward compatibility while delegating to specialized classes.
 * 
 * @package Ksfraser\FaBankImport\Service\Schema
 * @author Kevin Fraser
 * @since 20250402
 */
class BankImportModuleSchemaService
{
    /**
     * @var ModuleSchemaInstaller
     */
    private $moduleSchemaInstaller;

    /**
     * @var BankAccountMappingService
     */
    private $bankAccountMappingService;

    /**
     * Constructor
     *
     * @param ModuleSchemaInstaller|null $moduleSchemaInstaller Optional schema installer
     * @param BankAccountMappingService|null $bankAccountMappingService Optional mapping service
     */
    public function __construct(
        ?ModuleSchemaInstaller $moduleSchemaInstaller = null,
        ?BankAccountMappingService $bankAccountMappingService = null
    ) {
        $this->moduleSchemaInstaller = $moduleSchemaInstaller ?? new ModuleSchemaInstaller();
        $this->bankAccountMappingService = $bankAccountMappingService ?? new BankAccountMappingService();
    }

    /**
     * Ensure schema drift repairs for all module tables.
     *
     * Delegates to ModuleSchemaInstaller which orchestrates all schema operations.
     *
     * @return array<string, bool>
     */
    public function ensureAll(): array
    {
        return $this->moduleSchemaInstaller->ensureAll();
    }

    /**
     * Get mapping by OFX identifiers
     *
     * Delegates to BankAccountMappingService for query execution.
     * 
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intu_bid Intuit BID
     * @return \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping|null
     */
    public function getBankAccountMappingByOFXIdentifiers(?string $bankid, ?string $acctid, ?string $intu_bid)
    {
        return $this->bankAccountMappingService->getBankAccountMappingByOFXIdentifiers($bankid, $acctid, $intu_bid);
    }

    /**
     * Get all mappings for a FA bank account
     *
     * Delegates to BankAccountMappingService.
     * 
     * @param int $faAccountId The FA bank account ID
     * @return array Array of BankAccountMapping entities
     */
    public function getMappingsForFABankAccount(int $faAccountId): array
    {
        return $this->bankAccountMappingService->getMappingsForFABankAccount($faAccountId);
    }

    /**
     * Count total bank account mappings
     *
     * Delegates to BankAccountMappingService.
     * 
     * @return int Total count of mappings
     */
    public function countBankAccountMappings(): int
    {
        return $this->bankAccountMappingService->countBankAccountMappings();
    }
}

