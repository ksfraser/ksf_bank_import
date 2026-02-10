<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Service;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey;

final class DetectedAccountAssociationKeyTest extends TestCase
{
    public function testKeyIsStableAndBounded(): void
    {
        $detected = '4503 3000-1618 0307';
        $k1 = DetectedAccountAssociationKey::forDetectedAccount($detected);
        $k2 = DetectedAccountAssociationKey::forDetectedAccount($detected);

        $this->assertSame($k1, $k2);
        $this->assertLessThanOrEqual(100, strlen($k1));
        $this->assertStringStartsWith('acct_assoc.', $k1);
    }
}
