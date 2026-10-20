<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * @BABOK RTM: FR-050 / ARCH-003; file: Exception/PartnerPersistenceException.php; coverage: docs/TestCases/ (file rollup)
 * PartnerPersistenceException - Thrown when database operations fail
 * 
 * Wraps PDO exceptions and other persistence layer errors.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerPersistenceException extends PartnerException
{
    /** @BABOK FR-050 / ARCH-003 — method: fromThrowable */
    public static function fromThrowable(\Throwable $previous, string $operation): self
    {
        return new self(
            sprintf('Database operation failed (%s): %s', $operation, $previous->getMessage()),
            0,
            $previous
        );
    }

    /** @BABOK FR-050 / ARCH-003 — method: updateWithoutId */
    public static function updateWithoutId(): self
    {
        return new self(
            'Cannot update partner: identity not set (id must be > 0)'
        );
    }

    /** @BABOK FR-050 / ARCH-003 — method: deleteNotFound */
    public static function deleteNotFound(): self
    {
        return new self('Delete operation failed: partner not found');
    }
}
