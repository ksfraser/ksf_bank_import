<?php
/**
 * Production Baseline Test for QFX Parser Files Location
 * 
 * This test documents the KNOWN-GOOD state of QFX parser files
 * from the prod-bank-import-2025 branch (pre-namespace reorganization).
 * 
 * PROD BASELINE CHARACTERISTICS:
 * - Five QFX parser files exist in ROOT directory:
 *   - class.AbstractQfxParser.php (91 lines)
 *   - class.CibcQfxParser.php (91 lines)
 *   - class.ManuQfxParser.php (91 lines)
 *   - class.PcmcQfxParser.php (91 lines)
 *   - class.QfxParserFactory.php (91 lines)
 * - Each file has @author Kevin Fraser / ChatGPT annotation
 * - Each file has @since 20250409 annotation
 * - Files contain abstract class AbstractQfxParser and concrete implementations
 * - Files use require_once (__DIR__ . '/vendor/autoload.php')
 * - Files have detectBank() and detectBankId() protected methods
 * - NO namespace declarations (plain PHP classes)
 * 
 * CHANGES IN MAIN (detected as test failures):
 * - All 5 files DELETED from root directory (91 deletions each)
 * - All 5 files MOVED to src/Ksfraser/FaBankImport/ directory
 * - Files likely gained namespace declarations
 * - Files likely gained PSR-4 autoloading compliance
 * 
 * TEST STRATEGY:
 * Test for PRESENCE of files in root directory on PROD.
 * Test for ABSENCE of files in src/Ksfraser/FaBankImport/ on PROD.
 * 
 * @package Ksfraser\FaBankImport\Tests\Integration
 * @group ProductionBaseline
 * @group RegressionTest
 */

use PHPUnit\Framework\TestCase;

class QfxParserFilesProductionBaselineTest extends TestCase
{
    private $rootDir;
    private $srcDir;

    protected function setUp(): void
    {
        $this->rootDir = __DIR__ . '/../..';
        $this->srcDir = __DIR__ . '/../../src/Ksfraser/FaBankImport';
    }

    /**
     * Test 1: AbstractQfxParser exists in root directory on PROD
     */
    public function testAbstractQfxParserExistsInRoot(): void
    {
        $filePath = $this->rootDir . '/class.AbstractQfxParser.php';
        $this->assertFileExists($filePath, 
            'PROD has AbstractQfxParser in root directory');
        $this->assertFileIsReadable($filePath);
    }

    /**
     * Test 2: CibcQfxParser exists in root directory on PROD
     */
    public function testCibcQfxParserExistsInRoot(): void
    {
        $filePath = $this->rootDir . '/class.CibcQfxParser.php';
        $this->assertFileExists($filePath,
            'PROD has CibcQfxParser in root directory');
        $this->assertFileIsReadable($filePath);
    }

    /**
     * Test 3: ManuQfxParser exists in root directory on PROD
     */
    public function testManuQfxParserExistsInRoot(): void
    {
        $filePath = $this->rootDir . '/class.ManuQfxParser.php';
        $this->assertFileExists($filePath,
            'PROD has ManuQfxParser in root directory');
        $this->assertFileIsReadable($filePath);
    }

    /**
     * Test 4: PcmcQfxParser exists in root directory on PROD
     */
    public function testPcmcQfxParserExistsInRoot(): void
    {
        $filePath = $this->rootDir . '/class.PcmcQfxParser.php';
        $this->assertFileExists($filePath,
            'PROD has PcmcQfxParser in root directory');
        $this->assertFileIsReadable($filePath);
    }

    /**
     * Test 5: QfxParserFactory exists in root directory on PROD
     */
    public function testQfxParserFactoryExistsInRoot(): void
    {
        $filePath = $this->rootDir . '/class.QfxParserFactory.php';
        $this->assertFileExists($filePath,
            'PROD has QfxParserFactory in root directory');
        $this->assertFileIsReadable($filePath);
    }

    /**
     * Test 6: AbstractQfxParser has expected content structure
     */
    public function testAbstractQfxParserContent(): void
    {
        $filePath = $this->rootDir . '/class.AbstractQfxParser.php';
        $content = file_get_contents($filePath);
        
        $this->assertStringContainsString('@author Kevin Fraser / ChatGPT', $content,
            'PROD has @author annotation');
        $this->assertStringContainsString('@since 20250409', $content,
            'PROD has @since annotation');
        $this->assertStringContainsString('abstract class AbstractQfxParser', $content,
            'PROD has abstract class declaration');
        $this->assertStringContainsString('require_once (__DIR__ . \'/vendor/autoload.php\'', $content,
            'PROD uses require_once for autoload');
        $this->assertStringContainsString('protected function detectBank', $content,
            'PROD has detectBank method');
        $this->assertStringContainsString('protected function detectBankId', $content,
            'PROD has detectBankId method');
    }

    /**
     * Test 7: CibcQfxParser extends AbstractQfxParser
     */
    public function testCibcQfxParserExtendsAbstract(): void
    {
        $filePath = $this->rootDir . '/class.CibcQfxParser.php';
        $content = file_get_contents($filePath);
        
        $this->assertMatchesRegularExpression('/class\s+CibcQfxParser\s+extends\s+AbstractQfxParser/',
            $content, 'PROD CibcQfxParser extends AbstractQfxParser');
    }

