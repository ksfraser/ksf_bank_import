<?php

declare(strict_types=1);

/**
 * Behaviour of the statement-fixture leak scanner and manifest gate.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Tools
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../tools/check_statement_fixtures.php';

/**
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
final class CheckStatementFixturesTest extends TestCase
{
    /**
     * Luhn accepts the classic test card and rejects a one-digit change.
     */
    public function testLuhnDetectsValidCardNumbers(): void
    {
        $this->assertTrue(sfx_luhn('4111111111111111'));
        $this->assertFalse(sfx_luhn('4111111111111112'));
        $this->assertFalse(sfx_luhn('12345'));
    }

    /**
     * Real card shapes are reported, contiguous and grouped alike.
     */
    public function testCardNumbersAreReported(): void
    {
        $text = "<ACCTID>3813446221912245<ACCTTYPE>CREDITLINE</CCACCTFROM>\n"
            . "2026-04-16,COFFEE SHOP,4111 1111 1111 1111,10.00\n";

        $findings = sfx_scan_text($text);

        $this->assertCount(2, $findings);
        $this->assertStringContainsString('3813446221912245', $findings[0]);
        $this->assertStringContainsString('4111111111111111', $findings[1]);
    }

    /**
     * OFX timestamps, FITIDs and long bank references are not card numbers.
     */
    public function testOfxMetadataAndLongReferencesAreNotReported(): void
    {
        $text = "<DTSERVER>20240223062401<INTU.BID>00005\n"
            . "<STMTTRN><DTPOSTED>20240220120000.000[-5:EST]<FITID>202405100677693<TRNAMT>-134.34</STMTTRN>\n"
            . "2026-10-19,REF,\"\"\"19922535124122142194201\"\"\",TRANS,APPROVED,10.00\n"
            . "<ACCTID>************2245</ACCTID>\n";

        $this->assertSame(array(), sfx_scan_text($text));
    }

    /**
     * Valid IBANs are flagged; scrubber-masked ones are not.
     */
    public function testValidIbanIsReported(): void
    {
        $findings = sfx_scan_text("pay to GB82WEST12345698765432 now\n");

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('GB82WEST12345698765432', $findings[0]);
        $this->assertSame(array(), sfx_scan_text("XX53XXXX3703652161365255\n"));
    }

    /**
     * Documentation e-mail placeholders stay usable in fixtures.
     */
    public function testEmailPlaceholdersAreAllowed(): void
    {
        $this->assertSame(array(), sfx_scan_text("someone@example.com\n"));
        $this->assertCount(1, sfx_scan_text("kevin@ksfraser.com\n"));
    }

    /**
     * Every tracked statement fixture is pinned and unchanged.
     */
    public function testEveryTrackedFixtureIsReviewed(): void
    {
        $root = dirname(__DIR__, 3);
        $manifest = sfx_read_manifest($root . '/tools/fixtures.sha256');

        $this->assertNotEmpty($manifest);
        $this->assertSame(array(), sfx_verify_manifest($root, $manifest));
    }
}
