<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * @BABOK RTM: FR-050 / ARCH-003; file: Exception/TrainingException.php; coverage: docs/TestCases/ (file rollup)
 * TrainingException - Thrown when training operations fail
 * 
 * Wraps errors during training data collection and processing.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class TrainingException extends PartnerException
{
    /** @BABOK FR-050 / ARCH-003 — method: databaseError */
    public static function databaseError(\Throwable $previous): self
    {
        return new self(
            sprintf('Training failed due to database error: %s', $previous->getMessage()),
            0,
            $previous
        );
    }

    /** @BABOK FR-050 / ARCH-003 — method: noPartnersFound */
    public static function noPartnersFound(): self
    {
        return new self('Training failed: no partners found in database');
    }

    /** @BABOK FR-050 / ARCH-003 — method: invalidDryRunOption */
    public static function invalidDryRunOption(?bool $dryRun): self
    {
        return new self(
            sprintf('Invalid dry-run option: %s', (string)$dryRun)
        );
    }
}
