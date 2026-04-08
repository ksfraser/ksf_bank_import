<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

/**
 * Exchange Rate Provider Interface
 * 
 * Contract for exchange rate lookup services
 */
interface ExchangeRateProvider
{
    /**
     * Get exchange rate between two currencies
     * 
     * @param string $from Source currency (e.g., 'USD')
     * @param string $to Target currency (e.g., 'CAD')
     * @return float Exchange rate
     */
    public function getRate(string $from, string $to): float;
}
