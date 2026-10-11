<?php

namespace Tests\Unit\Domain\ValueObjects;

use Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MatchConfidence value object
 */
class MatchConfidenceTest extends TestCase
{
    public function testConstructorCreatesValidObject(): void
    {
        $confidence = new MatchConfidence(80.0, 75.0);

        $this->assertSame(80.0, $confidence->getKeywordCoverage());
        $this->assertSame(75.0, $confidence->getScoreStrength());
        // confidence = (80 * 0.6) + (75 * 0.4) = 48 + 30 = 78
        $this->assertSame(78.0, $confidence->getPercentage());
    }

    public function testConstructorCalculatesConfidenceCorrectly(): void
    {
        // 100% coverage, 100% strength = 100% confidence
        $confidence = new MatchConfidence(100.0, 100.0);
        $this->assertSame(100.0, $confidence->getPercentage());

        // 50% coverage, 50% strength = 50% confidence
        $confidence = new MatchConfidence(50.0, 50.0);
        $this->assertSame(50.0, $confidence->getPercentage());

        // 60% coverage, 40% strength = (60*0.6) + (40*0.4) = 36 + 16 = 52%
        $confidence = new MatchConfidence(60.0, 40.0);
        $this->assertSame(52.0, $confidence->getPercentage());
    }

    public function testConstructorThrowsOnInvalidKeywordCoverage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword coverage must be between 0 and 100');

        new MatchConfidence(150.0, 50.0);
    }

    public function testConstructorThrowsOnNegativeKeywordCoverage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword coverage must be between 0 and 100');

        new MatchConfidence(-10.0, 50.0);
    }

    public function testConstructorThrowsOnInvalidScoreStrength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Score strength must be between 0 and 100');

        new MatchConfidence(50.0, 150.0);
    }

    public function testMeetsThresholdReturnsTrueWhenAboveThreshold(): void
    {
        $confidence = new MatchConfidence(80.0, 80.0); // 80% confidence

        $this->assertTrue($confidence->meetsThreshold(70.0));
        $this->assertTrue($confidence->meetsThreshold(80.0));
    }

    public function testMeetsThresholdReturnsFalseWhenBelowThreshold(): void
    {
        $confidence = new MatchConfidence(60.0, 50.0); // 56% confidence

        $this->assertFalse($confidence->meetsThreshold(70.0));
    }

    public function testIsHighConfidence(): void
    {
        $highConfidence = new MatchConfidence(90.0, 85.0); // 88% confidence
        $mediumConfidence = new MatchConfidence(60.0, 60.0); // 60% confidence
        $lowConfidence = new MatchConfidence(30.0, 30.0); // 30% confidence

        $this->assertTrue($highConfidence->isHighConfidence());
        $this->assertFalse($mediumConfidence->isHighConfidence());
        $this->assertFalse($lowConfidence->isHighConfidence());
    }

    public function testIsMediumConfidence(): void
    {
        $highConfidence = new MatchConfidence(90.0, 85.0); // 88% confidence
        $mediumConfidence = new MatchConfidence(60.0, 60.0); // 60% confidence
        $lowConfidence = new MatchConfidence(30.0, 30.0); // 30% confidence

        $this->assertFalse($highConfidence->isMediumConfidence());
        $this->assertTrue($mediumConfidence->isMediumConfidence());
        $this->assertFalse($lowConfidence->isMediumConfidence());
    }

    public function testIsLowConfidence(): void
    {
        $highConfidence = new MatchConfidence(90.0, 85.0); // 88% confidence
        $mediumConfidence = new MatchConfidence(60.0, 60.0); // 60% confidence
        $lowConfidence = new MatchConfidence(30.0, 30.0); // 30% confidence

        $this->assertFalse($highConfidence->isLowConfidence());
        $this->assertFalse($mediumConfidence->isLowConfidence());
        $this->assertTrue($lowConfidence->isLowConfidence());
    }

    public function testGetLevel(): void
    {
        $highConfidence = new MatchConfidence(90.0, 85.0);
        $mediumConfidence = new MatchConfidence(60.0, 60.0);
        $lowConfidence = new MatchConfidence(30.0, 30.0);

        $this->assertSame('high', $highConfidence->getLevel());
        $this->assertSame('medium', $mediumConfidence->getLevel());
        $this->assertSame('low', $lowConfidence->getLevel());
    }

    public function testFromMatchStatisticsCreatesValidObject(): void
    {
        // 2 out of 3 keywords matched = 66.67% coverage
        // Score 100 out of 150 top score = 66.67% strength
        $confidence = MatchConfidence::fromMatchStatistics(2, 3, 100.0, 150.0);

        $this->assertEqualsWithDelta(66.67, $confidence->getKeywordCoverage(), 0.01);
        $this->assertEqualsWithDelta(66.67, $confidence->getScoreStrength(), 0.01);
    }

    public function testFromMatchStatisticsHandlesZeroTopScore(): void
    {
        $confidence = MatchConfidence::fromMatchStatistics(2, 3, 0.0, 0.0);

        $this->assertEqualsWithDelta(66.67, $confidence->getKeywordCoverage(), 0.01);
        $this->assertSame(0.0, $confidence->getScoreStrength());
    }

    public function testFromMatchStatisticsThrowsOnInvalidTotalKeywords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total search keywords must be positive');

        MatchConfidence::fromMatchStatistics(2, 0, 100.0, 150.0);
    }

    public function testFromMatchStatisticsThrowsOnTooManyMatchedKeywords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Matched keyword count (5) must be between 0 and 3');

        MatchConfidence::fromMatchStatistics(5, 3, 100.0, 150.0);
    }

    public function testFromMatchStatisticsThrowsOnNegativeScores(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scores cannot be negative');

        MatchConfidence::fromMatchStatistics(2, 3, -10.0, 150.0);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $confidence = new MatchConfidence(80.0, 75.0);

        $array = $confidence->toArray();

        $this->assertIsArray($array);
        $this->assertSame(80.0, $array['keyword_coverage']);
        $this->assertSame(75.0, $array['score_strength']);
        $this->assertSame(78.0, $array['percentage']);
        $this->assertSame('high', $array['level']);
    }

    public function testToStringReturnsReadableFormat(): void
    {
        $confidence = new MatchConfidence(80.0, 75.0);

        $string = (string)$confidence;

        $this->assertStringContainsString('78.0% confidence', $string);
        $this->assertStringContainsString('coverage:80.0%', $string);
        $this->assertStringContainsString('strength:75.0%', $string);
        $this->assertStringContainsString('level:high', $string);
    }
}
