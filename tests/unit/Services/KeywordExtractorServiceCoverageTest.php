<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Config\ConfigService;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Domain\Exceptions\InvalidKeywordException;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/Support/fa_db_stubs_services.php';

/**
 * Behavioural tests for the keyword extractor that complement the existing
 * characterisation tests.
 */
final class KeywordExtractorServiceCoverageTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();
    }

    protected function tearDown(): void
    {
        FaDbFake::deactivate();

        parent::tearDown();
    }

    public function testMinimumKeywordLengthIsReadFromTheConfigurationService(): void
    {
        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('get')
            ->with('pattern_matching.min_keyword_length', 3)
            ->willReturn(5);

        $service = new KeywordExtractorService(null, null, ConfigService::create($repository));

        $this->assertSame(5, $service->getMinKeywordLength());
    }

    public function testMinimumKeywordLengthFallsBackToThreeWithoutConfiguration(): void
    {
        $service = new KeywordExtractorService();

        $this->assertSame(3, $service->getMinKeywordLength());
    }

    public function testGetStopwordsReturnsTheConfiguredStopwordList(): void
    {
        $service = new KeywordExtractorService(['alpha', 'beta']);

        $this->assertSame(['alpha', 'beta'], $service->getStopwords());
    }

    public function testAddStopwordNormalisesAndIgnoresDuplicates(): void
    {
        $service = new KeywordExtractorService([]);

        $service->addStopword('  Alpha  ');
        $service->addStopword('alpha');
        $service->addStopword('   ');

        $this->assertSame(['alpha'], $service->getStopwords());
    }

    public function testSetMinKeywordLengthUpdatesTheThreshold(): void
    {
        $service = new KeywordExtractorService();

        $service->setMinKeywordLength(8);

        $this->assertSame(8, $service->getMinKeywordLength());
        $this->assertFalse($service->isValid('testing'));
        $this->assertTrue($service->isValid('testingxy'));
    }

    public function testSetMinKeywordLengthRejectsNonPositiveValues(): void
    {
        $service = new KeywordExtractorService();

        $this->expectException(InvalidKeywordException::class);

        $service->setMinKeywordLength(0);
    }

    public function testExtractReturnsNoKeywordsWhenTheTokenizerFails(): void
    {
        $this->fake->pregSplitFails = true;

        $service = new KeywordExtractorService();

        $this->assertSame([], $service->extract('some text that cannot be tokenised'));
    }

    public function testExtractSkipsTokensThatAreNotValidKeywords(): void
    {
        $service = new KeywordExtractorService();

        $keywords = $service->extract('invoice 12345 payment');

        $texts = array_map(function ($keyword) {
            return $keyword->getText();
        }, $keywords);

        $this->assertContains('invoice', $texts);
        $this->assertContains('payment', $texts);
        $this->assertNotContains('12345', $texts);
    }

    public function testIsValidRejectsTokensThatCannotBeBuiltIntoKeywords(): void
    {
        $service = new KeywordExtractorService();

        $this->assertFalse($service->isValid('12345'));
    }
}
