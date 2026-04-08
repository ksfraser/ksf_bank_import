<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

/**
 * Bank Metadata Provider Interface
 * 
 * Contract for bank information lookup services
 */
interface BankMetadataProvider
{
    /**
     * Get metadata for a bank
     * 
     * @param string $bankName Bank name or identifier
     * @return array<string, mixed> Bank metadata (SWIFT, routing number, contact, etc.)
     */
    public function getMetadata(string $bankName): array;
}
