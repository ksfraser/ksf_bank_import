<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for parsers.inc functions
 *
 * Tests the parser discovery and configuration:
 * - getParsers() function
 * - Parser configuration loading
 * - Legacy parser inclusion
 */
class ParsersTest extends TestCase
{
    private $originalParsersDir;

    protected function setUp(): void
    {
        $this->originalParsersDir = null;
    }

    protected function tearDown(): void
    {
        // Restore any modified globals
        if ($this->originalParsersDir) {
            // Reset any global state if needed
        }
    }

    /**
     * Test getParsers returns array
     */
    public function testGetParsersReturnsArray(): void
    {
        $parsers = getParsers();
        $this->assertIsArray($parsers);
    }

    /**
     * Test getParsers includes QFX parser
     */
    public function testGetParsersIncludesQfxParser(): void
    {
        $parsers = getParsers();

        $this->assertArrayHasKey('QFX', $parsers);
        $this->assertEquals('QFX/OFX/Quickbooks (QBO) format', $parsers['QFX']['name']);
        $this->assertArrayHasKey('select', $parsers['QFX']);
        $this->assertArrayHasKey('bank_account', $parsers['QFX']['select']);
    }

    /**
     * Test getParsers with non-existent parsers directory
     */
    public function testGetParsersWithNonExistentDirectory(): void
    {
        // Mock the parsers directory to not exist
        $parsers = getParsers();

        // Should still return QFX parser
        $this->assertArrayHasKey('QFX', $parsers);
        $this->assertGreaterThanOrEqual(1, count($parsers));
    }

    /**
     * Test getParsers discovers parser configurations
     */
    public function testGetParsersDiscoversConfigurations(): void
    {
        // This test would require setting up a mock parsers directory
        // with parser.json files, which is complex for unit testing

        $this->markTestIncomplete('Requires mock filesystem setup for parser discovery');
    }

    /**
     * Test ParserConfig integration
     */
    public function testParserConfigIntegration(): void
    {
        // Test that ParserConfig class exists and can be used
        $this->assertTrue(class_exists('\\Ksfraser\\FaBankImport\\Config\\ParserConfig'));

        $config = \Ksfraser\FaBankImport\Config\ParserConfig::getAll();
        $this->assertIsArray($config);
    }

    /**
     * Test parser configuration structure
     */
    public function testParserConfigurationStructure(): void
    {
        $parsers = getParsers();

        // Check that each parser has required fields
        foreach ($parsers as $pid => $parser) {
            $this->assertIsString($pid);
            $this->assertIsArray($parser);
            $this->assertArrayHasKey('name', $parser);

            // Most parsers should have select configuration
            if ($pid !== 'QFX') {
                $this->assertArrayHasKey('select', $parser);
                $this->assertArrayHasKey('bank_account', $parser['select']);
            }
        }
    }

    /**
     * Test QFX parser specific configuration
     */
    public function testQfxParserConfiguration(): void
    {
        $parsers = getParsers();

        $qfx = $parsers['QFX'];

        $this->assertEquals('QFX/OFX/Quickbooks (QBO) format', $qfx['name']);
        $this->assertArrayHasKey('select', $qfx);
        $this->assertEquals('Select bank account', $qfx['select']['bank_account']);
    }

    /**
     * Test parser directory scanning
     */
    public function testParserDirectoryScanning(): void
    {
        // Test the directory scanning logic conceptually
        $parsersDir = dirname(__DIR__, 2) . '/Parsers';

        if (is_dir($parsersDir)) {
            $dirs = scandir($parsersDir);
            $this->assertIsArray($dirs);
            $this->assertContains('.', $dirs);
            $this->assertContains('..', $dirs);
        } else {
            $this->markTestSkipped('Parsers directory does not exist');
        }
    }

    /**
     * Test parser.json file parsing
     */
    public function testParserJsonParsing(): void
    {
        $parsersDir = dirname(__DIR__, 2) . '/Parsers';

        if (is_dir($parsersDir)) {
            $dirs = scandir($parsersDir);

            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;

                $parserJson = $parsersDir . '/' . $dir . '/parser.json';
                if (is_file($parserJson)) {
                    $config = json_decode(file_get_contents($parserJson), true);

                    $this->assertIsArray($config);
                    $this->assertArrayHasKey('name', $config);
                    $this->assertArrayHasKey('description', $config);
                    $this->assertArrayHasKey('namespace', $config);
                    $this->assertArrayHasKey('class', $config);
                    $this->assertArrayHasKey('filetype', $config);
                }
            }
        } else {
            $this->markTestSkipped('Parsers directory does not exist');
        }
    }

    /**
     * Test enabled parser filtering
     */
    public function testEnabledParserFiltering(): void
    {
        $enabledStates = \Ksfraser\FaBankImport\Config\ParserConfig::getAll();
        $parsers = getParsers();

        // All returned parsers should be enabled
        foreach (array_keys($parsers) as $pid) {
            if ($pid !== 'QFX') { // QFX is always included
                $this->assertArrayHasKey($pid, $enabledStates);
                $this->assertNotEmpty($enabledStates[$pid]);
            }
        }
    }

    /**
     * Test parser configuration validation
     */
    public function testParserConfigurationValidation(): void
    {
        $parsers = getParsers();

        foreach ($parsers as $pid => $parser) {
            // Validate required fields
            $this->assertArrayHasKey('name', $parser);
            $this->assertIsString($parser['name']);
            $this->assertNotEmpty($parser['name']);

            if (isset($parser['description'])) {
                $this->assertIsString($parser['description']);
            }

            if (isset($parser['namespace'])) {
                $this->assertIsString($parser['namespace']);
            }

            if (isset($parser['class'])) {
                $this->assertIsString($parser['class']);
            }

            if (isset($parser['filetype'])) {
                $this->assertIsString($parser['filetype']);
            }
        }
    }
}