<?php

namespace Tests\Unit\Domain\ValueObjects;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for KeywordMatch value object
 */
class KeywordMatchTest extends TestCase
{
    private function createTestKeywordMatch(): KeywordMatch
    {
        $keywords = [
            new Keyword('shoppers'),
            new Keyword('drug'),
            new Keyword('mart'),
        ];

        $confidence = new MatchConfidence(75.0, 85.0); // 78.5% confidence

        return new KeywordMatch(
            123,
            2,
            5,
            'Shoppers Drug Mart',
            $keywords,
            150,
            165.0,
            $confidence
        );
    }

    public function testConstructorCreatesValidObject(): void
    {
        $match = $this->createTestKeywordMatch();

        $this->assertSame(123, $match->getPartnerId());
        $this->assertSame(2, $match->getPartnerType());
        $this->assertSame(5, $match->getPartnerDetailId());
        $this->assertSame('Shoppers Drug Mart', $match->getPartnerName());
        $this->assertSame(3, $match->getMatchedKeywordCount());
        $this->assertSame(150, $match->getRawScore());
        $this->assertSame(165.0, $match->getFinalScore());
    }

    public function testConstructorThrowsOnInvalidPartnerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner ID must be positive');

        new KeywordMatch(
            0,
            2,
            5,
            'Test',
            [new Keyword('test')],
            100,
            110.0,
            new MatchConfidence(75.0, 85.0)
        );
    }

    public function testConstructorThrowsOnEmptyPartnerName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner name cannot be empty');

        new KeywordMatch(
            123,
            2,
            5,
            '',
            [new Keyword('test')],
            100,
            110.0,
            new MatchConfidence(75.0, 85.0)
        );
    }

    public function testConstructorThrowsOnEmptyKeywords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Must have at least one matched keyword');

        new KeywordMatch(
            123,
            2,
            5,
            'Test',
            [],
            100,
            110.0,
            new MatchConfidence(75.0, 85.0)
        );
    }

    public function testConstructorThrowsOnNegativeRawScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Raw score must be non-negative');

        new KeywordMatch(
            123,
            2,
            5,
            'Test',
            [new Keyword('test')],
            -10,
            110.0,
            new MatchConfidence(75.0, 85.0)
        );
    }

    public function testConstructorThrowsOnNegativeFinalScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Final score must be non-negative');

        new KeywordMatch(
            123,
            2,
            5,
            'Test',
            [new Keyword('test')],
            100,
            -10.0,
            new MatchConfidence(75.0, 85.0)
        );
    }

    public function testConstructorThrowsWhenFinalScoreLessThanRawScore(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Final score (50.00) should not be less than raw score (100)');

        new KeywordMatch(
            123,
            2,
            5,
            'Test',
            [new Keyword('test')],
            100,
            50.0,
            new MatchConfidence(75.0, 85.0)
        );
    }

    public function testGetConfidencePercentage(): void
    {
        $match = $this->createTestKeywordMatch();

        // confidence = (75 * 0.6) + (85 * 0.4) = 45 + 34 = 79
        $this->assertSame(79.0, $match->getConfidencePercentage());
    }

    public function testMeetsConfidenceThreshold(): void
    {
        $match = $this->createTestKeywordMatch();

        $this->assertTrue($match->meetsConfidenceThreshold(70.0));
        $this->assertTrue($match->meetsConfidenceThreshold(79.0));
        $this->assertFalse($match->meetsConfidenceThreshold(80.0));
    }

    public function testGetClusteringBonus(): void
    {
        $match = $this->createTestKeywordMatch();

        // clustering bonus = finalScore / rawScore = 165 / 150 = 1.1
        $this->assertEqualsWithDelta(1.1, $match->getClusteringBonus(), 0.01);
    }

    public function testGetClusteringBonusWithZeroRawScore(): void
    {
        $match = new KeywordMatch(
            123,
            2,
            5,
            'Test',
            [new Keyword('test')],
            0,
            0.0,
            new MatchConfidence(75.0, 85.0)
        );

        $this->assertSame(0.0, $match->getClusteringBonus());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $match = $this->createTestKeywordMatch();

        $array = $match->toArray();

        $this->assertIsArray($array);
        $this->assertSame(123, $array['partner_id']);
        $this->assertSame(2, $array['partner_type']);
        $this->assertSame(5, $array['partner_detail_id']);
        $this->assertSame('Shoppers Drug Mart', $array['partner_name']);
        $this->assertIsArray($array['matched_keywords']);
        $this->assertCount(3, $array['matched_keywords']);
        $this->assertSame('shoppers', $array['matched_keywords'][0]);
        $this->assertSame('drug', $array['matched_keywords'][1]);
        $this->assertSame('mart', $array['matched_keywords'][2]);
        $this->assertSame(3, $array['matched_keyword_count']);
        $this->assertSame(150, $array['raw_score']);
        $this->assertSame(165.0, $array['final_score']);
        $this->assertEqualsWithDelta(1.1, $array['clustering_bonus'], 0.01);
        $this->assertSame(79.0, $array['confidence_percentage']);
        $this->assertSame(75.0, $array['keyword_coverage']);
        $this->assertSame(85.0, $array['score_strength']);
    }

    public function testToStringReturnsReadableFormat(): void
    {
        $match = $this->createTestKeywordMatch();

        $string = (string)$match;

        $this->assertStringContainsString('Shoppers Drug Mart', $string);
        $this->assertStringContainsString('ID:123', $string);
        $this->assertStringContainsString('keywords:3', $string);
        $this->assertStringContainsString('score:165.00', $string);
        $this->assertStringContainsString('confidence:79.0%', $string);
    }
}
