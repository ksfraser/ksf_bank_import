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

    public function testThe2023HeaderSpellingsMapToTheSameColumns(): void
    {
        $csv = '"Date","Posted Date","Reference number","Activity Type","Status","Card number",'
            . '"Merchant Category","Merchant name","Merchant city","Merchant State or Province",'
            . '"Merchant country","Merchant postal code","Amount","Rewards","Name on Card"' . "\n"
            . '"2023-04-16","2023-04-18","ref-9","TRANS","APPROVED","************2251",'
            . '"Grocery","WAL-MART #1050","AIRDRIE","AB","CAN","T4B 3G5","$37.73","","KEVIN FRASER"' . "\n";

        $transaction = (new \ro_wmmc_csv_parser())
            ->parse($csv, $this->staticData(), false)['2023-04-16']->transactions[0];

        $this->assertSame('AB', $transaction->transactionTitle3, 'The 2023 "Merchant State or Province" header is recognised.');
        $this->assertSame('T4B 3G5', trim($transaction->transactionTitle5), 'The 2023 "Merchant postal code" header is recognised.');
        $this->assertSame('CAN', $transaction->transactionTitle4);
    }

    public function testThe2026HeaderKeepsTheTransactionDateAsTheStatementId(): void
    {
        $csv = '"Transaction Date","Posted Date","Reference Number","Activity Type","Status",'
            . '"Card Number","Merchant Category","Merchant Name","Merchant City",'
            . '"Merchant State/Province","Merchant Country","Merchant Zip","Amount","Rewards","Name on Card"' . "\n"
            . '"2026-10-22","2026-10-23","""26296000537600369326296""","TRANS","APPROVED",'
            . '"************9895","","PAYMENT - THANK YOU","","","","","-$125.89","","MARCIA LIN FRASER"' . "\n"
            . '"2026-10-19","2026-10-20","""55503806293004001147432""","TRANS","APPROVED",'
            . '"************9895","Grocery Stores and Supermarkets","WAL-MART #1050","AIRDRIE",'
            . '"AB","CAN","T4B 3G5","$126.49","","MARCIA LIN FRASER"' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertSame(['2026-10-22', '2026-10-19'], array_keys($statements),
            'The statement key and id come from "Transaction Date", not "Posted Date".');
        $this->assertSame('2026-10-22-00000-0', $statements['2026-10-22']->statementId);

        $payment = $statements['2026-10-22']->transactions[0];
        $this->assertSame('B', $payment->transactionDC, 'A negative payment is money coming back.');
        $this->assertSame(125.89, $payment->transactionAmount);
        $this->assertSame('2026-10-23', $payment->entryTimestamp, 'The posted date is the entry timestamp.');

        $purchase = $statements['2026-10-19']->transactions[0];
        $this->assertSame('T4B 3G5', trim($purchase->transactionTitle5), 'The 2026 "Merchant Zip" header is recognised.');
        $this->assertSame('AB', $purchase->transactionTitle3);
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
        $this->expectException(\WmmcColumnHeaderMismatchException::class);
        $this->expectExceptionMessage('missing required column(s): date, amount, merchant');

        (new \ro_wmmc_csv_parser())->parse('"Foo","Bar"' . "\n" . '"a","b"' . "\n", $this->staticData(), false);
    }

    public function testAHeaderMismatchReportsWhatItActuallySaw(): void
    {
        try {
            (new \ro_wmmc_csv_parser())->parse('"Foo","Bar"' . "\n" . '"a","b"' . "\n", $this->staticData(), false);
            $this->fail('A header with no recognizable columns must be rejected.');
        } catch (\WmmcColumnHeaderMismatchException $e) {
            $this->assertSame(['date', 'amount', 'merchant'], $e->getMissingColumns());
            $this->assertSame(['Foo', 'Bar'], $e->getSeenHeaders());
            $this->assertStringContainsString('Headers seen: Foo | Bar', $e->getMessage());
        }
    }

    public function testUnknownSpellingsAreResolvedFromSynonyms(): void
    {
        $csv = '"Value Date","Description","Debit","Rewards"' . "\n"
            . '"2026-01-05","CORNER STORE","25.00",""' . "\n";

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertSame(['2026-01-05'], array_keys($statements));
        $this->assertSame('CORNER STORE', $statements['2026-01-05']->transactions[0]->merchant);
        $this->assertSame(25.0, $statements['2026-01-05']->transactions[0]->transactionAmount);
    }

    public function testAPostingDateIsNotAcceptedAsTheTransactionDate(): void
    {
        $this->expectException(\WmmcColumnHeaderMismatchException::class);
        $this->expectExceptionMessage('missing required column(s): date');

        (new \ro_wmmc_csv_parser())->parse(
            '"Posting Date","Merchant Name","Amount"' . "\n" . '"2026-01-05","STORE","25.00"' . "\n",
            $this->staticData(),
            false
        );
    }

    public function testTodaysExportParsesFromTheSanitizedFixture(): void
    {
        $csv = (string) file_get_contents(__DIR__ . '/../../wmmc_2026.csv');

        $statements = (new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false);

        $this->assertNotEmpty($statements);

        $dates = array();
        foreach ($statements as $statement) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}-00000-0$/', $statement->statementId);
            $this->assertSame('CAD', $statement->currency);
            $this->assertNotEmpty($statement->transactions);
            $dates[$statement->timestamp] = true;

            foreach ($statement->transactions as $transaction) {
                $this->assertNotSame('', $transaction->merchant);
                $this->assertSame($transaction->merchant, $transaction->transactionTitle1);
                $this->assertContains($transaction->transactionDC, array('D', 'C', 'B'));
                $this->assertGreaterThan(0, $transaction->transactionAmount);
                $this->assertNotSame('', $transaction->transactionCode);
            }
        }

        // The 2026 header carries "Transaction Date", which must be the
        // statement date, not the posting date.
        $this->assertArrayHasKey('2026-10-22', $dates);
        $this->assertArrayNotHasKey('2026-10-23', $dates);
    }

    public function testMerchantDetailsAreCarriedOntoTheTransaction(): void
    {
        $csv = (string) file_get_contents(__DIR__ . '/../../wmmc_2026.csv');

        $found = null;
        foreach ((new \ro_wmmc_csv_parser())->parse($csv, $this->staticData(), false) as $statement) {
            foreach ($statement->transactions as $transaction) {
                if ($transaction->city !== '') {
                    $found = $transaction;
                    break 2;
                }
            }
        }

        $this->assertNotNull($found, 'The export carries a merchant with a city.');

        $this->assertSame('SAMPLE TOWN', $found->city);
        $this->assertSame('ON', $found->province);
        $this->assertSame('CAN', $found->country);
        $this->assertMatchesRegularExpression('/^X\dX \dX\d$/', $found->postalcode);
        $this->assertStringContainsString($found->postalcode, $found->address, 'The postal code is part of the address.');
        $this->assertNotSame('', $found->cardnumber);
        $this->assertNotSame('', $found->nameoncard);
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
