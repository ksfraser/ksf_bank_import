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
 * @BABOK FR-PROC-002, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
 */
class BankTransferPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'BT';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        return 'Bank Transfer';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'BANK_TRANSFER';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 30;
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Transfers between bank accounts';
    }
}
