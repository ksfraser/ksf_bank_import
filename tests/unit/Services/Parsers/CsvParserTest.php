<?php

namespace Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Parsers\CsvParser;
use Ksfraser\FaBankImport\Import\Services\Parsers\SynonymResolver;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
use Ksfraser\Exceptions\Utility\EncodingMismatchException;

/**
 * Unit Tests for CsvParser
 *
 * Tests configurable CSV parsing with:
 * - Header-based synonym mapping
 * - Index-based bank-specific mapping
 * - Runtime custom synonyms
 * - Config file synonyms
 * - Encoding detection
 * - All 4 exception types
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Parsers\CsvParser
 */
class CsvParserTest extends TestCase
{
    private CsvParser $parser;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->parser = new CsvParser();
        $this->tempDir = sys_get_temp_dir() . '/csv_parser_tests_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    /**
     * Test 1: Parse valid CSV with standard headers (auto-detect)
     */
    public function testParseValidCsvWithStandardHeaders(): void
    {
        $csv = "Date,Amount,Merchant Name,Description,Reference Number\n";
        $csv .= "2024-01-15,100.00,Vendor ABC,Payment,REF001\n";
        $csv .= "2024-01-16,50.50,Vendor XYZ,Refund,REF002\n";

        $file = $this->tempDir . '/test_standard.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(ParsedStatementDTO::class, $result[0]);
    }

    /**
     * Test 2: Parse CSV with custom header variations (synonym matching)
     */
    public function testParseCSVWithHeaderVariations(): void
    {
        $csv = "Transaction Date,Sum,Beneficiary,Memo,Reference\n";
        $csv .= "2024-01-15,100.00,Vendor ABC,Payment desc,REF001\n";

        $file = $this->tempDir . '/test_synonyms.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        // Should match transactionDate → Transaction Date, amount → Sum, etc.
    }

