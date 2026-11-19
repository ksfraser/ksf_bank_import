<?php

declare(strict_types=1);

/**
 * Behaviour of the WMMC credit-card CSV parser.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Parsers
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../parser.php';
require_once __DIR__ . '/../../../includes/banking.php';
require_once __DIR__ . '/../../../includes/ro_wmmc_csv_parser.php';

/**
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
final class WmmcCsvParserTest extends TestCase
{
    /**
     * The 15-column export from 2024 onwards.
     *
     * @var string
     */
    private $header2024 = '"Date","Posted Date","reference number","Activity Type","status",'
        . '"Transaction card number","Merchant category","Merchant name","Merchant City",'
        . '"Merchant state/province","Merchant country","Merchant postal code/zip","Amount",'
        . '"rewards","name on card"';

    private function staticData(): array
    {
        return [
            'account' => '************2251',
            'currency' => 'CAD',
            'account_code' => '1060',
        ];
    }

    public function testParsesTheCurrentFifteenColumnExport(): void
    {
        $csv = $this->header2024 . "\n"
            . '"2024-01-08","2024-01-10","""55503804009004047182377""","TRANS","APPROVED",'
            . '"************2251","Grocery Stores and Supermarkets","WAL-MART #1050","AIRDRIE",'
            . '"AB","CAN","T4B 3G5","$56.34","","KEVIN FRASER"' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertCount(1, $statements);
        $statement = $statements['2024-01-08'];

        $this->assertSame('WMMC', $statement->bank);
        $this->assertSame('************2251', $statement->account);
        $this->assertSame('CAD', $statement->currency);
        $this->assertSame('2024-01-08-00000-0', $statement->statementId);
        $this->assertCount(1, $statement->transactions);

        $transaction = $statement->transactions[0];
        $this->assertSame(56.34, $transaction->transactionAmount);
        $this->assertSame('D', $transaction->transactionDC);
        $this->assertSame('2024-01-08', $transaction->valueTimestamp);
        $this->assertSame('2024-01-10', $transaction->entryTimestamp);
        $this->assertSame('WAL-MART #1050', $transaction->merchant);
        $this->assertSame('WAL-MART #1050', $transaction->account);
        $this->assertSame('"55503804009004047182377"', $transaction->reference);
        $this->assertSame('APPROVED', $transaction->status);
        $this->assertStringContainsString('AIRDRIE, AB;CAN T4B 3G5;', $transaction->memo);
    }

    public function testParsesTheLegacySixColumnExportWithoutOptionalColumns(): void
    {
        $csv = '"Transaction Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"' . "\n"
            . '"2024-02-01","TRANS","COSTCO WHOLESALE","Wholesale Clubs","$120.00",""' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertSame(['2024-02-01'], array_keys($statements));

        $transaction = $statements['2024-02-01']->transactions[0];
        $this->assertSame(120.0, $transaction->transactionAmount);
        $this->assertSame('COSTCO WHOLESALE', $transaction->merchant);
        $this->assertSame('', $transaction->reference);
        $this->assertSame('2024-02-01', $transaction->entryTimestamp, 'Posted date falls back to the transaction date.');
        $this->assertSame('WMMC', $statements['2024-02-01']->bank);
    }

    public function testTheMaskedCardNumberIdentifiesTheStatementWhenNoAccountIsSelected(): void
    {
        $csv = $this->header2024 . "\n"
            . '"2024-03-02","2024-03-03","ref-1","TRANS","APPROVED","************9001",'
            . '"Gas","SHELL 1234","CALGARY","AB","CAN","T2P 1A1","$40.00","","K FRASER"' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, ['currency' => 'CAD'], false);
        $statement = $statements['2024-03-02'];

        $this->assertSame('************9001', $statement->account, 'The masked card number identifies the statement.');
        $this->assertSame('SHELL 1234', $statement->transactions[0]->account);
    }

    public function testPaymentsAndRefundsAreRecognisedBySign(): void
    {
        $csv = '"Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"' . "\n"
            . '"2024-04-01","TRANS","PAYMENT - THANK YOU","Payment","-500.00",""' . "\n"
            . '"2024-04-01","TRANS","AMAZON.CA","Retail","-25.00",""' . "\n"
            . '"2024-04-01","TRANS","TIM HORTONS","Restaurants","(4.50)",""' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);
        $transactions = $statements['2024-04-01']->transactions;

        $this->assertCount(3, $transactions);
        $this->assertSame('B', $transactions[0]->transactionDC, 'A payment from the bank.');
        $this->assertSame(500.0, $transactions[0]->transactionAmount, 'Amounts are stored unsigned.');
        $this->assertSame('C', $transactions[1]->transactionDC, 'A refund is a credit.');
        $this->assertSame('C', $transactions[2]->transactionDC, 'Accounting-style parentheses mark a credit.');
        $this->assertSame(4.5, $transactions[2]->transactionAmount);
        $this->assertSame('D', (new \ro_wmmc_csv_parser())->parse(
            '"Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"' . "\n"
            . '"2024-04-01","TRANS","TIM HORTONS","Restaurants","4.50",""' . "\n",
            $this->staticData(),
            false
        )['2024-04-01']->transactions[0]->transactionDC);
    }

    public function testRaggedRowsAndWindowsLineEndingsAreTolerated(): void
    {
        $csv = "\xEF\xBB\xBF" . $this->header2024 . "\r\n"
            . '"2024-05-01","2024-05-02","ref-2","TRANS","APPROVED","************9001",'
            . '"Gas","PETRO-CANADA","EDMONTON","AB","CAN","T5J 0N3","$60.00"' . "\r\n"
            . "\r\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertCount(1, $statements);
        $this->assertSame('PETRO-CANADA', $statements['2024-05-01']->transactions[0]->merchant);
    }

    public function testMultipleDatesBecomeSeparateStatements(): void
    {
        $csv = '"Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"' . "\n"
            . '"2024-06-01","TRANS","A","Retail","1.00",""' . "\n"
            . '"2024-06-02","TRANS","B","Retail","2.00",""' . "\n"
            . '"2024-06-02","TRANS","C","Retail","3.00",""' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertSame(['2024-06-01', '2024-06-02'], array_keys($statements));
        $this->assertCount(1, $statements['2024-06-01']->transactions);
        $this->assertCount(2, $statements['2024-06-02']->transactions);
    }

    public function testRowsFromTwoCardsOnTheSameDayBecomeSeparateStatements(): void
    {
        $csv = $this->header2024 . "\n"
            . '"2024-09-01","2024-09-02","","TRANS","APPROVED","************1111",'
            . '"Gas","SHELL A","CALGARY","AB","CAN","T2P 1A1","$10.00","","K FRASER"' . "\n"
            . '"2024-09-01","2024-09-02","","TRANS","APPROVED","************2222",'
            . '"Gas","SHELL B","CALGARY","AB","CAN","T2P 1A1","$20.00","","K FRASER"' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, ['currency' => 'CAD'], false);

        $this->assertCount(2, $statements, 'Each card on the same date is its own statement.');
        $this->assertSame('2024-09-01-1111-0', $statements['2024-09-01-1111-0']->statementId);
        $this->assertSame('2024-09-01-2222-0', $statements['2024-09-01-2222-0']->statementId);
        $this->assertSame('************1111', $statements['2024-09-01-1111-0']->account);
        $this->assertSame('************2222', $statements['2024-09-01-2222-0']->account);
        $this->assertSame('SHELL A', $statements['2024-09-01-1111-0']->transactions[0]->merchant);
        $this->assertSame('SHELL B', $statements['2024-09-01-2222-0']->transactions[0]->merchant);
    }

    public function testRowsWithoutAReferenceStillGetADuplicateDetectionCode(): void
    {
        $csv = '"Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"' . "\n"
            . '"2024-10-01","TRANS","AMAZON.CA","Retail","25.00",""' . "\n"
            . '"2024-10-01","TRANS","AMAZON.CA","Retail","25.00",""' . "\n";

        $transactions = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false)['2024-10-01']->transactions;

        $this->assertNotSame('', $transactions[0]->transactionCode);
        $this->assertSame($transactions[0]->transactionCode, $transactions[1]->transactionCode,
            'Identical rows must hash to the same code so the duplicate check can find them.');
    }

    public function testDebugOutputIsSilentUnlessRequested(): void
    {
        $csv = '"Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"' . "\n"
            . '"2024-07-01","TRANS","A","Retail","1.00",""' . "\n";

        ob_start();
        (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);
        $silent = ob_get_clean();

        $this->assertSame('', $silent);
    }

    public function testAnUnrecognisedHeaderIsRejectedWithAClearError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no date or amount column');

        (new \ro_wmmc_csv_parser())->parse('"Foo","Bar"' . "\n" . '"a","b"' . "\n", $this->staticData(), false);
    }

    public function testEveryStatementPassesValidation(): void
    {
        $csv = $this->header2024 . "\n"
            . '"2024-08-01","2024-08-02","ref-3","TRANS","APPROVED","************2251",'
            . '"Grocery","WAL-MART #1050","AIRDRIE","AB","CAN","T4B 3G5","$56.34","","KEVIN FRASER"' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        foreach ($statements as $statement) {
            $this->assertTrue($statement->validate(false), 'The statement carries every field the importer needs.');
        }
    }
}
