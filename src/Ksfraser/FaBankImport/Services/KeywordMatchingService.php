<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence;
use Ksfraser\FaBankImport\Domain\Exceptions\PartnerDataNotFoundException;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;
use Ksfraser\FaBankImport\Config\ConfigService;

/**
 * @BABOK CLASS ROLLUP: KeywordMatchingService — 9 methods; BR-001 (9); BR-002 (9); FR-008 (9)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/KeywordMatchingService.php; coverage: docs/TestCases/ (file rollup)
 * Service for keyword-based partner matching with scoring and confidence
 *
 * Implements the keyword clustering bonus algorithm and multi-factor
 * confidence calculation for pattern matching.
 *
 * @package Ksfraser\FaBankImport\Services
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class KeywordMatchingService
{
    /**
     * @var PartnerDataRepositoryInterface Repository for partner data
     */
    private PartnerDataRepositoryInterface $repository;

    /**
     * @var KeywordExtractorService Keyword extraction service
     */
    private KeywordExtractorService $extractor;

    /**
     * @var ConfigService|null Configuration service
     */
    private ?ConfigService $configService;

    /**
     * @var float Clustering factor for bonus calculation
     */
    private float $clusteringFactor;

    /**
     * @var float Minimum confidence threshold (percentage)
     */
    private float $minConfidenceThreshold;

    /**
     * @var int Maximum number of suggestions to return
     */
    private int $maxSuggestions;

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct
     * Constructor
     *
     * @param PartnerDataRepositoryInterface $repository Partner data repository
     * @param KeywordExtractorService        $extractor  Keyword extraction service
     * @param ConfigService|null             $configService Configuration service (optional)
     */
    public function __construct(
        PartnerDataRepositoryInterface $repository,
        KeywordExtractorService $extractor,
        ?ConfigService $configService = null
    ) {
        $this->repository = $repository;
        $this->extractor = $extractor;
        $this->configService = $configService;

        // Load configuration
        $this->loadConfiguration();
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: search
     * Search for partners by keywords with scoring
     *
     * @param string   $searchText  Text to search for
     * @param int|null $partnerType Optional partner type filter
     * @param int|null $limit       Maximum results (uses config default if null)
     *
     * @return array<KeywordMatch> Array of KeywordMatch objects, sorted by relevance
     */
    public function search(string $searchText, ?int $partnerType = null, ?int $limit = null): array
    {
        // Extract keywords from search text
        $searchKeywords = $this->extractor->extract($searchText);
        
        if (empty($searchKeywords)) {
            return [];
        }

        // Convert keywords to strings for repository search
        $keywordStrings = array_map(fn(Keyword $k) => $k->getText(), $searchKeywords);

        // Search repository
        $limit = $limit ?? $this->maxSuggestions;
        $partnerMatches = $this->repository->searchByKeywords($keywordStrings, $partnerType, $limit * 2);

        if (empty($partnerMatches)) {
            return [];
        }

        // Convert to KeywordMatch objects with scoring and confidence
        $matches = $this->buildKeywordMatches($partnerMatches, $searchKeywords);

        // Filter by confidence threshold
        $matches = array_filter(
            $matches,
            fn(KeywordMatch $m) => $m->meetsConfidenceThreshold($this->minConfidenceThreshold)
        );

        // Sort by keyword count (desc), then score (desc)
        usort($matches, function(KeywordMatch $a, KeywordMatch $b) {
            $keywordCountCompare = $b->getMatchedKeywordCount() - $a->getMatchedKeywordCount();
            if ($keywordCountCompare !== 0) {
                return $keywordCountCompare;
            }
            return $b->getFinalScore() <=> $a->getFinalScore();
        });

        // Apply limit
        return array_slice($matches, 0, $limit);
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getTopMatch
     * Get the top suggested partner for a search text
     *
     * Returns the single best match, or null if no match meets confidence threshold.
     *
     * @param string   $searchText  Text to search for
     * @param int|null $partnerType Optional partner type filter
     *
     * @return KeywordMatch|null Top match or null if no qualifying match
     */
    public function getTopMatch(string $searchText, ?int $partnerType = null): ?KeywordMatch
    {
        $matches = $this->search($searchText, $partnerType, 1);
        return !empty($matches) ? $matches[0] : null;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: calculateScore
     * Calculate score for a partner match
     *
     * Applies clustering bonus to raw score based on number of matched keywords.
     * Formula: final_score = raw_score * (1 + ((keyword_count - 1) * clustering_factor))
     *
     * @param int $rawScore      Raw score (sum of occurrence counts)
     * @param int $keywordCount  Number of keywords that matched
     *
     * @return float Final score with clustering bonus applied
     */
    public function calculateScore(int $rawScore, int $keywordCount): float
    {
        if ($keywordCount <= 0 || $rawScore <= 0) {
            return 0.0;
        }

        // Clustering bonus: multiplicative boost for multiple keyword matches
        $clusteringMultiplier = 1.0 + (($keywordCount - 1) * $this->clusteringFactor);
        
        return $rawScore * $clusteringMultiplier;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: buildKeywordMatches
     * Build KeywordMatch objects from repository results
     *
     * @param array<array>     $partnerMatches Raw partner match data from repository
     * @param array<Keyword>   $searchKeywords Search keywords
     *
     * @return array<KeywordMatch> Array of KeywordMatch objects
     */
    private function buildKeywordMatches(array $partnerMatches, array $searchKeywords): array
    {
        $keywordMatches = [];
        
        // Find top score for confidence calculation
        $topScore = 0;
        foreach ($partnerMatches as $match) {
            $rawScore = $match['total_score'];
            $keywordCount = $match['keyword_count'];
            $finalScore = $this->calculateScore($rawScore, $keywordCount);
            
            if ($finalScore > $topScore) {
                $topScore = $finalScore;
            }
        }

        // Build KeywordMatch objects
        foreach ($partnerMatches as $match) {
            // Convert matched keyword strings back to Keyword objects
            $keywordStrings = explode(',', $match['matched_keywords']);
            $matchedKeywords = array_map(
                fn(string $k) => new Keyword(trim($k)),
                $keywordStrings
            );

            // Calculate scores
            $rawScore = $match['total_score'];
            $keywordCount = count($matchedKeywords);
            $finalScore = $this->calculateScore($rawScore, $keywordCount);

            // Calculate confidence
            $confidence = MatchConfidence::fromMatchStatistics(
                $keywordCount,
                count($searchKeywords),
                $finalScore,
                $topScore
            );

            // Get partner name (if available in match data, otherwise use partner_id)
            $partnerName = $match['partner_name'] ?? 'Partner #' . $match['partner_id'];

            // Create KeywordMatch object
            $keywordMatches[] = new KeywordMatch(
                $match['partner_id'],
                $match['partner_type'],
                $match['partner_detail_id'],
                $partnerName,
                $matchedKeywords,
                $rawScore,
                $finalScore,
                $confidence
            );
        }

        return $keywordMatches;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: loadConfiguration
     * Load configuration from ConfigService
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        if ($this->configService === null) {
            // Use defaults
            $this->clusteringFactor = 0.2;
            $this->minConfidenceThreshold = 30.0;
            $this->maxSuggestions = 5;
            return;
        }

        $this->clusteringFactor = (float)$this->configService->get(
            'pattern_matching.keyword_clustering_factor',
            0.2
        );

        $this->minConfidenceThreshold = (float)$this->configService->get(
            'pattern_matching.min_confidence_threshold',
            30.0
        );

        $this->maxSuggestions = (int)$this->configService->get(
            'pattern_matching.max_suggestions',
            5
        );
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getClusteringFactor
     * Get current clustering factor
     *
     * @return float
     */
    public function getClusteringFactor(): float
    {
        return $this->clusteringFactor;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getMinConfidenceThreshold
     * Get minimum confidence threshold
     *
     * @return float
     */
    public function getMinConfidenceThreshold(): float
    {
        return $this->minConfidenceThreshold;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getMaxSuggestions
     * Get maximum suggestions
     *
     * @return int
     */
    public function getMaxSuggestions(): int
    {
        return $this->maxSuggestions;
    }
}
