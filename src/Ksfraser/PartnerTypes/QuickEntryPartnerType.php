<?php

/**
 * Quick Entry Partner Type
 *
 * Represents a quick entry partner type in the bank import system.
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
 * Quick Entry Partner Type
 *
 * Used for quick entry journal transactions.
 * @BABOK FR-PROC-002, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
 */
class QuickEntryPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'QE';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        return 'Quick Entry';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'QUICK_ENTRY';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 40;
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Quick entry journal transactions';
    }
}
