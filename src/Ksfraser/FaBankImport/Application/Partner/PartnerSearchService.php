<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerMatchResult;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * PartnerSearchService
 * 
 * Orchestrator that coordinates partner matching across multiple domain services.
 * This service is INDEPENDENT - it never depends on PROD code (pdata.inc, views, etc).
 * 
 * Responsibilities:
 * - Accept a search text and partner type
 * - Extract keywords using KeywordExtractor
 * - Retrieve candidate partners from PartnerRepository
 * - Score each candidate using ScoringEngine with all 6 factors
 * - Return ranked PartnerMatchResult[] (highest confidence first)
 * - Optionally auto-select best match if confidence > 75%
 * - Update partner on successful match (occurrence count, last matched timestamp)
 * 
 * This replaces:
 * - Direct calls to search_partner_keywords() in pdata.inc
 * - Ad-hoc search logic in build_partner_keyword_data.php
 * - Manual partner selection in views
 */
final class PartnerSearchService
{
    private const CONFIDENCE_THRESHOLD = 0.75; // 75% for auto-select

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly KeywordExtractor $keywordExtractor,
        private readonly ScoringEngine $scoringEngine,
    ) {}

    /**
     * Search for partners matching the given text.
     * 
     * Process:
     * 1. Extract keywords from search text
     * 2. Fetch all candidates of the given type from repository
     * 3. Score each candidate using 6-factor algorithm
     * 4. Sort by confidence descending
     * 5. Return top results as ranked PartnerMatchResult[]
     * 
     * @param string $searchText Transaction description, bank reference, etc.
     * @param PartnerType $partnerType The type of partners to search for
     * @param int $limit Maximum results to return (default: unlimited)
     * @return PartnerMatchResult[] Sorted by confidence (highest first)
     */
    public function search(string $searchText, PartnerType $partnerType, int $limit = 0): array
    {
        // Early return: empty search text
        if (empty(trim($searchText))) {
            return [];
        }

        $searchTextTrimmed = trim($searchText);

        // Extract keywords from search text
        $searchKeywords = $this->keywordExtractor->extract($searchTextTrimmed);

        // Fetch all candidate partners of this type
        $candidates = $this->partnerRepository->getByType($partnerType);
        $clusterSizeByPartnerId = $this->buildClusterSizeMap($candidates);

        // Score each candidate and build results
        $results = [];
        foreach ($candidates as $candidate) {
            $clusterSize = $clusterSizeByPartnerId[$candidate->id()] ?? 1;
            $factors = $this->calculateFactors($searchTextTrimmed, $searchKeywords, $candidate, $clusterSize);
            $confidence = $this->calculateConfidence($factors);

            $results[] = new PartnerMatchResult(
                partner: $candidate,
                confidence: $confidence,
                factors: $factors,
            );
        }

        // Sort by confidence descending (highest first)
        usort($results, fn(PartnerMatchResult $a, PartnerMatchResult $b) =>
            $b->confidence() <=> $a->confidence()
        );

        // Apply limit if specified
        if ($limit > 0 && count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Automatically select the best matching partner if confidence is high enough.
     * 
     * If the top match has confidence >= 75%, this method will:
     * 1. Call search() to find candidates
     * 2. Check if top result meets threshold
     * 3. Update the partner (increment occurrence_count, update last_matched_ts)
     * 4. Return the PartnerMatchResult
     * 
     * If no match meets threshold, returns null (human review needed).
     * 
     * @param string $searchText Transaction description
     * @param PartnerType $partnerType Type of partner to auto-select
     * @return PartnerMatchResult|null Best match if confidence >= 75%, null otherwise
     */
    public function autoSelect(string $searchText, PartnerType $partnerType): ?PartnerMatchResult
    {
        $results = $this->search($searchText, $partnerType, limit: 1);

        if (empty($results)) {
            return null;
        }

        $topMatch = $results[0];

        // Check if confidence meets auto-select threshold
        if ($topMatch->confidence() < self::CONFIDENCE_THRESHOLD) {
            return null;
        }

        // Update partner: increment occurrence count and update last matched timestamp
        $updatedPartner = new PartnerEntity(
            id: $topMatch->partner()->id(),
            name: $topMatch->partner()->name(),
            type: $topMatch->partner()->type(),
            occurrenceCount: $topMatch->partner()->occurrenceCount() + 1,
            lastMatchedTs: new \DateTime(), // Current timestamp
        );

        $this->partnerRepository->update($updatedPartner);

        return $topMatch;
    }

    /**
     * Calculate all 6 factors for a single candidate partner.
     * 
     * Factors:
     * 1. substring: +100 if search text contains partner name
     * 2. keyword: +10 per keyword match
     * 3. account: +80 if account number matches
     * 4. occurrence: multiplier (1.0 / sqrt(count), minimum 0.5)
     * 5. recency: multiplier (exponential decay over days)
     * 6. clustering: multiplier (bonus for accounts with few partners)
     * 
     * @param string $searchText Original search text
     * @param string[] $searchKeywords Extracted keywords from search text
     * @param PartnerEntity $candidate Partner being scored
     * @return array{substring: float, keyword: float, account: float, occurrence: float, recency: float, clustering: float}
     */
    private function calculateFactors(
        string $searchText,
        array $searchKeywords,
        PartnerEntity $candidate,
        int $clusterSize = 1
    ): array {
        $partnerName = $candidate->name();
        $partnerKeywords = $this->keywordExtractor->extract($partnerName);

        // Handle null lastMatchedTs
        $lastMatchedTs = $candidate->lastMatchedTs() ?? new \DateTime('-1 year');

        $searchAccounts = $this->extractAccountHints($searchText);
        $partnerAccounts = $this->extractAccountHints($partnerName);
        $accountScore = 0.0;
        if (!empty($searchAccounts)) {
            $accountScore = $this->scoringEngine->calculateAccountScore($searchAccounts[0], $partnerAccounts);
        }

        return [
            'substring' => $this->scoringEngine->calculateSubstringScore($searchText, $partnerName),
            'keyword' => $this->scoringEngine->calculateKeywordScore($searchKeywords, $partnerKeywords),
            'account' => $accountScore,
            'occurrence' => $this->scoringEngine->calculateOccurrenceMultiplier($candidate->occurrenceCount()),
            'recency' => $this->scoringEngine->calculateRecencyMultiplier($lastMatchedTs, new \DateTime()),
            'clustering' => $this->scoringEngine->calculateClusteringBonus($candidate->name(), max(1, $clusterSize)),
        ];
    }

    /**
     * Extract likely account-number tokens from free text.
     *
     * @param string $text
     * @return string[]
     */
    private function extractAccountHints(string $text): array
    {
        if (!preg_match_all('/\b\d{3,}\b/', $text, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[0]));
    }

    /**
     * Build a simple cluster-size map based on shared account hints in partner names.
     *
     * @param PartnerEntity[] $candidates
     * @return array<int,int> keyed by partner ID
     */
    private function buildClusterSizeMap(array $candidates): array
    {
        $clusters = [];
        foreach ($candidates as $candidate) {
            $hints = $this->extractAccountHints($candidate->name());
            if (empty($hints)) {
                $clusters[$candidate->id()] = 1;
                continue;
            }

            $clusterSize = 0;
            foreach ($candidates as $other) {
                $otherHints = $this->extractAccountHints($other->name());
                if (!empty(array_intersect($hints, $otherHints))) {
                    $clusterSize++;
                }
            }
            $clusters[$candidate->id()] = max(1, $clusterSize);
        }

        return $clusters;
    }

    /**
     * Calculate final confidence from all 6 factors.
     * 
     * Formula (from ScoringEngine):
     * (substring + keyword + account) × occurrence × recency × clustering
     * Clamped to [0.0, 1.0]
     * 
     * @param array $factors Six factors from calculateFactors()
     * @return float Confidence [0.0 to 1.0]
     */
    private function calculateConfidence(array $factors): float
    {
        // Use ScoringEngine to combine factors
        $totalScore = $this->scoringEngine->calculateCombinedScore($factors);

        // Normalize to [0.0, 1.0]
        // Max possible score: (100 + 100 + 80) × 1.0 × 1.0 × 1.2 = 295.2
        $maxScore = 295.2;
        $normalized = min(1.0, max(0.0, $totalScore / $maxScore));

        return $normalized;
    }
}
