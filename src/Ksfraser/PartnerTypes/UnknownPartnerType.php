<?php
declare(strict_types=1);
namespace Ksfraser\PartnerTypes;

class UnknownPartnerType extends AbstractPartnerType
{
    public function getCode(): string
    {
        return 'ZZ';
    }

    public function getLabel(): string
    {
        return 'Unknown';
    }

    public function getConstantName(): string
    {
        return 'ZZ';
    }

    public function getShortCode(): string
    {
        return 'ZZ';
    }
}
