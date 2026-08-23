<?php
/**
 * Production Baseline Test for QFX Parser Files Location
 *
 * Originally documented the PROD state of the QFX parser files (location,
 * annotations, source patterns). INVERTED/CONVERTED (refactor-psr): the five
 * parser files now live in src/Ksfraser/FaBankImport/, and this test verifies
 * BEHAVIOR rather than file contents:
 *
 * - All parser classes load and exist at runtime
 * - Class hierarchy: Cibc/Pmc/Manu extend AbstractQfxParser
 * - AbstractQfxParser declares abstract parse() and detectBank()/detectBankId()
 * - QfxParserFactory routes content to the correct concrete parser
 *
 * @package KsfBankImport\Tests\Integration
 * @group ProductionBaseline
 * @group RegressionTest
 */

use PHPUnit\Framework\TestCase;

class QfxParserFilesProductionBaselineTest extends TestCase
{
    private $srcDir;
    private $files = [
        'class.AbstractQfxParser.php',
        'class.CibcQfxParser.php',
        'class.ManuQfxParser.php',
        'class.PcmcQfxParser.php',
        'class.QfxParserFactory.php'
    ];

    protected function setUp(): void
    {
        $this->srcDir = __DIR__ . '/../../src/Ksfraser/FaBankImport';
        foreach ($this->files as $file) {
            $this->assertFileExists($this->srcDir . '/' . $file);
        }
        // Each consolidated file declares all parser classes; require them
        // guarded so repeated loads don't redeclare.
        if (!class_exists('AbstractQfxParser')) {
            require_once $this->srcDir . '/class.AbstractQfxParser.php';
        }
    }

    /**
     * All five files exist in the consolidated src location.
     */
    public function testParserFilesExistInSrcLocation(): void
    {
        // Assertions performed in setUp(); keep a positive assertion for clarity.
        $this->assertDirectoryExists($this->srcDir);
        $this->assertTrue(class_exists('AbstractQfxParser'));
        $this->assertTrue(class_exists('CibcQfxParser'));
        $this->assertTrue(class_exists('PmcQfxParser'));
        $this->assertTrue(class_exists('ManuQfxParser'));
        $this->assertTrue(class_exists('QfxParserFactory'));
    }

    /**
     * Concrete parsers extend AbstractQfxParser (runtime hierarchy, not source text).
     */
    public function testConcreteParsersExtendAbstract(): void
    {
        foreach (['CibcQfxParser', 'PmcQfxParser', 'ManuQfxParser'] as $parser) {
            $this->assertContains(
                'AbstractQfxParser',
                class_parents($parser),
                "$parser must extend AbstractQfxParser"
            );
        }
    }

    /**
     * AbstractQfxParser contract: parse() is abstract; detectBank()/detectBankId()
     * are available to subclasses. The consolidated prod file also declared all
     * classes together, which is preserved by loading any one of the files.
     */
    public function testAbstractContract(): void
    {
        $ref = new ReflectionClass('AbstractQfxParser');

        $parse = $ref->getMethod('parse');
        $this->assertTrue($parse->isAbstract(), 'parse() must remain abstract');
        $this->assertFalse($ref->isInstantiable(), 'AbstractQfxParser must not be instantiable');

        $this->assertTrue($ref->hasMethod('detectBank'), 'detectBank() must exist');
        $this->assertTrue($ref->hasMethod('detectBankId'), 'detectBankId() must exist');
    }

    /**
     * Factory routes content markers to the correct concrete parser instance.
     */
    public function testFactoryRoutesToCorrectParser(): void
    {
        $this->assertInstanceOf('CibcQfxParser', QfxParserFactory::createParser('... CIBC statement ...'));
        $this->assertInstanceOf('PmcQfxParser', QfxParserFactory::createParser('... PMC statement ...'));
        $this->assertInstanceOf('ManuQfxParser', QfxParserFactory::createParser('... MANU statement ...'));
    }

    /**
     * Factory rejects unrecognized content instead of returning a wrong parser.
     */
    public function testFactoryThrowsForUnknownContent(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown bank type');
        QfxParserFactory::createParser('no known bank marker here');
    }
}
