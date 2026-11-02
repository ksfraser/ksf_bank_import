<?php

/**
 * Customer Partner Type
 *
 * Represents a customer partner type in the bank import system.
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
 * Customer Partner Type
 *
 * Used for transactions involving customers (accounts receivable).
 * @BABOK FR-PROC-002, FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
 */
class CustomerPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'CU';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        return 'Customer';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'CUSTOMER';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 20; // High priority - common type
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Customer transactions (accounts receivable)';
    }
}
