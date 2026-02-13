<?php

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

final class UnknownPartnerType extends AbstractPartnerType
{
    public function getShortCode(): string
    {
        return 'ZZ';
    }

    public function getLabel(): string
    {
        return 'Unknown';
    }

    public function getConstantName(): string
    {
        return 'UNKNOWN';
    }

    public function getPriority(): int
    {
        return 999;
    }

    public function getDescription(): ?string
    {
        return 'Fallback partner type when no specific match applies.';
    }
}
