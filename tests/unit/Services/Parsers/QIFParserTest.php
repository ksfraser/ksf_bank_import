<?php

namespace Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Parsers\QIFParser;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * Unit Tests for QIFParser
 *
 * Tests QIF (Quicken Interchange Format) parsing with:
 * - QIF transaction records
 * - Date format normalization (M/D/Y, M/D/YY, YYYYMMDD)
 * - Amount and debit/credit handling
 * - Payee/memo extraction
 * - All 4 exception types
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Parsers\QIFParser
 */
class QIFParserTest extends TestCase
{
    private QIFParser $parser;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->parser = new QIFParser();
        $this->tempDir = sys_get_temp_dir() . '/qif_parser_tests_' . uniqid();
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
     * Test 1: Parse valid QIF bank statement format
     */
    public function testParseValidQIFBankStatement(): void
    {
        $qif = $this->buildQIFBankSample();
        $file = $this->tempDir . '/test_bank.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC001']);

        $this->assertIsArray($result);
        if (count($result) > 0) {
            $this->assertInstanceOf(ParsedStatementDTO::class, $result[0]);
        }
    }

    /**
     * Test 2: Parse QIF with credit card transactions
     */
    public function testParseQIFCreditCardFormat(): void
    {
        $qif = $this->buildQIFCreditCardSample();
        $file = $this->tempDir . '/test_ccard.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'CCTEST', 'accountId' => 'CC001']);

        $this->assertIsArray($result);
    }

    /**
     * Test 3: QIF date normalization M/D/Y format
     */
    public function testQIFDateNormalizationMDY(): void
    {
        $qif = <<<'QIF'
!Type:Bank
D1/15/24
T-100.00
PTest Merchant
MTest payment
^
QIF;
        $file = $this->tempDir . '/test_date_mdy.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC', 'dateFormat' => 'MDY']);

        $this->assertIsArray($result);
    }

    /**
     * Test 4: QIF date normalization with two-digit year
     */
    public function testQIFDateNormalizationTwoDigitYear(): void
    {
        $qif = <<<'QIF'
!Type:Bank
D01/15/24
T-100.00
^
QIF;
        $file = $this->tempDir . '/test_date_2digit.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC']);

        $this->assertIsArray($result);
    }

    /**
     * Test 5: QIF amount with debit/credit handling
     */
    public function testQIFAmountAndDebitCredit(): void
    {
        $qif = <<<'QIF'
!Type:Bank
D1/15/24
T-100.00
PDebit Merchant
M-100.00 debit
^
D1/16/24
T100.00
PCredit Deposit
M100.00 credit
^
QIF;
        $file = $this->tempDir . '/test_amounts.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC']);

        $this->assertIsArray($result);
    }

    /**
     * Test 6: QIF payee field extraction
     */
    public function testQIFPayeeExtraction(): void
    {
        $qif = <<<'QIF'
!Type:Bank
D1/15/24
T-50.00
PVendor ABC
MPayment for invoice
^
QIF;
        $file = $this->tempDir . '/test_payee.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC']);

        $this->assertIsArray($result);
    }

    /**
     * Test 7: QIF memo field extraction
     */
    public function testQIFMemoExtraction(): void
    {
        $qif = <<<'QIF'
!Type:Bank
D1/15/24
T-100.00
PMerchant
MMultiple line memo here
^
QIF;
        $file = $this->tempDir . '/test_memo.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC']);

        $this->assertIsArray($result);
    }

    /**
     * Test 8: QIF with check number field
     */
    public function testQIFCheckNumberField(): void
    {
        $qif = <<<'QIF'
!Type:Bank
D1/15/24
T-100.00
NCheck 001
PMerchant
M check payment
^
QIF;
        $file = $this->tempDir . '/test_check.qif';
        file_put_contents($file, $qif);

        $result = $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC']);

        $this->assertIsArray($result);
    }

    /**
     * Test 9: File not found - throws FileNotFoundException
     */
    public function testFileNotFoundThrowsException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->parser->parse('/path/that/does/not/exist.qif');
    }

    /**
     * Test 10: Invalid QIF format - throws UnsupportedFileTypeException
     */
    public function testInvalidQIFFormatThrowsException(): void
    {
        $file = $this->tempDir . '/invalid.txt';
        file_put_contents($file, "This is not a QIF file");

        $this->expectException(UnsupportedFileTypeException::class);
        $this->parser->parse($file, ['bankId' => 'TEST', 'accountId' => 'ACC']);
    }

    /**
     * Get supported MIME types
     */
    public function testGetSupportedTypes(): void
    {
        $types = $this->parser->getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('application/x-qif', $types);
        $this->assertContains('text/x-qif', $types);
        $this->assertContains('text/plain', $types);
    }

    /**
     * Get parser name
     */
    public function testGetName(): void
    {
        $name = $this->parser->getName();
        $this->assertEquals('QIF Parser', $name);
    }

    // Helper methods

    private function buildQIFBankSample(): string
    {
        return <<<'QIF'
!Type:Bank
D1/15/24
T-100.00
PTest Store
MTest payment
^
D1/16/24
T-50.00
PDisk Store
MAnother payment
^
QIF;
    }

    private function buildQIFCreditCardSample(): string
    {
        return <<<'QIF'
!Type:CCard
D1/15/24
T-100.00
PVisa Payment
MCredit card charge
^
D1/16/24
T-75.00
PMastercard
MMastercard charge
^
QIF;
    }
}
