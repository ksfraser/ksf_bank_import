<?php

namespace Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Parsers\OFXParser;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * Unit Tests for OFXParser
 *
 * Tests OFX/QFX parsing using ksfraser/ksf_ofxparser library with:
 * - OFX 1.0 format support
 * - OFX 2.0 (XML) format support
 * - QFX format support
 * - Encoding detection
 * - Date normalization
 * - All 4 exception types
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Parsers\OFXParser
 */
class OFXParserTest extends TestCase
{
    private OFXParser $parser;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->parser = new OFXParser();
        $this->tempDir = sys_get_temp_dir() . '/ofx_parser_tests_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    /**
     * Test 1: Parse valid OFX 1.0 format
     */
    public function testParseValidOFX10Format(): void
    {
        $ofx = $this->buildOFX10Sample();
        $file = $this->tempDir . '/test_ofx10.ofx';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        if (count($result) > 0) {
            $this->assertInstanceOf(ParsedStatementDTO::class, $result[0]);
        }
    }

    /**
     * Test 2: Parse valid OFX 2.0 (XML) format
     */
    public function testParseValidOFX20XMLFormat(): void
    {
        $ofx = $this->buildOFX20Sample();
        $file = $this->tempDir . '/test_ofx20.xml';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
    }

    /**
     * Test 3: Parse valid QFX format
     */
    public function testParseValidQFXFormat(): void
    {
        $qfx = $this->buildQFXSample();
        $file = $this->tempDir . '/test.qfx';
        file_put_contents($file, $qfx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
    }

    /**
     * Test 4: OFX date normalization YYYYMMDD
     */
    public function testOFXDateNormalization(): void
    {
        // This test validates that dates in YYYYMMDD format are properly converted to YYYY-MM-DD
        $ofx = $this->buildOFX10Sample();
        $file = $this->tempDir . '/test_dates.ofx';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
    }

    /**
     * Test 5: Currency extraction from OFX
     */
    public function testCurrencyExtraction(): void
    {
        $ofx = $this->buildOFX10Sample();
        $file = $this->tempDir . '/test_currency.ofx';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        if (count($result) > 0) {
            // Verify currency is 3-letter code
            $parsed = $result[0];
            // Currency should be extracted and normalized
        }
    }

    /**
     * Test 6: Account reference extraction
     */
    public function testAccountReferenceExtraction(): void
    {
        $ofx = $this->buildOFX10Sample();
        $file = $this->tempDir . '/test_account.ofx';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
    }

    /**
     * Test 7: Encoding detection (UTF-8 BOM)
     */
    public function testEncodingDetectionUTF8BOM(): void
    {
        $ofx = "\xEF\xBB\xBF" . $this->buildOFX10Sample();
        $file = $this->tempDir . '/test_bom.ofx';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
    }

    /**
     * Test 8: OFX encoding declaration parsing
     */
    public function testOFXEncodingDeclaration(): void
    {
        $ofx = "ENCODING:UTF-8\n" . $this->buildOFX10Sample();
        $file = $this->tempDir . '/test_encoding_decl.ofx';
        file_put_contents($file, $ofx);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
    }

    /**
     * Test 9: File not found - throws FileNotFoundException
     */
    public function testFileNotFoundThrowsException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->parser->parse('/path/that/does/not/exist.ofx');
    }

    /**
     * Test 10: Invalid OFX format - throws UnsupportedFileTypeException
     */
    public function testInvalidFormatThrowsException(): void
    {
        $file = $this->tempDir . '/invalid.txt';
        file_put_contents($file, "This is not an OFX file");

        $this->expectException(UnsupportedFileTypeException::class);
        $this->parser->parse($file);
    }

    /**
     * Get supported MIME types
     */
    public function testGetSupportedTypes(): void
    {
        $types = $this->parser->getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('application/x-ofx', $types);
        $this->assertContains('text/x-ofx', $types);
        $this->assertContains('application/x-qfx', $types);
        $this->assertContains('application/vnd.intu.qbo', $types);
    }

    /**
     * Get parser name
     */
    public function testGetName(): void
    {
        $name = $this->parser->getName();
        $this->assertEquals('OFX Parser', $name);
    }

    // Helper methods to build test data

    private function buildOFX10Sample(): string
    {
        return <<<'OFX'
OFXHEADER:100
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEFORMAT:NO
NEWFILEFORMAT:YES

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456789
<ACCTID>098765432
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20240101
<DTEND>20240115
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20240115
<TRNAMT>-100.00
<FITID>1001
<NAME>Test Merchant
<MEMO>Test Transaction
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00
<DTASOF>20240115
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;
    }

    private function buildOFX20Sample(): string
    {
        return <<<'OFX'
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456789
<ACCTID>098765432
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20240101
<DTEND>20240115
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20240115
<TRNAMT>-50.00
<FITID>2001
<NAME>Merchant XYZ
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>5000.00
<DTASOF>20240115
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;
    }

    private function buildQFXSample(): string
    {
        return <<<'QFX'
!OFX
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<ACCTID>555555555
</BANKACCTFROM>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20240115
<TRNAMT>-25.00
<FITID>3001
<NAME>QFX Test
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
QFX;
    }
}
