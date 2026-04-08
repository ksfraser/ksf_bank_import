<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

/**
 * Unified result from duplicate matcher
 *
 * Value Object representing the outcome of a duplicate detection check.
 * Allows matchers to return comparable results with consistent structure.
 *
 * Benefits:
 * - Unified interface: All matchers return the same result type
 * - Composable: Results can be combined in chain
 * - Immutable: Safe to pass around
 * - Chainable: Easy to determine "should continue chain" logic
 */
final class DuplicateMatchResult
{
    /**
     * Whether a match was found
     *
     * @var bool
     */
    private bool $isMatch;

    /**
     * Confidence of match (0.0 - 1.0)
     *
     * @var float
     */
    private float $confidence;

    /**
     * Match details for logging/debugging
     *
     * @var array<string, mixed>
     */
    private array $details;

    /**
     * If match found, recommended action
     *
     * @var string|null
     */
    private ?string $action;

    /**
     * Create match result
     *
     * @param bool $isMatch Whether match was found
     * @param float $confidence Confidence score (0.0-1.0)
     * @param array<string, mixed> $details Match details
     * @param string|null $action Recommended action (review/merge/skip)
     */
    public function __construct(
        bool $isMatch,
        float $confidence = 0.0,
        array $details = [],
        ?string $action = null
    ) {
        $this->isMatch = $isMatch;
        $this->confidence = max(0.0, min(1.0, $confidence)); // Clamp to 0-1
        $this->details = $details;
        $this->action = $action;
    }

    /**
     * Check if match was found
     *
     * @return bool True if match found
     */
    public function isMatch(): bool
    {
        return $this->isMatch;
    }

    /**
     * Get match confidence
     *
     * @return float Confidence score (0.0-1.0)
     */
    public function getConfidence(): float
    {
        return $this->confidence;
    }

    /**
     * Get match details
     *
     * @return array<string, mixed> Details about match
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Get recommended action
     *
     * @return string|null Action code (review/merge/skip) or null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Check if result is high confidence match
     *
     * @param float $threshold Minimum confidence (default 0.8)
     * @return bool True if match and confidence >= threshold
     */
    public function isHighConfidence(float $threshold = 0.8): bool
    {
        return $this->isMatch && $this->confidence >= $threshold;
    }

    /**
     * Create no-match result
     *
     * Factory for readable code
     *
     * @param array<string, mixed> $details Optional details
     * @return self
     */
    public static function noMatch(array $details = []): self
    {
        return new self(false, 0.0, $details);
    }

    /**
     * Create match result
     *
     * Factory for readable code
     *
     * @param float $confidence Confidence score
     * @param array<string, mixed> $details Match details
     * @param string|null $action Recommended action
     * @return self
     */
    public static function match(
        float $confidence,
        array $details = [],
        ?string $action = null
    ): self {
        return new self(true, $confidence, $details, $action);
    }
}
