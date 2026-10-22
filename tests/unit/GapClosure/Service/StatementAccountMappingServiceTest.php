<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service;

use Ksfraser\FaBankImport\Service\StatementAccountMappingService;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the statement account mapping service.
 *
 * @see \Ksfraser\FaBankImport\Service\StatementAccountMappingService
 */
final class StatementAccountMappingServiceTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheDetectedAccountsAreCollectedPerFile(): void
    {
        $service = new StatementAccountMappingService();

        $result = $service->collectDetectedAccountsByFile([
            [
                (object) ['acctid' => 'A1', 'account' => 'ignored'],
                (object) ['acctid' => 'A1', 'account' => 'ignored'],
                (object) ['acctid' => '', 'account' => 'A2'],
            ],
        ]);

        $this->assertSame([0 => ['A1', 'A2']], $result);
    }

    /**
     * @return void
     */
    public function testAStatementWithoutAnAccountIsSkipped(): void
    {
        $service = new StatementAccountMappingService();

        $result = $service->collectDetectedAccountsByFile([
            [(object) []],
        ]);

        $this->assertSame([0 => []], $result);
    }

    /**
     * @return void
     */
    public function testTheMappingIsAppliedToTheStatements(): void
    {
        $service = new StatementAccountMappingService();

        $statements = [
            [
                (object) ['acctid' => 'A1', 'account' => 'original'],
                (object) ['acctid' => 'A2', 'account' => 'untouched'],
            ],
        ];

        $result = $service->applyAccountNumberMapping($statements, ['A1' => '1234']);

        $this->assertSame('1234', $result[0][0]->account);
        $this->assertSame('untouched', $result[0][1]->account);
    }

    /**
     * @return void
     */
    public function testAStatementWithoutAnAccountIsNotMapped(): void
    {
        $service = new StatementAccountMappingService();

        $statements = [
            [(object) []],
        ];

        $result = $service->applyAccountNumberMapping($statements, ['original' => '1234']);

        $this->assertNull($result[0][0]->account ?? null);
    }

    /**
     * @return void
     */
    public function testTheAccountIdentifierIsPreferred(): void
    {
        $service = new StatementAccountMappingService();

        $this->assertSame('A1', $service->getDetectedAccount((object) ['acctid' => 'A1', 'account' => 'A2']));
        $this->assertSame('A2', $service->getDetectedAccount((object) ['acctid' => '  ', 'account' => 'A2']));
    }

    /**
     * @return void
     */
    public function testAStatementWithoutAnyAccountHasNoDetectedAccount(): void
    {
        $service = new StatementAccountMappingService();

        $this->assertNull($service->getDetectedAccount((object) []));
        $this->assertNull($service->getDetectedAccount((object) ['acctid' => '', 'account' => '']));
    }
}
