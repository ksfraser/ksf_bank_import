<?php

namespace Ksfraser\FaBankImport\Import\Exceptions;

/**
 * Thrown when a transaction processor strategy cannot be created.
 * 
 * Examples:
 * - Unknown partner type
 * - Processor class not found
 * - Processor factory misconfigured
 */
class ProcessorNotFoundException extends ImportException
{
    protected bool $recoverable = true;

    /**
     * Create exception for unknown partner type.
     *
     * @param string $partnerType
     * @param array $supportedTypes
     * @return self
     */
    public static function unknownPartnerType(string $partnerType, array $supportedTypes): self
    {
        return new self(
            "Unknown transaction processor for partner type '{$partnerType}'. Supported: " . implode(', ', $supportedTypes),
            9001,
            null,
            ['partner_type' => $partnerType, 'supported_types' => $supportedTypes]
        );
    }

    /**
     * Create exception for missing processor class.
     *
     * @param string $className
     * @return self
     */
    public static function classNotFound(string $className): self
    {
        return new self(
            "Processor class not found: {$className}",
            9002,
            null,
            ['class_name' => $className]
        );
    }

    /**
     * Create exception for processor instantiation failure.
     *
     * @param string $className
     * @param string $error
     * @return self
     */
    public static function instantiationFailed(string $className, string $error): self
    {
        return new self(
            "Failed to instantiate processor {$className}: {$error}",
            9003,
            null,
            ['class_name' => $className, 'error' => $error],
            false
        );
    }

    /**
     * Create exception for invalid processor interface.
     *
     * @param string $className
     * @return self
     */
    public static function invalidInterface(string $className): self
    {
        return new self(
            "Processor {$className} does not implement required interface",
            9004,
            null,
            ['class_name' => $className]
        );
    }
}
