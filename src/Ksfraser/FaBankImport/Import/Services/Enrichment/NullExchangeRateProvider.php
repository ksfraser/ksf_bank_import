<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

/**
 * Null Exchange Rate Provider
 * 
 * Null Object pattern implementation - returns neutral (1.0) rate
 * Use when exchange rate lookup is not available
 */
final class NullExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * Always return 1.0 (no conversion)
     * 
     * @param string $from
     * @param string $to
     * @return float
     */
    public function getRate(string $from, string $to): float
    {
        return 1.0;
    }
}
