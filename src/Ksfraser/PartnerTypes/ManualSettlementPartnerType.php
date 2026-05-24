<?php

/**
 * Manual Settlement Partner Type
 *
 * Represents a manual settlement partner type in the bank import system.
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
 * Manual Settlement Partner Type
 *
 * Used for manually settling transactions to existing GL entries.
 */
class ManualSettlementPartnerType extends AbstractPartnerType
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
        // Updated to match new design: MA = Matched Transaction
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
    
    /**
     * @inheritDoc
     * 
     * Manual Settlement uses displayManualSettlement method
     */
    public function getStrategyMethodName(): string
    {
        return 'displayManualSettlement';
    }
}
