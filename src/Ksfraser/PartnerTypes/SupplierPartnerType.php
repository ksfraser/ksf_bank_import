<?php

/**
 * Supplier Partner Type
 *
 * Represents a supplier/vendor partner type in the bank import system.
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
 * Supplier Partner Type
 *
 * Used for transactions involving vendors/suppliers (accounts payable).
 */
class SupplierPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
     */
    public function getShortCode(): string
    {
        return 'SP';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Supplier';
    }

    /**
     * @inheritDoc
     */
    public function getConstantName(): string
    {
        return 'SUPPLIER';
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 10; // High priority - common type
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Vendor or supplier transactions (accounts payable)';
    }
}
