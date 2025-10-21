<?php

/**
 * Matched Partner Type
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
 * Matched Partner Type
 *
 * Used for transactions that have been matched.
 */
class MatchedPartnerType extends AbstractPartnerType
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
        // Updated to match legacy process_statements.php label for backward compatibility
        return 'Matched';
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
        return 60;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Matched transactions';
    }
}
