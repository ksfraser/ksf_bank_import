<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * TrainingException - Thrown when training operations fail
 * 
 * Wraps errors during training data collection and processing.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class TrainingException extends PartnerException
{
    public static function databaseError(\Throwable $previous): self
    {
        return new self(
            sprintf('Training failed due to database error: %s', $previous->getMessage()),
            0,
            $previous
        );
    }

    public static function noPartnersFound(): self
    {
        return new self('Training failed: no partners found in database');
    }

    public static function invalidDryRunOption(?bool $dryRun): self
    {
        return new self(
            sprintf('Invalid dry-run option: %s', (string)$dryRun)
        );
    }
}
