<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

/**
 * Null Bank Metadata Provider
 * 
 * Null Object pattern implementation - returns empty metadata
 * Use when bank metadata lookup is not available
 */
final class NullBankMetadataProvider implements BankMetadataProvider
{
    /**
     * Always return empty array
     * 
     * @param string $bankName
     * @return array<string, mixed>
     */
    public function getMetadata(string $bankName): array
    {
        return [];
    }
}
