<?php

/**
 * Unknown Partner Type
 *
 * Represents an unknown or undefined partner type in the bank import system.
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
 * Unknown Partner Type
 *
 * Fallback type for unrecognized partner types.
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
        return 999; // Lowest priority - fallback type
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Unknown or undefined partner type';
    }
}
