<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * PartnerPersistenceException - Thrown when database operations fail
 * 
 * Wraps PDO exceptions and other persistence layer errors.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerPersistenceException extends PartnerException
{
    public static function fromThrowable(\Throwable $previous, string $operation): self
    {
        return new self(
            sprintf('Database operation failed (%s): %s', $operation, $previous->getMessage()),
            0,
            $previous
        );
    }

    public static function updateWithoutId(): self
    {
        return new self(
            'Cannot update partner: identity not set (id must be > 0)'
        );
    }

    public static function deleteNotFound(): self
    {
        return new self('Delete operation failed: partner not found');
    }
}
