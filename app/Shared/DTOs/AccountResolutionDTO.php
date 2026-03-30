<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

/**
 * Resolves detected bank accounts from imports to FrontAccounting bank account references
 */
class AccountResolutionDTO
{
    public $detectedAccounts; // array of accounts found in imported files
    public $faAccounts; // array of FrontAccounting bank accounts
    public $mappings; // array<string,string>, detected => FA account mappings

    public function __construct(array $data = [])
    {
        $this->detectedAccounts = $data['detectedAccounts'] ?? [];
        $this->faAccounts = $data['faAccounts'] ?? [];
        $this->mappings = $data['mappings'] ?? [];
    }

    /**
     * Check if all detected accounts have been mapped
     */
    public function isFullyMapped(): bool
    {
        return count($this->detectedAccounts) === count($this->mappings);
    }

    /**
     * Get unmapped account identifiers
     */
    public function getUnmappedAccounts(): array
    {
        return array_filter(
            $this->detectedAccounts,
            fn($account) => !isset($this->mappings[$account])
        );
    }

    /**
     * Add or update a mapping
     */
    public function setMapping(string $detectedAccount, string $faAccountId): void
    {
        $this->mappings[$detectedAccount] = $faAccountId;
    }
}
