<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004

 * TrainingException - Thrown when training operations fail
 * 
 * Wraps errors during training data collection and processing.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class TrainingException extends PartnerException
{
    /** * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
*/
    public static function databaseError(\Throwable $previous): self
    {
        return new self(
            sprintf('Training failed due to database error: %s', $previous->getMessage()),
            0,
            $previous
        );
    }

    /** * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
*/
    public static function noPartnersFound(): self
    {
        return new self('Training failed: no partners found in database');
    }

    /** * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
*/
    public static function invalidDryRunOption(?bool $dryRun): self
    {
        return new self(
            sprintf('Invalid dry-run option: %s', (string)$dryRun)
        );
    }
}
