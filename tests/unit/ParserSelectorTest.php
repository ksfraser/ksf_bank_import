<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Request\ParserSelector;
use Ksfraser\FaBankImport\Request\ParameterProvider;
use Ksfraser\FaBankImport\Services\ParserRegistry;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;

class ParserSelectorTest extends TestCase
{
    public function testGetSelectedParserReturnsParserFromParameterProvider()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturnCallback(function($key) {
            return $key === 'parser' ? 'QFX' : null;
        });

        $configRepo = $this->createMock(DatabaseConfigRepository::class);
        $parserRegistry = new ParserRegistry($configRepo);

        $selector = new ParserSelector($parameterProvider, $parserRegistry);
        $this->assertEquals('QFX', $selector->getSelectedParser());
    }

    public function testGetSelectedParserReturnsDefaultWhenNoParserSelected()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturn(null);

        $configRepo = $this->createMock(DatabaseConfigRepository::class);
        $parserRegistry = $this->createMock(ParserRegistry::class);
        $parserRegistry->method('getParsersArray')->willReturn([
            'QFX' => ['name' => 'QFX Parser'],
            'OFX' => ['name' => 'OFX Parser']
        ]);

        $selector = new ParserSelector($parameterProvider, $parserRegistry);
        $this->assertEquals('QFX', $selector->getSelectedParser());
    }

    public function testGetSelectedParserReturnsFirstParserWhenQFXNotAvailable()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturn(null);

        $configRepo = $this->createMock(DatabaseConfigRepository::class);
        $parserRegistry = $this->createMock(ParserRegistry::class);
        $parserRegistry->method('getParsersArray')->willReturn([
            'OFX' => ['name' => 'OFX Parser'],
            'CSV' => ['name' => 'CSV Parser']
        ]);

        $selector = new ParserSelector($parameterProvider, $parserRegistry);
        $this->assertEquals('OFX', $selector->getSelectedParser());
    }
}