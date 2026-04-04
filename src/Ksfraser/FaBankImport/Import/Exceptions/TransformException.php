<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Exception thrown when transformation from DTO to domain entity fails
 *
 * Indicates errors such as:
 * - Cannot create entity with invalid data
 * - Missing required entity properties
 * - Type mismatch during conversion
 */
class TransformException extends ImportException
{
    /**
     * Create exception for entity creation failure
     *
     * @param string $entityType The entity type that failed to create
     * @param string $reason The reason for failure
     * @return self
     */
    public static function entityCreationFailed(string $entityType, string $reason): self
    {
        return new self(
            "Failed to create {$entityType}: {$reason}"
        );
    }

    /**
     * Create exception for type mismatch
     *
     * @param string $field The field with type mismatch
     * @param string $expectedType The expected type
     * @param string $actualType The actual type provided
     * @return self
     */
    public static function typeMismatch(string $field, string $expectedType, string $actualType): self
    {
        return new self(
            "Type mismatch for field '{$field}': expected {$expectedType}, got {$actualType}"
        );
    }

    /**
     * Create exception for missing required data
     *
     * @param array<int, string> $missingFields The missing required fields
     * @return self
     */
    public static function missingRequiredData(array $missingFields): self
    {
        return new self(
            "Missing required data for transformation: " . implode(', ', $missingFields)
        );
    }
}
