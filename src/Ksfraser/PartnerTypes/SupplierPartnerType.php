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
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
 */
class SupplierPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'SP';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        return 'Supplier';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'SUPPLIER';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 10; // High priority - common type
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Vendor or supplier transactions (accounts payable)';
    }
}
