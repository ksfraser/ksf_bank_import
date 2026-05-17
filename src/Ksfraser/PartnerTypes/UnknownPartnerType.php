<?php

/**
 * Unknown Partner Type
 *
 * Represents an unknown or unclassified partner type in the bank import system.
 *
 * @package    Ksfraser\PartnerTypes
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251120
 */

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

/**
 * Unknown Partner Type
 *
 * Used for transactions with an unclassified or unknown partner type.
 * Typically has the lowest priority and is a fallback classification.
 */
class UnknownPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
     */
    public function getShortCode(): string
    {
        return 'ZZ';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Unknown';
    }

    /**
     * @inheritDoc
     */
    public function getConstantName(): string
    {
        return 'UNKNOWN';
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 999;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Unknown or unclassified transactions';
    }

    /**
     * @inheritDoc
     */
    public function isUnknown(): bool
    {
        return true;
    }
}
