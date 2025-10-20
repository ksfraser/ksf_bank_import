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
 */
class CustomerPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
     */
    public function getShortCode(): string
    {
        return 'CU';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Customer';
    }

    /**
     * @inheritDoc
     */
    public function getConstantName(): string
    {
        return 'CUSTOMER';
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 20; // High priority - common type
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Customer transactions (accounts receivable)';
    }
}
