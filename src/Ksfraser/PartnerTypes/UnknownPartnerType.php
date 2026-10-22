<?php

/**
 * Unknown Partner Type
 *
 * Represents an unrecognized/unclassified transaction partner type in the
 * bank import system. Sorts last (priority 999) so it never takes precedence.
 *
 * @package    Ksfraser\PartnerTypes
 * @copyright  2025 KSF
 * @license    MIT
 * @since      20260822
 */

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

/**
 * Unknown Partner Type
 *
 * Fallback for transactions that could not be classified.
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class UnknownPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
     *
     * 'UN' - distinct from the two live dispatch codes (MA = manual
     * settlement, ZZ = matched). No production switch handles this yet;
     * see PartnerTypeConstants::UNKNOWN.
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'UN';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        return 'Unknown';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'UNKNOWN';
    }

    /**
     * @inheritDoc
     *
     * Unknown sorts after every classified type so it never wins a match.
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 999;
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Unrecognized transactions requiring manual classification';
    }

    /**
     * @inheritDoc
     *
     * UNKNOWN is classification-only: no production case/switch dispatches
     * on it, so it must not appear in optype dropdowns.
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function isDispatchable(): bool
    {
        return false;
    }
}
