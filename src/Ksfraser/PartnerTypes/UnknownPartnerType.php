<?php

/**
 * Unknown Partner Type
 *
 * Represents an unknown or unprocessed transaction partner type.
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
 * Used for transactions with unknown or unresolved partner types.
 * Short code 'ZZ' is used to represent unknown/catch-all transactions.
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
        return 'Unknown transaction type';
    }

    /**
     * @inheritDoc
     */
    public function getStrategyMethodName(): string
    {
        return 'displayUnknown';
    }
}
