<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Domain;

use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the keyword value objects.
 *
 * @see \Ksfraser\FaBankImport\Domain\ValueObjects\Keyword
 * @see \Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch
 */
final class KeywordValueObjectTest extends TestCase
{
    /**
     * @return void
     */
    public function testAKeywordIsNormalised(): void
    {
        $keyword = new Keyword('  ACME  Ltd  ');

        $this->assertSame('acme ltd', $keyword->getText());
        $this->assertSame(8, $keyword->getLength());
    }

    /**
     * @return void
     */
    public function testAKeywordThatIsTooLongIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword too long (max 100 chars)');

        new Keyword(str_repeat('a', 101));
    }

    /**
     * @return void
     */
    public function testTheMatchedKeywordsAreReportedBack(): void
    {
        $keywords = [new Keyword('acme'), new Keyword('ltd')];
        $match = $this->match($keywords);

        $this->assertSame($keywords, $match->getMatchedKeywords());
        $this->assertSame(2, $match->getMatchedKeywordCount());
    }

    /**
     * @return void
     */
    public function testTheConfidenceIsReportedBack(): void
    {
        $confidence = new MatchConfidence(50.0, 60.0);
        $match = $this->match([new Keyword('acme')], $confidence);

        $this->assertSame($confidence, $match->getConfidence());
    }

    /**
     * @return void
     */
    public function testAMatchedKeywordHasToBeAKeyword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All matched keywords must be Keyword objects');

        $this->match(['not a keyword']);
    }

    /**
     * Build a keyword match.
     *
     * @param array $keywords The matched keywords.
     * @param MatchConfidence|null $confidence The confidence of the match.
     * @return KeywordMatch The match.
     */
    private function match(array $keywords, ?MatchConfidence $confidence = null): KeywordMatch
    {
        return new KeywordMatch(
            42,
            1,
            7,
            'Acme Ltd',
            $keywords,
            3,
            3.5,
            $confidence ?? new MatchConfidence(50.0, 60.0)
        );
    }
}
