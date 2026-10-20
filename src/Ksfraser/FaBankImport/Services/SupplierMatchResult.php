<?php

/**
 * Supplier Match Result Value Object
 *
 * Holds the results of supplier matching including matched suppliers,
 * confidence scores, and auto-match decision.
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

/**
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/SupplierMatchResult.php; coverage: docs/TestCases/ (file rollup)
 * Supplier Match Result
 *
 * Immutable value object representing supplier matching results.
 *
 * @since 7.6
 */
class SupplierMatchResult
{
    /**
     * Matched suppliers ordered by confidence
     *
     * @var array<array{
     *     supplier_id: int,
     *     supplier_name: string,
     *     supplier_type: int,
     *     confidence: int,
     *     raw_score: float,
     *     enhanced_score: float
     * }>
     */
    private array $matches;

    /**
     * Auto-match decision: 'auto', 'manual', or 'no_match'
     *
     * @var string
     */
    private string $decision;

    /**
     * Configuration used for matching
     *
     * @var SupplierMatchingConfiguration
     */
    private SupplierMatchingConfiguration $configuration;

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: __construct
     * Constructor
     *
     * @param array $matches Sorted matches array
     * @param string $decision Auto-match decision
     * @param SupplierMatchingConfiguration $configuration Configuration used
     * @since 7.6
     */
    public function __construct(
        array $matches,
        string $decision,
        SupplierMatchingConfiguration $configuration
    ) {
        $this->matches = $matches;
        $this->decision = $decision;
        $this->configuration = $configuration;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getMatches
     * Get all matched suppliers ordered by confidence
     *
     * @return array<array> Matches with confidence scores
     * @since 7.6
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getMatchCount
     * Get count of matches
     *
     * @return int Number of matches
     * @since 7.6
     */
    public function getMatchCount(): int
    {
        return count($this->matches);
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getBestMatch
     * Get the best (highest confidence) match
     *
     * @return array|null Best match or null if no matches
     * @since 7.6
     */
    public function getBestMatch(): ?array
    {
        return $this->matches[0] ?? null;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getBestSupplierId
     * Get the best supplier ID
     *
     * @return int|null Best matching supplier ID or null
     * @since 7.6
     */
    public function getBestSupplierId(): ?int
    {
        $best = $this->getBestMatch();
        return $best ? $best['supplier_id'] : null;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getDecision
     * Get auto-match decision
     *
     * @return string 'auto', 'manual', or 'no_match'
     * @since 7.6
     */
    public function getDecision(): string
    {
        return $this->decision;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: isAutoMatch
     * Is this an auto-match decision?
     *
     * @return bool True if auto-match decision
     * @since 7.6
     */
    public function isAutoMatch(): bool
    {
        return $this->decision === 'auto';
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: isManualSelection
     * Is this a manual selection decision?
     *
     * @return bool True if manual selection required
     * @since 7.6
     */
    public function isManualSelection(): bool
    {
        return $this->decision === 'manual';
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: isNoMatch
     * Is this a no-match result?
     *
     * @return bool True if no matches
     * @since 7.6
     */
    public function isNoMatch(): bool
    {
        return $this->decision === 'no_match';
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getConfiguration
     * Get configuration used for matching
     *
     * @return SupplierMatchingConfiguration Configuration reference
     * @since 7.6
     */
    public function getConfiguration(): SupplierMatchingConfiguration
    {
        return $this->configuration;
    }

    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: toArray
     * Convert to array for serialization
     *
     * @return array{
     *     matches: array,
     *     decision: string,
     *     match_count: int,
     *     best_supplier_id: int|null,
     *     is_auto_match: bool,
     *     is_manual: bool,
     *     is_no_match: bool
     * }
     * @since 7.6
     */
    public function toArray(): array
    {
        return [
            'matches' => $this->matches,
            'decision' => $this->decision,
            'match_count' => $this->getMatchCount(),
            'best_supplier_id' => $this->getBestSupplierId(),
            'is_auto_match' => $this->isAutoMatch(),
            'is_manual' => $this->isManualSelection(),
            'is_no_match' => $this->isNoMatch(),
        ];
    }
}
