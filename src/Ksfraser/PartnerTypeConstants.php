<?php

/**
 * Partner Type Constants (Backward Compatibility Facade)
 *
 * Provides backward-compatible constants and delegates to the dynamic PartnerTypeRegistry.
 * New code should use PartnerTypeRegistry directly for full flexibility.
 *
 * @package    Ksfraser
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 * @deprecated Use PartnerTypeRegistry for dynamic partner type management
 */

declare(strict_types=1);

namespace Ksfraser;

use Ksfraser\PartnerTypes\PartnerTypeRegistry;

/**
 * Partner Type Constants (Facade)
 *
 * Backward-compatible facade for existing code.
 * Delegates to PartnerTypeRegistry for dynamic type management.
 *
 * Example usage:
 * ```php
 * // Old way (still works)
 * if ($partnerType === PartnerTypeConstants::SUPPLIER) { ... }
 *
 * // New way (recommended)
 * $registry = PartnerTypeRegistry::getInstance();
 * if ($registry->isValid($partnerType)) { ... }
 * ```
 */
final class PartnerTypeConstants
{
    /**
     * Supplier/Vendor partner type
     */
    public const SUPPLIER = 'SP';

    /**
     * Customer partner type
     */
    public const CUSTOMER = 'CU';

    /**
     * Bank transfer partner type
     */
    public const BANK_TRANSFER = 'BT';

    /**
     * Quick entry partner type
     */
    public const QUICK_ENTRY = 'QE';

    /**
     * Manual settlement partner type
     */
    public const MANUAL_SETTLEMENT = 'MA';

    /**
     * Matched transaction partner type
     */
    public const MATCHED = 'ZZ';

    /**
     * Prevent instantiation of this constants class
     */
    private function __construct()
    {
    }

    /**
     * Get all partner type constants as short code => label array
     *
     * Returns an array suitable for use in dropdowns and form selectors.
     * Format: ['SP' => 'Supplier', 'CU' => 'Customer', ...]
     *
     * Delegates to PartnerTypeRegistry for dynamic discovery.
     *
     * @return array<string, string> Array of short codes and human-readable labels
     */
    public static function getAll(): array
    {
        $registry = PartnerTypeRegistry::getInstance();
        $result = [];
        
        // Build array with short codes as keys and labels as values
        foreach ($registry->getAll() as $type) {
            $result[$type->getShortCode()] = $type->getLabel();
        }
        
        return $result;
    }

    /**
     * Check if a partner type is valid
     *
     * Delegates to PartnerTypeRegistry.
     *
     * @param string $type The partner type to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $type): bool
    {
        return PartnerTypeRegistry::getInstance()->isValid($type);
    }

    /**
     * Get human-readable label for partner type
     *
     * Delegates to PartnerTypeRegistry.
     *
     * @param string $type The partner type constant
     * @return string Human-readable label
     */
    public static function getLabel(string $type): string
    {
        return PartnerTypeRegistry::getInstance()->getLabel($type);
    }

    /**
     * Get the registry instance
     *
     * Provides access to the full registry API for advanced usage.
     *
     * @return PartnerTypeRegistry
     */
    public static function getRegistry(): PartnerTypeRegistry
    {
        return PartnerTypeRegistry::getInstance();
    }

    /**
     * Get partner type short code by constant name
     *
     * Allows handlers to get their short code by referencing a constant.
     * This centralizes the code mapping for easier maintenance.
     *
     * Example usage:
     * ```php
     * $code = PartnerTypeConstants::getCodeByConstant('SUPPLIER'); // Returns 'SP'
     * ```
     *
     * @param string $constantName The constant name (e.g., 'SUPPLIER', 'CUSTOMER')
     * @return string The two-character short code
     * @throws \InvalidArgumentException If constant name not found
     */
    public static function getCodeByConstant(string $constantName): string
    {
        $type = PartnerTypeRegistry::getInstance()->getByConstant($constantName);
        
        if ($type === null) {
            throw new \InvalidArgumentException(
                sprintf('Partner type constant "%s" not found', $constantName)
            );
        }
        
        return $type->getShortCode();
    }
}
