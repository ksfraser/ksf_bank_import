<?php

declare(strict_types=1);

/**
 * Behaviour of the RBC Royal Bank CSV parser.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Parsers
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../parser.php';
require_once __DIR__ . '/../../../includes/banking.php';
require_once __DIR__ . '/../../../includes/GenericCsvParser.php';
require_once __DIR__ . '/../../../includes/ro_rbc_csv_parser.php';

/**
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
final class RbcCsvParserTest extends TestCase
{
    private function sample(): string
    {
        return "Account Type,Account Number,Transaction Date,Cheque Number,Description 1,Description 2,CAD$,USD$\n"
            . "Visa,4510144022764295,9/13/2026,,McDonalds 40016 AIRDRIE,,-5.24,\n"
            . "Visa,4510144022764295,9/17/2026,,PAYMENT - THANK YOU / PAI EMENT - MERCI,,22.95,\n"
            . "Visa,4510144022764295,10/4/2026,,Refund from store,,9.85,\n"
            . "Chequing,0031234567890,10/5/2026,1024,Rent payment,,,-1200.00\n";
    }

    public function testParsesTheHeaderAndEveryRow(): void
    {
        $statements = (new \ro_rbc_csv_parser())->parse($this->sample(), [], false);

        $this->assertCount(4, $statements);
        $this->assertSame('RBC', $statements['2026-09-13']->bank);
        $this->assertSame('4510144022764295', $statements['2026-09-13']->account);
        $this->assertSame('CAD', $statements['2026-09-13']->currency);
    }

    public function testCardChargesAreDebitsAndPaymentsAreBankCredits(): void
    {
        $statements = (new \ro_rbc_csv_parser())->parse($this->sample(), [], false);

        $charge = $statements['2026-09-13']->transactions[0];
        $this->assertSame('D', $charge->transactionDC);
        $this->assertSame(5.24, $charge->transactionAmount, 'Amounts are stored unsigned.');

        $payment = $statements['2026-09-17']->transactions[0];
        $this->assertSame('B', $payment->transactionDC);

        $refund = $statements['2026-10-04']->transactions[0];
        $this->assertSame('C', $refund->transactionDC);
    }

    public function testTheUsdColumnSelectsTheUsdStatement(): void
    {
        $content = "Account Type,Account Number,Transaction Date,Cheque Number,Description 1,Description 2,CAD$,USD$\n"
            . "Visa,4510144022764295,9/13/2026,,US merchant,,-5.24,\n"
            . "Visa,4510144022764295,9/13/2026,,US merchant,,,-7.50\n";

        $statements = (new \ro_rbc_csv_parser())->parse($content, [], false);

        $this->assertCount(2, $statements);
        $this->assertSame('CAD', $statements['2026-09-13-00000-0']->currency);
        $this->assertSame('USD', $statements['2026-09-13-00000-1']->currency);
    }

    public function testChequeNumbersBecomeTheTransactionCode(): void
    {
        $statements = (new \ro_rbc_csv_parser())->parse($this->sample(), [], false);
        $cheque = $statements['2026-10-05']->transactions[0];

        $this->assertSame('1024', $cheque->transactionCode);
        $this->assertSame('1024', $cheque->checknumber);
        $this->assertSame('1200', number_format($cheque->transactionAmount, 0, '.', ''));
    }

    public function testCardAccountsAreRecordedAsTheCardNumber(): void
    {
        $statements = (new \ro_rbc_csv_parser())->parse($this->sample(), [], false);

        $this->assertSame('4510144022764295', $statements['2026-09-13']->transactions[0]->cardnumber);
        $this->assertSame('', $statements['2026-10-05']->transactions[0]->cardnumber);
    }

    public function testTabDelimitedExportsParseToo(): void
    {
        $content = str_replace(',', "\t", $this->sample());

        $statements = (new \ro_rbc_csv_parser())->parse($content, [], false);

        $this->assertCount(4, $statements);
    }

    public function testTheRealExportFixtureParses(): void
    {
        $content = file_get_contents(__DIR__ . '/../../rbc_2026.csv');
        $this->assertIsString($content);

        $statements = (new \ro_rbc_csv_parser())->parse($content, [], false);

        $this->assertCount(5, $statements);
        $this->assertArrayHasKey('2026-09-13', $statements);
        $this->assertSame(5.24, $statements['2026-09-13']->transactions[0]->transactionAmount);
    }

    public function testAMissingDateColumnFailsLoudly(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/header mismatch/i');

        (new \ro_rbc_csv_parser())->parse("Account Number,Amount\n123,4.00\n", [], false);
    }

    public function testEveryStatementPassesValidation(): void
    {
        foreach ((new \ro_rbc_csv_parser())->parse($this->sample(), [], false) as $statement) {
            $this->assertTrue($statement->validate(false));
        }
    }
}
