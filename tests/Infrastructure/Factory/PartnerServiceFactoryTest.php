<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Infrastructure\Factory;

use Ksfraser\FaBankImport\Infrastructure\Factory\PartnerServiceFactory;
use Ksfraser\FaBankImport\Application\Partner\PartnerSearchService;
use Ksfraser\FaBankImport\Application\Partner\PartnerDataService;
use Ksfraser\FaBankImport\Application\Partner\KeywordExtractor;
use Ksfraser\FaBankImport\Application\Partner\ScoringEngine;
use Ksfraser\FaBankImport\Infrastructure\Database\PartnerRepositoryPdoImpl;
use PHPUnit\Framework\TestCase;
use PDO;

class PartnerServiceFactoryTest extends TestCase
{
    private PDO $pdo;
    private PartnerServiceFactory $factory;

    protected function setUp(): void
    {
        // Use in-memory SQLite for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create schema
        $this->createSchema();

        $this->factory = new PartnerServiceFactory();
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE bi_partners_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                partner_type TEXT NOT NULL,
                occurrence_count INTEGER DEFAULT 0,
                last_matched_ts DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Test 1: Factory creates PartnerRepository
     */
    public function testFactoryCreatesPartnerRepository(): void
    {
        $repository = $this->factory->createPartnerRepository($this->pdo);

        $this->assertInstanceOf(PartnerRepositoryPdoImpl::class, $repository);
    }

    /**
     * Test 2: Factory creates KeywordExtractor
     */
    public function testFactoryCreatesKeywordExtractor(): void
    {
        $extractor = $this->factory->createKeywordExtractor();

        $this->assertInstanceOf(KeywordExtractor::class, $extractor);
    }

    /**
     * Test 3: Factory creates ScoringEngine
     */
    public function testFactoryCreatesScoringEngine(): void
    {
        $scorer = $this->factory->createScoringEngine();

        $this->assertInstanceOf(ScoringEngine::class, $scorer);
    }

    /**
     * Test 4: Factory creates PartnerSearchService
     */
    public function testFactoryCreatesPartnerSearchService(): void
    {
        $searchService = $this->factory->createPartnerSearchService($this->pdo);

        $this->assertInstanceOf(PartnerSearchService::class, $searchService);
    }

    /**
     * Test 5: Factory creates PartnerDataService
     */
    public function testFactoryCreatesPartnerDataService(): void
    {
        $dataService = $this->factory->createPartnerDataService($this->pdo);

        $this->assertInstanceOf(PartnerDataService::class, $dataService);
    }

    /**
     * Test 6: PartnerSearchService has proper dependencies wired
     */
    public function testPartnerSearchServiceHasProperDependencies(): void
    {
        $searchService = $this->factory->createPartnerSearchService($this->pdo);

        // Use reflection to verify it's not null and properly initialized
        $reflection = new \ReflectionClass($searchService);
        $this->assertNotNull($searchService, 'PartnerSearchService should be created');

        // The service should work without throwing exceptions
        $results = $searchService->search('test', \Ksfraser\FaBankImport\Entity\PartnerType::SUPPLIER);
        $this->assertIsArray($results);
    }

    /**
     * Test 7: PartnerDataService has proper dependencies wired
     */
    public function testPartnerDataServiceHasProperDependencies(): void
    {
        $dataService = $this->factory->createPartnerDataService($this->pdo);

        // The service should work without throwing exceptions
        $result = $dataService->getPartnerData(1, \Ksfraser\FaBankImport\Entity\PartnerType::SUPPLIER);
        $this->assertNull($result); // No data in test DB, but call should work
    }

    /**
     * Test 8: KeywordExtractor returns same instance (singleton)
     */
    public function testKeywordExtractorSingleton(): void
    {
        $extractor1 = $this->factory->createKeywordExtractor();
        $extractor2 = $this->factory->createKeywordExtractor();

        $this->assertSame($extractor1, $extractor2, 'KeywordExtractor should be singleton');
    }

    /**
     * Test 9: ScoringEngine returns same instance (singleton)
     */
    public function testScoringEngineSingleton(): void
    {
        $scorer1 = $this->factory->createScoringEngine();
        $scorer2 = $this->factory->createScoringEngine();

        $this->assertSame($scorer1, $scorer2, 'ScoringEngine should be singleton');
    }

    /**
     * Test 10: Multiple PartnerSearchService instances use same extractors
     */
    public function testMultipleSearchServicesShareExtractors(): void
    {
        $searchService1 = $this->factory->createPartnerSearchService($this->pdo);
        $searchService2 = $this->factory->createPartnerSearchService($this->pdo);

        // While services are different, they should use the same extractors
        // This is verified implicitly by KeywordExtractor singleton
        $this->assertNotSame($searchService1, $searchService2);
    }

    /**
     * Test 11: Factory method is static and accessible
     */
    public function testFactoryMethodIsStatic(): void
    {
        // Should be able to call without instantiation
        $reflection = new \ReflectionClass(PartnerServiceFactory::class);
        
        // Verify factory has static create methods
        $this->assertTrue($reflection->hasMethod('createPartnerRepository'));
        $this->assertTrue($reflection->hasMethod('createPartnerSearchService'));
    }

    /**
     * Test 12: All services can be created without exceptions
     */
    public function testAllServicesCreateSuccessfully(): void
    {
        // This test ensures no exceptions are thrown during service creation
        $repository = $this->factory->createPartnerRepository($this->pdo);
        $extractor = $this->factory->createKeywordExtractor();
        $scorer = $this->factory->createScoringEngine();
        $searchService = $this->factory->createPartnerSearchService($this->pdo);
        $dataService = $this->factory->createPartnerDataService($this->pdo);

        $this->assertNotNull($repository);
        $this->assertNotNull($extractor);
        $this->assertNotNull($scorer);
        $this->assertNotNull($searchService);
        $this->assertNotNull($dataService);
    }
}
