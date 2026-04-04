<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\FaBankImport\Import\DTOs\ValidationResultDTO;
use Ksfraser\Exceptions\Utility\ValidationException;

/**
 * Contract for import data validation
 *
 * Validators check parsed data against business rules:
 * - Required fields present
 * - Data types correct
 * - Values within acceptable ranges
 * - Business rule compliance
 */
interface ValidatorInterface
{
    /**
     * Validate parsed statement data
     *
     * @param ParsedStatementDTO $statement The parsed statement
     * @return ValidationResultDTO Validation result with errors/warnings
     *
     * @throws ValidationException If validation fails
     */
    public function validate(ParsedStatementDTO $statement): ValidationResultDTO;

    /**
     * Get validator name
     *
     * @return string
     */
    public function getName(): string;
}
