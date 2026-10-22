<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Parser;

use PHPUnit\Framework\TestCase;

require_once FA_ROOT . '/src/Ksfraser/FaBankImport/class.AbstractQfxParser.php';

/**
 * A probe for the abstract QFX parser.
 *
 * The parser lives in the global namespace and declares its bank
 * detection as protected, so the probe exposes them.
 */
final class QfxParserProbe extends \AbstractQfxParser
{
    /**
     * @inheritDoc
     */
    public function parse($content, $static_data = array(), $debug = true)
    {
    }

    /**
     * @param object $institute The institute of the statement.
     * @param array $staticData The static data of the statement.
     * @return string The detected bank.
     */
    public function callDetectBank($institute, array $staticData): string
    {
        return $this->detectBank($institute, $staticData);
    }

    /**
     * @param object $institute The institute of the statement.
     * @param array $staticData The static data of the statement.
     * @return string The detected bank identifier.
     */
    public function callDetectBankId($institute, array $staticData): string
    {
        return $this->detectBankId($institute, $staticData);
    }

    /**
     * @return bool Whether the bank was taken from the file.
     */
    public function isBankFromFile(): bool
    {
        return $this->bank_from_file;
    }

    /**
     * @return bool Whether the bank identifier was taken from the file.
     */
    public function isBankIdFromFile(): bool
    {
        return $this->bankid_from_file;
    }
}

/**
 * Coverage for the abstract QFX parser and the parsers that extend it.
 */
final class AbstractQfxParserTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheBankIsTakenFromTheFile(): void
    {
        $parser = new QfxParserProbe();
        $institute = (object) ['name' => 'Test Bank', 'id' => '123'];

        $this->assertSame('Test Bank', $parser->callDetectBank($institute, []));
        $this->assertTrue($parser->isBankFromFile());
    }

    /**
     * @return void
     */
    public function testTheBankFallsBackToTheStaticData(): void
    {
        $parser = new QfxParserProbe();
        $institute = (object) ['name' => null, 'id' => null];

        $this->assertSame('Chequing', $parser->callDetectBank($institute, ['account_name' => 'Chequing']));
        $this->assertFalse($parser->isBankFromFile());
    }

    /**
     * @return void
     */
    public function testTheBankFallsBackToSavings(): void
    {
        $parser = new QfxParserProbe();
        $institute = (object) ['name' => null, 'id' => null];

        $this->assertSame('Savings', $parser->callDetectBank($institute, []));
        $this->assertFalse($parser->isBankFromFile());
    }

    /**
     * @return void
     */
    public function testTheBankIdentifierIsTakenFromTheFile(): void
    {
        $parser = new QfxParserProbe();
        $institute = (object) ['name' => 'Test Bank', 'id' => '123'];

        $this->assertSame('123', $parser->callDetectBankId($institute, []));
        $this->assertTrue($parser->isBankIdFromFile());
    }

    /**
     * @return void
     */
    public function testTheBankIdentifierFallsBackToTheStaticData(): void
    {
        $parser = new QfxParserProbe();
        $institute = (object) ['name' => null, 'id' => null];

        $this->assertSame('0042', $parser->callDetectBankId($institute, ['account_code' => '0042']));
        $this->assertFalse($parser->isBankIdFromFile());
    }

    /**
     * @return void
     */
    public function testTheBankIdentifierFallsBackToTheDefault(): void
    {
        $parser = new QfxParserProbe();
        $institute = (object) ['name' => null, 'id' => null];

        $this->assertSame('1060', $parser->callDetectBankId($institute, []));
        $this->assertFalse($parser->isBankIdFromFile());
    }

    /**
     * The concrete parsers carry no logic of their own yet.
     *
     * @return array<string, array{0: string}>
     */
    public function concreteParserProvider(): array
    {
        return [
            'cibc' => ['\CibcQfxParser'],
            'pmc' => ['\PmcQfxParser'],
            'manu' => ['\ManuQfxParser'],
        ];
    }

    /**
     * @dataProvider concreteParserProvider
     * @param string $class The parser class.
     * @return void
     */
    public function testTheConcreteParsersCanBeUsed(string $class): void
    {
        $parser = new $class();

        $this->assertNull($parser->parse('<OFX>'));
    }

    /**
     * @return void
     */
    public function testTheFactoryPicksTheParserOfTheBank(): void
    {
        $this->assertInstanceOf('\CibcQfxParser', \QfxParserFactory::createParser('CIBC'));
        $this->assertInstanceOf('\PmcQfxParser', \QfxParserFactory::createParser('PMC'));
        $this->assertInstanceOf('\ManuQfxParser', \QfxParserFactory::createParser('MANU'));
    }
}
