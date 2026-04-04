<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\DuplicateDetectedException;

/**
 * Contract for managing duplicate detection and review
 *
 * Handles:
 * - Creating review sessions for suspected duplicates
 * - Storing review data for user decision
 * - Retrieving pending reviews
 * - Recording user decisions
 */
interface ReviewStagerInterface
{
    /**
     * Create review session for duplicates
     *
     * @param string $sessionId Import session ID
     * @param array<int, array<string, mixed>> $duplicates Duplicate matches found
     * @return array<string, mixed> Review session metadata
     *
     * @throws DuplicateDetectedException If no duplicates to review
     */
    public function createReviewSession(string $sessionId, array $duplicates): array;

    /**
     * Get pending review sessions
     *
     * @param int|null $limit Maximum number to retrieve
     * @return array<int, array<string, mixed>> Pending reviews
     */
    public function getPendingReviews(?int $limit = null): array;

    /**
     * Record user decision on duplicate
     *
     * @param string $reviewId Review session ID
     * @param string $decision 'accept', 'reject', 'link'
     * @param array<string, mixed> $metadata Decision metadata
     * @return void
     */
    public function recordDecision(
        string $reviewId,
        string $decision,
        array $metadata = []
    ): void;

    /**
     * Get staging manager name
     *
     * @return string
     */
    public function getName(): string;
}
