<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Null Merchant Category Provider
 * 
 * Null Object pattern implementation - returns null for all transactions
 * Use when merchant category inference is not available
 */
final class NullMerchantCategoryProvider implements MerchantCategoryProvider
{
    /**
     * Always return null
     * 
     * @param BiTransaction $transaction
     * @return string|null
     */
    public function inferCategory(BiTransaction $transaction): ?string
    {
        return null;
    }
}
