<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ParserRegistry;

class ParserRegistryTest extends TestCase
{
    public function testReturnsQfxIfDirMissing()
    {
        $registry = new ParserRegistry('/nonexistent/path');
        $parsers = $registry->getAvailableParsers();
        $this->assertArrayHasKey('QFX', $parsers);
        $this->assertEquals('QFX/OFX/Quickbooks (QBO) format', $parsers['QFX']['name']);
    }

    public function testDoesNotOverwriteQfxIfPresent()
    {
        // Setup a fake Parsers dir with a QFX parser.json
        $tmp = sys_get_temp_dir() . '/parsers_' . uniqid();
        mkdir($tmp);
        mkdir("$tmp/QFX");
        file_put_contents("$tmp/QFX/parser.json", json_encode([
            'name' => 'Custom QFX',
            'description' => 'Custom QFX parser',
            'namespace' => 'Custom\\QFX',
            'class' => 'CustomQfxParser',
            'filetype' => 'qfx',
        ]));
        // Enable QFX in config stub
        $this->mockParserConfig(['QFX' => true]);
        $registry = new ParserRegistry($tmp);
        $parsers = $registry->getAvailableParsers();
        $this->assertArrayHasKey('QFX', $parsers);
        $this->assertEquals('Custom QFX', $parsers['QFX']['name']);
        // Cleanup
        unlink("$tmp/QFX/parser.json");
        rmdir("$tmp/QFX");
        rmdir($tmp);
    }

    public function testGetParsersArray()
    {
        $registry = $this->getMockBuilder(ParserRegistry::class)
            ->setConstructorArgs(['/nonexistent/path'])
            ->onlyMethods(['getAvailableParsers'])
            ->getMock();
        $registry->method('getAvailableParsers')->willReturn([
            'QFX' => ['name' => 'QFX/OFX/Quickbooks (QBO) format'],
            'ABC' => ['name' => 'ABC Parser'],
        ]);
        $result = $registry->getParsersArray();
        $this->assertEquals([
            'QFX' => 'QFX/OFX/Quickbooks (QBO) format',
            'ABC' => 'ABC Parser',
        ], $result);
    }

    private function mockParserConfig($states)
    {
        // Patch ParserConfig::getAll() for test
        eval('namespace Ksfraser\\FaBankImport\\Config; function ParserConfig_getAll_override() { return ' . var_export($states, true) . '; }');
        \Ksfraser\FaBankImport\Config\ParserConfig::getAll = 'Ksfraser\\FaBankImport\\Config\\ParserConfig_getAll_override';
    }
}
