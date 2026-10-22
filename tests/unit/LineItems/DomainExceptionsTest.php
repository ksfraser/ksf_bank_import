<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use InvalidArgumentException;
use Ksfraser\FaBankImport\Domain\Exceptions\InvalidKeywordException;
use Ksfraser\FaBankImport\Domain\Exceptions\PartnerDataNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the domain exceptions of the import pipeline.
 */
final class DomainExceptionsTest extends TestCase
{
    public function testTheEmptyKeywordExceptionIsAnInvalidArgument(): void
    {
        $exception = InvalidKeywordException::empty();

        $this->assertInstanceOf(InvalidKeywordException::class, $exception);
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertSame('Keyword cannot be empty', $exception->getMessage());
    }

    public function testTheTooShortKeywordExceptionNamesTheKeyword(): void
    {
        $exception = InvalidKeywordException::tooShort('ab', 3);

        $this->assertSame('Keyword "ab" is too short (minimum 3 characters)', $exception->getMessage());
    }

    public function testTheTooLongKeywordExceptionTruncatesTheKeyword(): void
    {
        $keyword = str_repeat('x', 60);

        $exception = InvalidKeywordException::tooLong($keyword, 10);

        $this->assertSame(
            'Keyword "' . str_repeat('x', 50) . '..." is too long (maximum 10 characters)',
            $exception->getMessage()
        );
    }

    public function testTheTooLongKeywordExceptionKeepsShortKeywordsIntact(): void
    {
        $exception = InvalidKeywordException::tooLong('abc', 10);

        $this->assertSame('Keyword "abc..." is too long (maximum 10 characters)', $exception->getMessage());
    }

    public function testTheNumericOnlyKeywordExceptionRejectsNumbers(): void
    {
        $exception = InvalidKeywordException::numericOnly('12345');

        $this->assertSame('Keyword "12345" cannot be purely numeric', $exception->getMessage());
    }

    public function testTheStopwordExceptionNamesTheKeyword(): void
    {
        $exception = InvalidKeywordException::isStopword('the');

        $this->assertSame('Keyword "the" is a stopword and cannot be used', $exception->getMessage());
    }

    public function testThePartnerDataExceptionNamesTheIdentifiers(): void
    {
        $exception = PartnerDataNotFoundException::forPartner(1, 2, 3);

        $this->assertSame(
            'Partner data not found for partner_id=1, partner_type=2, partner_detail_id=3',
            $exception->getMessage()
        );
    }

    public function testTheKeywordExceptionNamesTheKeyword(): void
    {
        $exception = PartnerDataNotFoundException::forKeyword('acme');

        $this->assertSame('No partner data found for keyword: "acme"', $exception->getMessage());
    }

    public function testTheKeywordExceptionCanNameThePartnerType(): void
    {
        $exception = PartnerDataNotFoundException::forKeyword('acme', 4);

        $this->assertSame('No partner data found for keyword: "acme" (partner_type=4)', $exception->getMessage());
    }

    public function testTheKeywordsExceptionListsEveryKeyword(): void
    {
        $exception = PartnerDataNotFoundException::forKeywords(['acme', 'globex']);

        $this->assertSame(
            'No partner data found for keywords: "acme", "globex"',
            $exception->getMessage()
        );
    }

    public function testTheKeywordsExceptionCanNameThePartnerType(): void
    {
        $exception = PartnerDataNotFoundException::forKeywords(['acme'], 4);

        $this->assertSame(
            'No partner data found for keywords: "acme" (partner_type=4)',
            $exception->getMessage()
        );
    }
}
