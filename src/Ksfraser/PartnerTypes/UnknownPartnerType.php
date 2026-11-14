<?php
declare(strict_types=1);
namespace Ksfraser\PartnerTypes;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class UnknownPartnerType extends AbstractPartnerType
{
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getCode(): string
    {
        return 'UN';
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        return 'Unknown';
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'UNKNOWN';
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'UN';
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 999;
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function isDispatchable(): bool
    {
        // Classification-only fallback: never a live dispatch option.
        return false;
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Unrecognized transactions requiring manual classification';
    }
}
