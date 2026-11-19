<?php

declare(strict_types=1);

/**
 * Behaviour of the Manulife Bank CSV parser.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Parsers
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../parser.php';
require_once __DIR__ . '/../../../includes/banking.php';
require_once __DIR__ . '/../../../includes/GenericCsvParser.php';
require_once __DIR__ . '/../../../includes/ro_manulife_csv_parser.php';

/**
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
final class ManulifeCsvParserTest extends TestCase
{
    /**
     * The headerless four-column export.
     *
     * @return string
     */
    private function sample(): string
    {
        return '"Advantage Account 1518404",01/01/2025,131.01,"Transfer From 1524001"' . "\n"
            . '"Advantage Account 1518404",01/02/2025,-25.50,"POS SQ AIRDRIE CURLING CL SQ02W2VH"' . "\n"
            . '"Advantage Account 1518404",01/02/2025,12.00,"Interest Deposit"' . "\n";
    }

    public function testParsesTheHeaderlessExportIntoStatements(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse($this->sample(), [], false);

        $this->assertSame(['2025-01-01', '2025-01-02'], array_keys($statements));
        $this->assertCount(1, $statements['2025-01-01']->transactions);
        $this->assertCount(2, $statements['2025-01-02']->transactions);
    }

    public function testStatementDefaultsAndAccountExtraction(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse($this->sample(), [], false);
        $statement = $statements['2025-01-01'];

        $this->assertSame('Manulife Bank', $statement->bank);
        $this->assertSame('1518404', $statement->account, 'The account number is read from the row.');
        $this->assertSame('CAD', $statement->currency);
        $this->assertSame('2025-01-01-00000-0', $statement->statementId);
    }

    public function testSignedAmountsBecomeFrontAccountingDirection(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse($this->sample(), [], false);

        $credit = $statements['2025-01-01']->transactions[0];
        $this->assertSame('C', $credit->transactionDC);
        $this->assertSame(131.01, $credit->transactionAmount, 'Amounts are stored unsigned.');

        $debit = $statements['2025-01-02']->transactions[0];
        $this->assertSame('D', $debit->transactionDC);
        $this->assertSame(25.5, $debit->transactionAmount);
    }

    public function testEveryTransactionGetsADuplicateDetectionCode(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse($this->sample(), [], false);

        foreach ($statements as $statement) {
            foreach ($statement->transactions as $transaction) {
                $this->assertNotSame('', $transaction->transactionCode);
                $this->assertSame('TRF', $transaction->transactionType);
            }
        }
    }

    public function testPayeeNameIsUsedAsTheMatchingAccount(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse($this->sample(), [], false);

        $external = $statements['2025-01-02']->transactions[0];
        $this->assertSame('AIRDRIE CURLING CL', $external->account);
        $this->assertSame('AIRDRIE CURLING CL', $external->merchant);
    }

    public function testEveryStatementPassesValidation(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse($this->sample(), [], false);

        foreach ($statements as $statement) {
            $this->assertTrue($statement->validate(false));
        }
    }

    public function testAStaticAccountOverridesTheRowAccount(): void
    {
        $statements = (new \ro_manulife_csv_parser())->parse(
            $this->sample(),
            ['account' => '1060', 'currency' => 'CAD', 'bank_name' => 'Manulife'],
            false
        );

        $this->assertSame('1060', $statements['2025-01-02']->account);
    }
}
