<?php

/**
 * Matched Transaction Partner Type
 *
 * Represents a matched transaction partner type in the bank import system.
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
 * Matched Transaction Partner Type
 *
 * Used for manually matching transactions to existing GL entries.
 */
class MatchedPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
     */
    public function getShortCode(): string
    {
        return 'MA';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Matched Transaction';
    }

    /**
     * @inheritDoc
     */
    public function getConstantName(): string
    {
        return 'MATCHED';
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Manually match to existing GL entries';
    }
}
