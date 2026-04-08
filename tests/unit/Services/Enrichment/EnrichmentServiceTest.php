<?php

namespace Tests\Unit\Services\Enrichment;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Enrichment\EnrichmentService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Tests for EnrichmentService
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Enrichment\EnrichmentService
 */
class EnrichmentServiceTest extends TestCase
{
    /**
     * Enrichment service under test
     *
     * @var EnrichmentService
     */
    private EnrichmentService $enrichmentService;

    protected function setUp(): void
    {
        // Create service with no providers (default)
        $this->enrichmentService = new EnrichmentService();
    }

    /**
     * Test: Enrich without any providers (no-op)
     */
    public function testEnrichWithoutProvidersReturnsStatement(): void
    {
        $statement = $this->buildTestStatement('CAD');

        $result = $this->enrichmentService->enrich($statement);

        // Should return the same statement
        $this->assertSame($statement, $result);
    }

    /**
     * Test: No providers available initially
     */
    public function testNoEnrichmentProvidersAvailable(): void
    {
        $this->assertFalse($this->enrichmentService->hasEnrichmentProviders());
    }

    /**
     * Test: Get providers available (all unavailable)
     */
    public function testGetProvidersAvailableAllUnavailable(): void
    {
        $providers = $this->enrichmentService->getProvidersAvailable();

        $this->assertFalse($providers['exchangeRate']);
        $this->assertFalse($providers['bankMetadata']);
        $this->assertFalse($providers['merchantCategory']);
    }

    /**
     * Test: Create with exchange rate provider available
     */
    public function testCreateWithExchangeRateProvider(): void
    {
        $mockProvider = new class {
            public function getRate(string $from, string $to): float {
                return 1.35; // Mock rate
            }
        };

        $service = new EnrichmentService(
            exchangeRateProvider: $mockProvider
        );

        $this->assertTrue($service->hasEnrichmentProviders());

        $providers = $service->getProvidersAvailable();
        $this->assertTrue($providers['exchangeRate']);
        $this->assertFalse($providers['bankMetadata']);
    }

    /**
     * Test: Create with bank metadata provider available
     */
    public function testCreateWithBankMetadataProvider(): void
    {
        $mockProvider = new class {
            public function lookupBank(string $bankId): array {
                return [
                    'swift' => 'ROYALTIES22',
                    'routing' => '002000033',
                    'contact' => 'contact@bank.com'
                ];
            }
        };

        $service = new EnrichmentService(
            bankMetadataProvider: $mockProvider
        );

        $this->assertTrue($service->hasEnrichmentProviders());

        $providers = $service->getProvidersAvailable();
        $this->assertFalse($providers['exchangeRate']);
        $this->assertTrue($providers['bankMetadata']);
    }

    /**
     * Test: Create with merchant category provider available
     */
    public function testCreateWithMerchantCategoryProvider(): void
    {
        $mockProvider = new class {
            public function categorize(string $merchant): array {
                return [
                    'name' => 'Groceries',
                    'confidence' => 0.95,
                    'subcategory' => 'Supermarket'
                ];
            }
        };

        $service = new EnrichmentService(
            merchantCategoryProvider: $mockProvider
        );

        $this->assertTrue($service->hasEnrichmentProviders());

        $providers = $service->getProvidersAvailable();
        $this->assertFalse($providers['exchangeRate']);
        $this->assertTrue($providers['merchantCategory']);
    }

    /**
     * Test: All three providers available
     */
    public function testCreateWithAllProvidersAvailable(): void
    {
        $mockExchange = new class {
            public function getRate(string $from, string $to): float {
                return 1.35;
            }
        };

        $mockBank = new class {
            public function lookupBank(string $bankId): array {
                return ['swift' => 'TESTBANK', 'routing' => '123456'];
            }
        };

        $mockCategory = new class {
            public function categorize(string $merchant): array {
                return ['name' => 'Dining', 'confidence' => 0.99];
            }
        };

        $service = new EnrichmentService(
            exchangeRateProvider: $mockExchange,
            bankMetadataProvider: $mockBank,
            merchantCategoryProvider: $mockCategory
        );

        $this->assertTrue($service->hasEnrichmentProviders());

        $providers = $service->getProvidersAvailable();
        $this->assertTrue($providers['exchangeRate']);
        $this->assertTrue($providers['bankMetadata']);
        $this->assertTrue($providers['merchantCategory']);
    }

    /**
     * Test: Enrich foreign currency statement with exchange rate provider
     */
    public function testEnrichForeignCurrencyWithExchangeRateProvider(): void
    {
        $mockProvider = new class {
            public function getRate(string $from, string $to): float {
                if ($from === 'USD' && $to === 'CAD') {
                    return 1.35;
                }
                return 1.0;
            }
        };

        $service = new EnrichmentService(exchangeRateProvider: $mockProvider);

        // Create USD statement
        $statement = $this->buildTestStatement(currency: 'USD');

        $result = $service->enrich($statement);

        // Should return statement (enrichment is metadata)
        $this->assertNotNull($result);
    }

    /**
     * Test: Skip enrichment for CAD currency (no exchange needed)
     */
    public function testSkipExchangeRateForCADCurrency(): void
    {
        $mockProvider = new class {
            public function getRate(string $from, string $to): float {
                throw new \Exception('Should not be called for CAD');
            }
        };

        $service = new EnrichmentService(exchangeRateProvider: $mockProvider);

        // Create CAD statement
        $statement = $this->buildTestStatement(currency: 'CAD');

        // Should not throw because exchange rate lookup is skipped for CAD
        $result = $service->enrich($statement);

        $this->assertNotNull($result);
    }

    /**
     * Test: Graceful handling of provider errors
     */
    public function testGracefulErrorHandlingInProviders(): void
    {
        $mockProvider = new class {
            public function getRate(string $from, string $to): float {
                throw new \Exception('Exchange rate service unavailable');
            }
        };

        $service = new EnrichmentService(exchangeRateProvider: $mockProvider);

        $statement = $this->buildTestStatement(currency: 'EUR');

        // Should not throw - error is caught and logged
        $result = $service->enrich($statement);

        $this->assertNotNull($result);
    }

    /**
     * Test: Enrich with all three providers
     */
    public function testEnrichWithAllProvidersSucceed(): void
    {
        $mockExchange = new class {
            public function getRate(string $from, string $to): float {
                return 1.35;
            }
        };

        $mockBank = new class {
            public function lookupBank(string $bankId): array {
                return [
                    'swift' => 'TESTSW1',
                    'routing' => '111000025',
                    'contact' => 'test@bank.ca',
                    'region' => 'Canada'
                ];
            }
        };

        $mockCategory = new class {
            public function categorize(string $merchant): array {
                return [
                    'name' => 'Utilities',
                    'confidence' => 0.92,
                    'subcategory' => 'Electricity'
                ];
            }
        };

        $service = new EnrichmentService(
            exchangeRateProvider: $mockExchange,
            bankMetadataProvider: $mockBank,
            merchantCategoryProvider: $mockCategory
        );

        $statement = $this->buildTestStatement(currency: 'USD');

        $result = $service->enrich($statement);

        // All enrichments should complete without error
        $this->assertNotNull($result);
    }

    /**
     * Helper: Build test statement
     *
     * @param string $currency Currency code
     * @return BiStatement
     */
    protected function buildTestStatement(string $currency = 'CAD'): BiStatement
    {
        return BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-001',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => $currency,
            'startBalance' => 1000.00,
            'endBalance' => 1500.00,
            'smtDate' => '2024-01-01'
        ], []);
    }
}
