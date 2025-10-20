<?php

/**
 * Partner Type Interface
 *
 * Defines the contract that all partner type implementations must follow.
 * Each partner type (Supplier, Customer, Bank Transfer, etc.) implements this interface.
 *
 * @package    Ksfraser\PartnerTypes
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 */

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

/**
 * Partner Type Interface
 *
 * Defines methods that all partner types must implement.
 *
 * Example implementation:
 * ```php
 * class SupplierPartnerType implements PartnerTypeInterface
 * {
 *     public function getShortCode(): string { return 'SP'; }
 *     public function getLabel(): string { return 'Supplier'; }
 *     public function getConstantName(): string { return 'SUPPLIER'; }
 *     public function getPriority(): int { return 100; }
 * }
 * ```
 */
interface PartnerTypeInterface
{
    /**
     * Get the two-character short code for this partner type
     *
     * Must be exactly 2 uppercase characters.
     *
     * @return string Two-character code (e.g., 'SP', 'CU', 'BT')
     */
    public function getShortCode(): string;

    /**
     * Get the human-readable label for this partner type
     *
     * Used for display in UI elements (dropdowns, labels, etc.)
     *
     * @return string Human-readable label (e.g., 'Supplier', 'Customer')
     */
    public function getLabel(): string;

    /**
     * Get the constant name for this partner type
     *
     * Used for code clarity and IDE autocomplete.
     *
     * @return string Constant name (e.g., 'SUPPLIER', 'CUSTOMER')
     */
    public function getConstantName(): string;

    /**
     * Get the sort priority for this partner type
     *
     * Lower numbers appear first in lists. Use 100 for standard priority.
     *
     * @return int Priority value (default: 100)
     */
    public function getPriority(): int;

    /**
     * Get optional description for this partner type
     *
     * Provides additional context about when to use this partner type.
     *
     * @return string|null Description or null if not available
     */
    public function getDescription(): ?string;
}