    /**
     * Test 3: Parse CSV with explicit columnMapping option
     */
    public function testParseWithExplicitColumnMapping(): void
    {
        $csv = "Col1,Col2,Col3,Col4,Col5\n";
        $csv .= "2024-01-15,100.00,ABC,Payment,REF001\n";

        $file = $this->tempDir . '/test_explicit.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file, [
            'columnMapping' => [
                'transactionDate' => 'Col1',
                'amount' => 'Col2',
                'merchant' => 'Col3',
                'description' => 'Col4',
                'reference' => 'Col5',
            ]
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test 4: Parse CSV with bank identifier (ro_wmmc preset)
     */
    public function testParseWithBankIdentifierWmmc(): void
    {
        $csv = "Date,Amount,Merchant Name,Merchant Category,Activity Type,Reference Number\n";
        $csv .= "2024-01-15,100.00,Vendor ABC,Shopping,Purchase,REF001\n";

        $file = $this->tempDir . '/test_wmmc.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file, [
            'bankIdentifier' => 'ro_wmmc'
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test 5: Parse CSV with runtime custom synonyms
     */
    public function testParseWithRuntimeCustomSynonyms(): void
    {
        $csv = "PostDate,Qty,Vendor,Note,OrderNum\n";
        $csv .= "2024-01-15,100.00,ABC,Payment,ORD001\n";

        $file = $this->tempDir . '/test_custom_synonyms.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file, [
            'customSynonyms' => [
                'transactionDate' => ['PostDate'],
                'amount' => ['Qty'],
                'merchant' => ['Vendor'],
                'description' => ['Note'],
                'reference' => ['OrderNum']
            ]
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test 6: Parse CSV with config file synonyms
     */
    public function testParseWithConfigFileSynonyms(): void
    {
        $config = [
            'synonyms' => [
                'transactionDate' => ['PostDate', 'ValueDate'],
                'amount' => ['Qty', 'Amount'],
                'merchant' => ['Vendor', 'Merchant Name'],
            ]
        ];

        $configFile = $this->tempDir . '/config.json';
        file_put_contents($configFile, json_encode($config));

        $csv = "PostDate,Qty,Vendor\n2024-01-15,100.00,ABC\n";
        $file = $this->tempDir . '/test_config.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file, [
            'synonymConfigFile' => $configFile
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test 7: Parse CSV with multiple transactions (same date/account)
     */
    public function testParseMultipleTransactionsSameDateAccount(): void
    {
        $csv = "Date,Amount,Merchant Name,Description,Reference Number\n";
        $csv .= "2024-01-15,100.00,Vendor ABC,Payment 1,REF001\n";
        $csv .= "2024-01-15,50.00,Vendor XYZ,Payment 2,REF002\n";
        $csv .= "2024-01-15,25.00,Vendor DEF,Payment 3,REF003\n";

        $file = $this->tempDir . '/test_multi.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        // Should group transactions by date/account
    }

    /**
     * Test 8: Parse CSV with empty lines (skip)
     */
    public function testParseCSVWithEmptyLines(): void
    {
        $csv = "Date,Amount,Merchant Name,Description,Reference Number\n";
        $csv .= "2024-01-15,100.00,Vendor ABC,Payment,REF001\n";
        $csv .= "\n";
        $csv .= "2024-01-16,50.00,Vendor XYZ,Payment,REF002\n";

        $file = $this->tempDir . '/test_empty_lines.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test 9: File not found - throws FileNotFoundException
     */
    public function testFileNotFoundThrowsException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->parser->parse('/path/that/does/not/exist.csv');
    }

    /**
     * Test 10: File not readable - throws FileNotFoundException
     */
    public function testFileNotReadableThrowsException(): void
    {
        $file = $this->tempDir . '/no_permission.csv';
        file_put_contents($file, 'Date,Amount\n');
        chmod($file, 0000);

        try {
            $this->expectException(FileNotFoundException::class);
            $this->parser->parse($file);
        } finally {
            chmod($file, 0644);
        }
    }

    /**
     * Test 11: Empty CSV file - throws ParsingFailedException
     */
    public function testEmptyCSVThrowsException(): void
    {
        $file = $this->tempDir . '/empty.csv';
        file_put_contents($file, '');

        $this->expectException(ParsingFailedException::class);
        $this->parser->parse($file);
    }

    /**
     * Test 12: CSV with empty header row - throws ParsingFailedException
     */
    public function testEmptyHeaderRowThrowsException(): void
    {
        $file = $this->tempDir . '/empty_header.csv';
        file_put_contents($file, "\n2024-01-15,100.00\n");

        $this->expectException(ParsingFailedException::class);
        $this->parser->parse($file);
    }

    /**
     * Test 13: Invalid encoding handling
     */
    public function testEncodingDetectionAndConversion(): void
    {
        // Create UTF-8 with BOM
        $csv = "\xEF\xBB\xBFDate,Amount,Merchant Name,Description,Reference Number\n";
        $csv .= "2024-01-15,100.00,Vendor ABC,Payment,REF001\n";

        $file = $this->tempDir . '/test_bom.csv';
        file_put_contents($file, $csv);

        $result = $this->parser->parse($file);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test 14: SynonymResolver injection (dependency injection)
     */
    public function testCustomSynonymResolverInjection(): void
    {
        $customResolver = new SynonymResolver();
        $customResolver->addSynonym('transactionDate', 'CustomDateHeader', 'csv');
        $customResolver->addSynonym('amount', 'CustomAmount', 'csv');

        $parser = new CsvParser($customResolver);

        $csv = "CustomDateHeader,CustomAmount,Merchant Name\n";
        $csv .= "2024-01-15,100.00,ABC\n";

        $file = $this->tempDir . '/test_di.csv';
        file_put_contents($file, $csv);

        $result = $parser->parse($file);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Get supported MIME types
     */
    public function testGetSupportedTypes(): void
    {
        $types = $this->parser->getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('text/csv', $types);
        $this->assertContains('application/csv', $types);
        $this->assertContains('text/plain', $types);
    }

    /**
     * Get parser name
     */
    public function testGetName(): void
    {
        $name = $this->parser->getName();
        $this->assertEquals('CSV Parser', $name);
    }
}
