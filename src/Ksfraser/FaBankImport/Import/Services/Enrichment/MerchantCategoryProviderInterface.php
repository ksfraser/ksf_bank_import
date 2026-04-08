<?php

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

/**
 * Interface for merchant category provider implementations
 *
 * Provides merchant categorization and classification capabilities
 */
interface MerchantCategoryProviderInterface
{
    /**
     * Categorize a merchant name or transaction description
     *
     * @param string $merchant Merchant name or transaction description
     * @return array<string, mixed>|null Category data with keys: name, confidence, subcategory
     */
    public function categorize(string $merchant): ?array;
}
