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

namespace Ksfraser\FaBankImport\Services;

/**
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
