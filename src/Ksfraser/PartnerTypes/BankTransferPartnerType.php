<?php

/**
 * Bank Transfer Partner Type
 *
 * Represents a bank transfer partner type in the bank import system.
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
 * Bank Transfer Partner Type
 *
 * Used for transactions involving transfers between bank accounts.
 */
class BankTransferPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
     */
    public function getShortCode(): string
    {
        return 'BT';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Bank Transfer';
    }

    /**
     * @inheritDoc
     */
    public function getConstantName(): string
    {
        return 'BANK_TRANSFER';
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 30;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return 'Transfers between bank accounts';
    }
}
