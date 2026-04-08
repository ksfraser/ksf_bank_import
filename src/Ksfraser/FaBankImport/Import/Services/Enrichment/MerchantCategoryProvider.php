<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Merchant Category Provider Interface
 * 
 * Contract for merchant category inference services
 */
interface MerchantCategoryProvider
{
    /**
     * Infer merchant category for a transaction
     * 
     * @param BiTransaction $transaction Transaction to categorize
     * @return string|null Category code or null if cannot determine
     */
    public function inferCategory(BiTransaction $transaction): ?string;
}
