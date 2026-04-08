<?php

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

/**
 * Interface for bank metadata provider implementations
 *
 * Provides bank information lookup capabilities (SWIFT codes, routing numbers, contact info)
 */
interface BankMetadataProviderInterface
{
    /**
     * Lookup bank metadata by identifier
     *
     * @param string $bankIdentifier Bank FIT ID or bank code
     * @return array<string, mixed>|null Bank metadata with keys: swift, routing, contact, region
     */
    public function lookupBank(string $bankIdentifier): ?array;
}
