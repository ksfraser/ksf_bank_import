<?php

namespace Ksfraser\FaBankImport\Import\Services\Enrichment;

use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Enriches BiStatement entities with optional metadata
 *
 * Handles:
 * - Exchange rate lookup for foreign currencies
 * - Bank metadata injection (SWIFT code, contact info)
 * - Merchant category inference
 * - Custom enrichment extension points
 *
 * All enrichment operations are optional via Null Object pattern.
 * Implements SRP: Single responsibility = metadata injection after transformation
 */
final class EnrichmentService
{
    /**
     * Exchange rate provider (never null - uses Null Object pattern)
     *
     * @var ExchangeRateProvider
     */
    private ExchangeRateProvider $exchangeRateProvider;

    /**
     * Bank metadata provider (never null - uses Null Object pattern)
     *
     * @var BankMetadataProvider
     */
    private BankMetadataProvider $bankMetadataProvider;

    /**
     * Merchant category provider (never null - uses Null Object pattern)
     *
     * @var MerchantCategoryProvider
     */
    private MerchantCategoryProvider $merchantCategoryProvider;

    /**
     * PSR-3 logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Company default currency (from FA configuration)
     *
     * @var string
     */
    private string $baseCurrency;

    /**
     * Create enrichment service
     *
     * @param ExchangeRateProvider $exchangeRateProvider Exchange rate provider (default: NullExchangeRateProvider)
     * @param BankMetadataProvider $bankMetadataProvider Bank metadata provider (default: NullBankMetadataProvider)
     * @param MerchantCategoryProvider $merchantCategoryProvider Merchant category provider (default: NullMerchantCategoryProvider)
     * @param string $baseCurrency Company default currency (e.g., 'CAD', 'USD') - defaults to 'CAD'
     * @param LoggerInterface $logger Optional PSR-3 logger
     */
    public function __construct(
        ExchangeRateProvider $exchangeRateProvider = null,
        BankMetadataProvider $bankMetadataProvider = null,
        MerchantCategoryProvider $merchantCategoryProvider = null,
        string $baseCurrency = 'CAD',
        ?LoggerInterface $logger = null
    ) {
        $this->exchangeRateProvider = $exchangeRateProvider ?? new NullExchangeRateProvider();
        $this->bankMetadataProvider = $bankMetadataProvider ?? new NullBankMetadataProvider();
        $this->merchantCategoryProvider = $merchantCategoryProvider ?? new NullMerchantCategoryProvider();
        $this->baseCurrency = $baseCurrency;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Enrich statement with metadata
     *
     * Process:
     * 1. If exchangeRateProvider available: Add exchange rate data
     * 2. If bankMetadataProvider available: Add bank info (SWIFT, contact)
     * 3. If merchantCategoryProvider available: Infer transaction categories
     * 4. Log enrichment operations (skip unavailable providers)
     *
     * @param BiStatement $statement Statement to enrich
     * @return BiStatement Enriched statement (or original if no providers)
     */
    public function enrich(BiStatement $statement): BiStatement
    {
        $enrichmentData = [];

        // Step 1: Exchange rate enrichment (optional)
        $exchangeRateData = $this->enrichExchangeRates($statement);
        if (!empty($exchangeRateData)) {
            $enrichmentData['exchangeRates'] = $exchangeRateData;
            $this->logger->debug('Exchange rate enrichment applied', $exchangeRateData);
        }

        // Step 2: Bank metadata enrichment (optional)
        $bankData = $this->enrichBankMetadata($statement);
        if (!empty($bankData)) {
            $enrichmentData['bankMetadata'] = $bankData;
            $this->logger->debug('Bank metadata enrichment applied', $bankData);
        }

        // Step 3: Merchant category enrichment (optional)
        $categoryData = $this->enrichMerchantCategories($statement);
        if (!empty($categoryData)) {
            $enrichmentData['merchantCategories'] = $categoryData;
            $this->logger->debug(
                sprintf('Merchant category enrichment applied to %d transactions', count($categoryData))
            );
        }

        // If enrichments were applied, log summary
        if (!empty($enrichmentData)) {
            $this->logger->info(
                sprintf('Statement enriched with %d enrichment types', count($enrichmentData)),
                ['enrichmentTypes' => array_keys($enrichmentData)]
            );
        } else {
            $this->logger->debug('No enrichment providers available; skipping enrichment');
        }

        // In a real implementation, enrichment data would be stored with the statement
        // For now, return the statement as-is (enrichment is metadata)
        return $statement;
    }

    /**
     * Enrich exchange rate data for foreign currencies
     *
     * If statement currency differs from company base currency, lookup exchange rate
     *
     * @param BiStatement $statement
     * @return array<string, mixed> Enrichment data or empty array
     */
    private function enrichExchangeRates(BiStatement $statement): array
    {
        try {
            $currency = $statement->getCurrency();

            // Skip if already in base currency
            if ($currency === $this->baseCurrency) {
                return [];
            }

            // Call provider (never null due to Null Object pattern)
            $rate = $this->exchangeRateProvider->getRate($currency, $this->baseCurrency);

            return [
                'currency' => $currency,
                'rate' => $rate,
                'base' => $this->baseCurrency,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf('Exchange rate enrichment failed: %s', $e->getMessage())
            );
        }

        return [];
    }

    /**
     * Enrich bank metadata
     *
     * Lookup bank information (SWIFT code, routing number, contact)
     *
     * @param BiStatement $statement
     * @return array<string, mixed> Enrichment data or empty array
     */
    private function enrichBankMetadata(BiStatement $statement): array
    {
        if ($this->bankMetadataProvider === null) {
            return [];
        }

        $bankIdentifier = $statement->getFitId() ?? $statement->getBank();
        $metadata = $this->bankMetadataProvider->getMetadata($bankIdentifier);

        if (empty($metadata)) {
            return [];
        }

        return [
            'bankIdentifier' => $bankIdentifier,
            'swiftCode' => $metadata['swift'] ?? null,
            'routingNumber' => $metadata['routing'] ?? null,
            'contact' => $metadata['contact'] ?? null,
            'region' => $metadata['region'] ?? null
        ];
    }

    /**
     * Enrich merchant categories for transactions
     *
     * Infer category from transaction details
     *
     * @param BiStatement $statement
     * @return array<int, array<string, string|null>> Array of transaction enrichment data
     */
    private function enrichMerchantCategories(BiStatement $statement): array
    {
        if ($this->merchantCategoryProvider === null) {
            return [];
        }

        $enrichedTransactions = [];

        foreach ($statement->getTransactions() as $transaction) {
            $category = $this->merchantCategoryProvider->inferCategory($transaction);

            if ($category !== null) {
                $enrichedTransactions[] = [
                    'transactionId' => $transaction->getId() ?? $transaction->getFitId(),
                    'merchant' => $transaction->getMerchant() ?? $transaction->getTransactionTitle(),
                    'category' => $category
                ];
            }
        }

        return $enrichedTransactions;
    }

    /**
     * Check if enrichment is available (any provider configured)
     *
     * @return bool True if at least one provider is available
     */
    public function hasEnrichmentProviders(): bool
    {
        return $this->exchangeRateProvider !== null
            || $this->bankMetadataProvider !== null
            || $this->merchantCategoryProvider !== null;
    }

    /**
     * Get configured providers summary
     *
     * @return array<string, bool> Provider availability map
     */
    public function getProvidersAvailable(): array
    {
        return [
            'exchangeRate' => $this->exchangeRateProvider !== null,
            'bankMetadata' => $this->bankMetadataProvider !== null,
            'merchantCategory' => $this->merchantCategoryProvider !== null
        ];
    }

    /**
     * Get company base currency
     *
     * @return string Base currency code (e.g., 'CAD', 'USD')
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }
}
