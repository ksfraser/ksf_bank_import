<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when contact processing encounters issues.
 * 
 * Examples:
 * - No matching contact found
 * - Contact data is malformed
 * - Contact creation fails
 */
class ContactImportException extends ImportException
{
    protected bool $recoverable = true;

    /**
     * Create exception for no matching contact.
     *
     * @param string $criteria Description of search criteria
     * @return self
     */
    public static function noMatch(string $criteria): self
    {
        return new self(
            "No matching contact found: {$criteria}",
            6001,
            null,
            ['criteria' => $criteria]
        );
    }

    /**
     * Create exception for malformed contact data.
     *
     * @param array $contactData
     * @param string $reason
     * @return self
     */
    public static function malformedData(array $contactData, string $reason): self
    {
        return new self(
            "Malformed contact data: {$reason}",
            6002,
            null,
            ['contact_data' => $contactData, 'reason' => $reason]
        );
    }

    /**
     * Create exception for duplicate contact scenario.
     *
     * @param string $name
     * @param array $duplicateIds
     * @return self
     */
    public static function duplicateFound(string $name, array $duplicateIds): self
    {
        return new self(
            "Multiple contacts found for '{$name}': " . implode(', ', $duplicateIds),
            6003,
            null,
            ['name' => $name, 'duplicate_ids' => $duplicateIds]
        );
    }

    /**
     * Create exception for contact creation failure.
     *
     * @param string $name
     * @param string $error
     * @return self
     */
    public static function creationFailed(string $name, string $error): self
    {
        return new self(
            "Failed to create contact '{$name}': {$error}",
            6004,
            null,
            ['name' => $name, 'error' => $error],
            false
        );
    }
}
