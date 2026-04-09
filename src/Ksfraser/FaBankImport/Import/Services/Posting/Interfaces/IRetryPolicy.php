<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

/**
 * Policy for calculating exponential backoff retry delays.
 * Implements the retry strategy when posting fails.
 */
interface IRetryPolicy
{
    /**
     * Calculate the delay in seconds for the next retry attempt.
     * 
     * Exponential backoff formula: delay = base * (2 ^ attemptNumber)
     * Example: attempt 1 → 5s, attempt 2 → 10s, attempt 3 → 20s
     * 
     * @param int $attemptNumber The retry attempt number (1-based)
     * 
     * @return int Delay in seconds before next retry, or -1 if max retries exceeded
     */
    public function calculateBackoff(int $attemptNumber): int;
}
