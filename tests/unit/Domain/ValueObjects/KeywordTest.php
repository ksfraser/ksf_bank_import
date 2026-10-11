<?php

namespace Tests\Unit\Domain\ValueObjects;

use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Keyword value object
 */
class KeywordTest extends TestCase
{
    public function testConstructorCreatesValidKeyword(): void
    {
        $keyword = new Keyword('shoppers');

        $this->assertSame('shoppers', $keyword->getText());
        $this->assertSame(8, $keyword->getLength());
        $this->assertTrue($keyword->isValid());
    }

    public function testConstructorNormalizesKeyword(): void
    {
        $keyword = new Keyword('  SHOPPERS Drug-Mart  ');

        $this->assertSame('shoppers drug-mart', $keyword->getText());
    }

    public function testConstructorRemovesSpecialCharacters(): void
    {
        $keyword = new Keyword('shop$per#s@');

        $this->assertSame('shoppers', $keyword->getText());
    }

    public function testConstructorThrowsOnEmptyKeyword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword cannot be empty');

        new Keyword('');
    }

    public function testConstructorThrowsOnTooShortKeyword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword too short');

        new Keyword('a');
    }

    public function testConstructorThrowsOnPurelyNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword cannot be purely numeric');

        new Keyword('12345');
    }

    public function testContainsReturnsTrueWhenKeywordContainsAnother(): void
    {
        $keyword1 = new Keyword('shoppers');
        $keyword2 = new Keyword('shop');

        $this->assertTrue($keyword1->contains($keyword2));
    }

    public function testContainsReturnsFalseWhenKeywordDoesNotContainAnother(): void
    {
        $keyword1 = new Keyword('shoppers');
        $keyword2 = new Keyword('walmart');

        $this->assertFalse($keyword1->contains($keyword2));
    }

    public function testEqualsReturnsTrueForSameKeyword(): void
    {
        $keyword1 = new Keyword('shoppers');
        $keyword2 = new Keyword('SHOPPERS');

        $this->assertTrue($keyword1->equals($keyword2));
    }

    public function testEqualsReturnsFalseForDifferentKeyword(): void
    {
        $keyword1 = new Keyword('shoppers');
        $keyword2 = new Keyword('walmart');

        $this->assertFalse($keyword1->equals($keyword2));
    }

    public function testIsStopwordReturnsTrueForStopword(): void
    {
        $keyword = new Keyword('the');
        $stopwords = ['the', 'and', 'or'];

        $this->assertTrue($keyword->isStopword($stopwords));
    }

    public function testIsStopwordReturnsFalseForNonStopword(): void
    {
        $keyword = new Keyword('shoppers');
        $stopwords = ['the', 'and', 'or'];

        $this->assertFalse($keyword->isStopword($stopwords));
    }

    public function testToStringReturnsText(): void
    {
        $keyword = new Keyword('shoppers');

        $this->assertSame('shoppers', (string)$keyword);
    }
}
