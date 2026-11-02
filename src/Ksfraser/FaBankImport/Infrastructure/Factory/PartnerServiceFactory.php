<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Factory;

use Ksfraser\FaBankImport\Application\Partner\KeywordExtractor;
use Ksfraser\FaBankImport\Application\Partner\ScoringEngine;
use Ksfraser\FaBankImport\Application\Partner\PartnerSearchService;
use Ksfraser\FaBankImport\Application\Partner\PartnerDataService;
use Ksfraser\FaBankImport\Infrastructure\Database\PartnerRepositoryPdoImpl;
use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use PDO;

/**
 * @BABOK CLASS ROLLUP: PartnerServiceFactory — 6 methods; FR-006 (6)
 * @BABOK RTM: FR-006 / ARCH-007; file: Infrastructure/Factory/PartnerServiceFactory.php; coverage: docs/TestCases/ (file rollup)
 * PartnerServiceFactory
 * 
 * Centralized factory for creating partner-related services.
 * Handles dependency injection and initialization of all partner services.
 * 
 * Usage:
 * ```php
 * $factory = new PartnerServiceFactory();
 * $searchService = $factory->createPartnerSearchService($pdo);
 * $dataService = $factory->createPartnerDataService($pdo);
 * ```
 * 
 * Services are cached where appropriate:
 * - KeywordExtractor: Singleton (shared across all uses)
 * - ScoringEngine: Singleton (shared across all uses)
 * - Repository: New instance per creation (each needs own connection)
 * - Search/Data Services: New instance per creation (uses fresh repository)
 */
final class PartnerServiceFactory
{
    /**
     * @var KeywordExtractor|null Singleton instance
     */
    private static ?KeywordExtractor $keywordExtractor = null;

    /**
     * @var ScoringEngine|null Singleton instance
     */
    private static ?ScoringEngine $scoringEngine = null;

    /**
 * @BABOK FR-006 / ARCH-007 — method: createPartnerRepository
     * Create a PartnerRepository instance
     * 
     * @param PDO $pdo PDO connection to use
     * @return PartnerRepository
     */
    public function createPartnerRepository(PDO $pdo): PartnerRepository
    {
        return new PartnerRepositoryPdoImpl($pdo);
    }

    /**
 * @BABOK FR-006 / ARCH-007 — method: createKeywordExtractor
     * Create a KeywordExtractor instance
     * 
     * Returns singleton to avoid re-initializing phrase/stopword lists.
     * 
     * @return KeywordExtractor
     */
    public function createKeywordExtractor(): KeywordExtractor
    {
        if (self::$keywordExtractor === null) {
            self::$keywordExtractor = new KeywordExtractor();
        }

        return self::$keywordExtractor;
    }

    /**
 * @BABOK FR-006 / ARCH-007 — method: createScoringEngine
     * Create a ScoringEngine instance
     * 
     * Returns singleton since it's stateless.
     * 
     * @return ScoringEngine
     */
    public function createScoringEngine(): ScoringEngine
    {
        if (self::$scoringEngine === null) {
            self::$scoringEngine = new ScoringEngine();
        }

        return self::$scoringEngine;
    }

    /**
 * @BABOK FR-006 / ARCH-007 — method: createPartnerSearchService
     * Create a PartnerSearchService instance
     * 
     * Orchestrates:
     * - PartnerRepository (injected with given PDO)
     * - KeywordExtractor (singleton)
     * - ScoringEngine (singleton)
     * 
     * @param PDO $pdo PDO connection to use
     * @return PartnerSearchService
     */
    public function createPartnerSearchService(PDO $pdo): PartnerSearchService
    {
        $repository = $this->createPartnerRepository($pdo);
        $extractor = $this->createKeywordExtractor();
        $scorer = $this->createScoringEngine();

        return new PartnerSearchService($repository, $extractor, $scorer);
    }

    /**
 * @BABOK FR-006 / ARCH-007 — method: createPartnerDataService
     * Create a PartnerDataService instance
     * 
     * Wraps:
     * - PartnerRepository (injected with given PDO)
     * 
     * @param PDO $pdo PDO connection to use
     * @return PartnerDataService
     */
    public function createPartnerDataService(PDO $pdo): PartnerDataService
    {
        $repository = $this->createPartnerRepository($pdo);

        return new PartnerDataService($repository);
    }

    /**
 * @BABOK FR-006 / ARCH-007 — method: reset
     * Reset singleton caches (useful for testing)
     */
    public static function reset(): void
    {
        self::$keywordExtractor = null;
        self::$scoringEngine = null;
    }
}
