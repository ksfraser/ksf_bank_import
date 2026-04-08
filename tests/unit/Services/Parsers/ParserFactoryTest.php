<?php

namespace Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Parsers\ParserFactory;
use Ksfraser\FaBankImport\Import\Services\Parsers\CsvParser;
use Ksfraser\FaBankImport\Import\Services\Parsers\OFXParser;
use Ksfraser\FaBankImport\Import\Services\Parsers\QIFParser;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\UnsupportedFileTypeException;

/**
 * Unit Tests for ParserFactory
 *
 * Tests MIME type detection and parser routing:
 * - MIME type detection via finfo
 * - Extension-based fallback detection
 * - Parser instantiation and routing
 * - Supported file checks
 * - Error handling for unsupported types
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Parsers\ParserFactory
 */
class ParserFactoryTest extends TestCase
{
    private ParserFactory $factory;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->factory = new ParserFactory();
        $this->tempDir = sys_get_temp_dir() . '/parser_factory_tests_' . uniqid();
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
     * Test 1: Create CSV parser for .csv file
     */
    public function testCreateCSVParserForCSVFile(): void
    {
        $file = $this->tempDir . '/test.csv';
        file_put_contents($file, "Date,Amount\n");

        $parser = $this->factory->create($file);

        $this->assertInstanceOf(CsvParser::class, $parser);
    }

    /**
     * Test 2: Create CSV parser for .txt file
     */
    public function testCreateCSVParserForTxtFile(): void
    {
        $file = $this->tempDir . '/test.txt';
        file_put_contents($file, "Date,Amount\n");

        $parser = $this->factory->create($file);

        $this->assertInstanceOf(CsvParser::class, $parser);
    }

    /**
     * Test 3: Create OFX parser for .ofx file
     */
    public function testCreateOFXParserForOFXFile(): void
    {
        $file = $this->tempDir . '/test.ofx';
        file_put_contents($file, "OFXHEADER:100\n");

        $parser = $this->factory->create($file);

        $this->assertInstanceOf(OFXParser::class, $parser);
    }

    /**
     * Test 4: Create OFX parser for .qfx file
     */
    public function testCreateOFXParserForQFXFile(): void
    {
        $file = $this->tempDir . '/test.qfx';
        file_put_contents($file, "!OFX\n<OFX>\n");

        $parser = $this->factory->create($file);

        $this->assertInstanceOf(OFXParser::class, $parser);
    }

    /**
     * Test 5: Create QIF parser for .qif file
     */
    public function testCreateQIFParserForQIFFile(): void
    {
        $file = $this->tempDir . '/test.qif';
        file_put_contents($file, "!Type:Bank\n");

        $parser = $this->factory->create($file);

        $this->assertInstanceOf(QIFParser::class, $parser);
    }

    /**
     * Test 6: File not found - throws FileNotFoundException
     */
    public function testFileNotFoundThrowsException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->factory->create('/path/that/does/not/exist.csv');
    }

    /**
     * Test 7: Unsupported file type - throws UnsupportedFileTypeException
     */
    public function testUnsupportedFileTypeThrowsException(): void
    {
        $file = $this->tempDir . '/test.xyz';
        file_put_contents($file, "Random content");

        $this->expectException(UnsupportedFileTypeException::class);
        $this->factory->create($file);
    }

    /**
     * Test 8: isSupported returns true for known types
     */
    public function testIsSupportedReturnsTrueForKnownTypes(): void
    {
        $file = $this->tempDir . '/test.csv';
        file_put_contents($file, "Date,Amount\n");

        $supported = $this->factory->isSupported($file);

        $this->assertTrue($supported);
    }

    /**
     * Test 9: isSupported returns false for unknown types
     */
    public function testIsSupportedReturnsFalseForUnknownTypes(): void
    {
        $file = $this->tempDir . '/test.xyz';
        file_put_contents($file, "Random content");

        $supported = $this->factory->isSupported($file);

        $this->assertFalse($supported);
    }

    /**
     * Get supported extensions
     */
    public function testGetSupportedExtensions(): void
    {
        $extensions = $this->factory->getSupportedExtensions();

        $this->assertIsArray($extensions);
        $this->assertContains('csv', $extensions);
        $this->assertContains('txt', $extensions);
        $this->assertContains('ofx', $extensions);
        $this->assertContains('qfx', $extensions);
        $this->assertContains('qif', $extensions);
    }

    /**
     * Get available parsers information
     */
    public function testGetAvailableParsers(): void
    {
        $parsers = $this->factory->getAvailableParsers();

        $this->assertIsArray($parsers);
        $this->assertArrayHasKey('csv', $parsers);
        $this->assertArrayHasKey('ofx', $parsers);
        $this->assertArrayHasKey('qif', $parsers);

        // Verify CSV parser info
        $this->assertArrayHasKey('name', $parsers['csv']);
        $this->assertArrayHasKey('class', $parsers['csv']);
        $this->assertArrayHasKey('extensions', $parsers['csv']);
        $this->assertArrayHasKey('mimeTypes', $parsers['csv']);
        $this->assertArrayHasKey('description', $parsers['csv']);

        $this->assertEquals('CSV Parser', $parsers['csv']['name']);
        $this->assertContains('csv', $parsers['csv']['extensions']);
    }
}
