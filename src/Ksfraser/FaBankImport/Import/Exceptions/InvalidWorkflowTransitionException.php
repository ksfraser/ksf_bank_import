<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when attempting an invalid workflow state transition
 * 
 * Example: Attempting to approve a transaction that is already APPROVED
 */
class InvalidWorkflowTransitionException extends DuplicateReviewException
{
    /**
     * Factory method for creating exception with context
     * 
     * @param string $currentStatus The current decision status
     * @param string $attemptedDecision The decision being attempted
     * @return self
     */
    public static function attemptedInvalidTransition(
        string $currentStatus,
        string $attemptedDecision
    ): self {
        return new self(
            message: "Cannot transition from '{$currentStatus}' to '{$attemptedDecision}'. " .
                "Valid transitions from '{$currentStatus}' are limited by workflow rules.",
            code: 1001
        );
    }
}
