<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\TransformException;

/**
 * Contract for transforming DTOs to domain entities
 *
 * Transforms parsed statement data into domain entities:
 * - ParsedStatementDTO → BiStatement + BiTransaction + BiLineItem
 * - Handles type coercion, mapping, entity creation
 * - Validates entity constraints
 */
interface TransformerInterface
{
    /**
     * Transform parsed statement into domain entities
     *
     * @param ParsedStatementDTO $statement Parsed statement DTO
     * @return array<int, mixed> Array of created domain entities
     *
     * @throws TransformException If transformation fails
     */
    public function transform(ParsedStatementDTO $statement): array;

    /**
     * Get transformer name
     *
     * @return string
     */
    public function getName(): string;
}