    /**
     * Test 8: ManuQfxParser extends AbstractQfxParser
     */
    public function testManuQfxParserExtendsAbstract(): void
    {
        $filePath = $this->rootDir . '/class.ManuQfxParser.php';
        $content = file_get_contents($filePath);
        
        $this->assertMatchesRegularExpression('/class\s+ManuQfxParser\s+extends\s+AbstractQfxParser/',
            $content, 'PROD ManuQfxParser extends AbstractQfxParser');
    }

    /**
     * Test 9: PcmcQfxParser file contains PmcQfxParser class (not Pcmc)
     */
    public function testPcmcFileContainsPmcClass(): void
    {
        $filePath = $this->rootDir . '/class.PcmcQfxParser.php';
        $content = file_get_contents($filePath);
        
        // Note: class name is PmcQfxParser, not PcmcQfxParser
        $this->assertMatchesRegularExpression('/class\s+PmcQfxParser\s+extends\s+AbstractQfxParser/',
            $content, 'PROD PcmcQfxParser file contains PmcQfxParser class');
    }

    /**
     * Test 10: QfxParserFactory has factory pattern
     */
    public function testQfxParserFactoryPattern(): void
    {
        $filePath = $this->rootDir . '/class.QfxParserFactory.php';
        $content = file_get_contents($filePath);
        
        $this->assertStringContainsString('class QfxParserFactory', $content,
            'PROD has QfxParserFactory class');
        $this->assertMatchesRegularExpression('/public\s+(static\s+)?function\s+create/',
            $content, 'PROD has create factory method');
    }

    /**
     * Test 11: NO namespace declarations in PROD files
     */
    public function testNoNamespaceDeclarations(): void
    {
        $files = [
            'class.AbstractQfxParser.php',
            'class.CibcQfxParser.php',
            'class.ManuQfxParser.php',
            'class.PcmcQfxParser.php',
            'class.QfxParserFactory.php'
        ];

        foreach ($files as $file) {
            $filePath = $this->rootDir . '/' . $file;
            $content = file_get_contents($filePath);
            $this->assertStringNotContainsString('namespace ', $content,
                "PROD {$file} has no namespace declaration");
        }
    }

    /**
     * Test 12: PcmcQfxParser file contains ALL parser classes (consolidated)
     */
    public function testPcmcFileContainsAllClasses(): void
    {
        $filePath = $this->rootDir . '/class.PcmcQfxParser.php';
        $content = file_get_contents($filePath);
        
        // PROD has all classes in one file
        $this->assertStringContainsString('abstract class AbstractQfxParser', $content,
            'PROD PcmcQfxParser file contains AbstractQfxParser');
        $this->assertStringContainsString('class CibcQfxParser extends AbstractQfxParser', $content,
            'PROD PcmcQfxParser file contains CibcQfxParser');
        $this->assertStringContainsString('class PmcQfxParser extends AbstractQfxParser', $content,
            'PROD PcmcQfxParser file contains PmcQfxParser');
        $this->assertStringContainsString('class ManuQfxParser extends AbstractQfxParser', $content,
            'PROD PcmcQfxParser file contains ManuQfxParser');
        $this->assertStringContainsString('class QfxParserFactory', $content,
            'PROD PcmcQfxParser file contains QfxParserFactory');
    }

    /**
     * Test 13: AbstractQfxParser has parse() abstract method
     */
    public function testAbstractQfxParserHasParseMethod(): void
    {
        $filePath = $this->rootDir . '/class.AbstractQfxParser.php';
        $content = file_get_contents($filePath);
        
        $this->assertMatchesRegularExpression('/abstract\s+public\s+function\s+parse/',
            $content, 'PROD AbstractQfxParser has abstract parse() method');
    }

    /**
     * Test 14: All parser files are approximately 91 lines
     */
    public function testFileSizesAreConsistent(): void
    {
        $files = [
            'class.AbstractQfxParser.php',
            'class.CibcQfxParser.php',
            'class.ManuQfxParser.php',
            'class.PcmcQfxParser.php',
            'class.QfxParserFactory.php'
        ];

        foreach ($files as $file) {
            $filePath = $this->rootDir . '/' . $file;
            $lineCount = count(file($filePath));
            $this->assertGreaterThan(85, $lineCount,
                "PROD {$file} should be around 91 lines (at least 85)");
            $this->assertLessThan(100, $lineCount,
                "PROD {$file} should be around 91 lines (under 100)");
        }
    }

    /**
     * Test 15: Files use require_once for autoload (not PSR-4 autoloading)
     */
    public function testFilesUseRequireOnce(): void
    {
        $files = [
            'class.AbstractQfxParser.php',
            'class.CibcQfxParser.php',
            'class.ManuQfxParser.php',
            'class.PcmcQfxParser.php',
            'class.QfxParserFactory.php'
        ];

        foreach ($files as $file) {
            $filePath = $this->rootDir . '/' . $file;
            $content = file_get_contents($filePath);
            $this->assertMatchesRegularExpression('/require_once|include_once/',
                $content, "PROD {$file} uses require_once/include_once (not PSR-4)");
        }
    }
}
