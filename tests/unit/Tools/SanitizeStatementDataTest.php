<?php

declare(strict_types=1);

/**
 * Behaviour of the statement-data sanitizer.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Tools
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../tools/sanitize_statement_data.php';

/**
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
final class SanitizeStatementDataTest extends TestCase
{
    /**
     * A CSV line keeps its statement data and loses its identity.
     */
    public function testCsvKeepsStatementDataAndReplacesIdentity(): void
    {
        $csv = "\"Date\",\"Posted Date\",\"Reference number\",\"Card number\",\"Merchant name\","
            . "\"Merchant city\",\"Merchant State or Province\",\"Merchant postal code\",\"Amount\","
            . "\"Name on Card\"\n"
            . "2026-04-16,2026-04-18,\"\"\"26296000537600369326296\"\"\",************2251,"
            . "\"WAL-MART NORTH\",\"AIRDRIE\",AB,\"T4B 2P9\",\$37.73,\"KEVIN FRASER\"\n";

        $out = sd_sanitize_csv($csv);

        $this->assertStringContainsString('2026-04-16', $out);
        $this->assertStringContainsString('2026-04-18', $out);
        $this->assertStringContainsString('$37.73', $out);
        $this->assertStringContainsString('************', $out);

        $this->assertStringNotContainsString('WAL-MART', $out);
        $this->assertStringNotContainsString('AIRDRIE', $out);
        $this->assertStringNotContainsString('KEVIN FRASER', $out);
        $this->assertStringNotContainsString('T4B 2P9', $out);
        $this->assertStringNotContainsString('26296000537600369326296', $out);
    }

    /**
     * The scrub is deterministic and value-stable.
     */
    public function testTheSameValueMapsToTheSameReplacement(): void
    {
        $csv = "\"Date\",\"Merchant name\",\"Amount\"\n"
            . "2026-04-16,\"WAL-MART NORTH\",\$1.00\n"
            . "2026-04-17,\"WAL-MART NORTH\",\$2.00\n";

        $out = sd_sanitize_csv($csv);
        $lines = explode("\n", trim($out));

        $first = str_getcsv($lines[1]);
        $second = str_getcsv($lines[2]);

        $this->assertSame($first[1], $second[1]);
        $this->assertSame($out, sd_sanitize_csv($csv));
    }

    /**
     * The RBC export keeps amounts and dates but not the card number.
     */
    public function testRbcExportIsScrubbed(): void
    {
        $csv = "Account Type,Account Number,Transaction Date,Cheque Number,"
            . "Description 1,Description 2,CAD$,USD$\n"
            . "Visa,4510144022764295,9/13/2026,,McDonalds 40016 AIRDRIE,,-5.24,\n";

        $out = sd_sanitize_csv($csv);

        $this->assertStringContainsString('Account Type,Account Number', $out);
        $this->assertStringContainsString('9/13/2026', $out);
        $this->assertStringContainsString('-5.24', $out);
        $this->assertStringNotContainsString('4510144022764295', $out);
        $this->assertStringNotContainsString('McDonalds', $out);
        $this->assertStringNotContainsString('AIRDRIE', $out);
    }

    /**
     * Headerless Manulife exports are recognised from their date column and
     * their account label keeps its wording.
     */
    public function testHeaderlessManulifeExportIsScrubbed(): void
    {
        $csv = "\"Advantage Account 1524001\",09/11/2026,1113.53,\"PAY The Travel Corp\"\n"
            . "\"Advantage Account 1524001\",09/11/2026,-285.00,\"Transfer to 1518404\"\n";

        $out = sd_sanitize_csv($csv);

        $this->assertStringNotContainsString('1524001', $out);
        $this->assertStringNotContainsString('The Travel Corp', $out);
        $this->assertStringContainsString('Advantage Account', $out);
        $this->assertStringContainsString('09/11/2026', $out);
        $this->assertStringContainsString('1113.53', $out);
        $this->assertStringContainsString('-285.00', $out);
    }

    /**
     * A tab-separated export stays tab-separated.
     */
    public function testTabDelimitedFilesKeepTheirDelimiter(): void
    {
        $csv = "Account Type\tAccount Number\tTransaction Date\tCheque Number\t"
            . "Description 1\tDescription 2\tCAD$\tUSD$\n"
            . "Visa\t4510144022764295\t9/13/2026\t\tMcDonalds 40016 AIRDRIE\t\t-5.24\t\n";

        $out = sd_sanitize_csv($csv);

        $this->assertStringContainsString("Visa\t", $out);
        $rows = explode("\n", trim($out));
        $this->assertSame(7, substr_count($rows[0], "\t"));
        $this->assertStringContainsString("\t", $rows[1], 'Tabs survive on the data row.');
        $this->assertStringNotContainsString(',', $rows[1], 'The row is not rewritten as comma-separated.');
        $this->assertStringNotContainsString('4510144022764295', $out);
    }

    /**
     * QFX/OFX tags lose their identifying content, amounts and dates survive.
     */
    public function testQfxReplacesNamesAndAccounts(): void
    {
        $qfx = "OFXHEADER:100\n<OFX><BANKMSGSRSV1><STMTRS>\n"
            . "<CURDEF>CAD</CURDEF>\n<BANKACCTFROM><BANKID>0543<ACCTID>543-997******6030</BANKACCTFROM>\n"
            . "<CITY>TORONTO</CITY><STATE>ON</STATE><POSTALCODE>T2P 2P2</POSTALCODE>\n"
            . "<STMTTRN><TRNTYPE>DEBIT<DTPOSTED>20260416<TRNAMT>-37.73\n"
            . "<NAME>LCBO #123 TORONTO<MEMO>INTERAC PURCHASE\n</STMTTRN></STMTRS></BANKMSGSRSV1></OFX>\n";

        $out = sd_sanitize_markup($qfx);

        $this->assertStringContainsString('<CURDEF>CAD</CURDEF>', $out);
        $this->assertStringContainsString('<DTPOSTED>20260416', $out);
        $this->assertStringContainsString('<TRNAMT>-37.73', $out);

        $this->assertStringNotContainsString('LCBO', $out);
        $this->assertStringNotContainsString('TORONTO', $out);
        $this->assertStringNotContainsString('T2P 2P2', $out);
        $this->assertStringNotContainsString('543-997******6030', $out);
        $this->assertStringNotContainsString('INTERAC', $out);
    }
}
