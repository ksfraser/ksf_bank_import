<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a database operation fails
 * 
 * This wraps and contextualizes repository-level exceptions
 */
class RepositoryException extends DuplicateReviewException
{
    /**
     * Factory method for wrapping repository error
     * 
     * @param string $operation The operation that failed (e.g., "update", "auditDecision")
     * @param string $reason The underlying error reason
     * @param \Throwable $previous The original exception
     * @return self
     */
    public static function operationFailed(
        string $operation,
        string $reason,
        ?\Throwable $previous = null
    ): self {
        return new self(
            message: "Repository operation '{$operation}' failed: {$reason}",
            code: 1006,
            previous: $previous
        );
    }
}
