<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Service;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\StatementAccountMappingService;

final class StatementAccountMappingServiceTest extends TestCase
{
    public function testCollectDetectedAccountsPrefersAcctid(): void
    {
        $service = new StatementAccountMappingService();

        $s1 = (object)['acctid' => 'ACCT-123', 'account' => 'SHOULD_NOT_USE'];
        $s2 = (object)['acctid' => 'ACCT-123', 'account' => 'IGNORED'];
        $s3 = (object)['account' => 'FALLBACK-999'];

        $multistatements = [
            0 => [$s1, $s2],
            1 => [$s3],
        ];

        $detected = $service->collectDetectedAccountsByFile($multistatements);

        $this->assertSame(['ACCT-123'], $detected[0]);
        $this->assertSame(['FALLBACK-999'], $detected[1]);
    }

    public function testApplyAccountNumberMappingUpdatesAccountOnly(): void
    {
        $service = new StatementAccountMappingService();

        $s1 = (object)['acctid' => 'ACCT-123', 'account' => 'ACCT-123'];
        $s2 = (object)['acctid' => 'ACCT-555', 'account' => 'ACCT-555'];

        $multistatements = [
            0 => [$s1, $s2],
        ];

        $mapped = $service->applyAccountNumberMapping($multistatements, [
            'ACCT-123' => 'FA-NUM-0001',
        ]);

        $this->assertSame('FA-NUM-0001', $mapped[0][0]->account);
        $this->assertSame('ACCT-123', $mapped[0][0]->acctid);

        // Unmapped stays unchanged
        $this->assertSame('ACCT-555', $mapped[0][1]->account);
        $this->assertSame('ACCT-555', $mapped[0][1]->acctid);
    }
}
