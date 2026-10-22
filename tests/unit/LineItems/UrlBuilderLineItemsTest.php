<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\UrlBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the string conversion of the URL builder.
 *
 * The rest of the builder is covered by tests/unit/UrlBuilderTest.php; the
 * alias for the HTML rendering is exercised here so that the line-item test
 * suite covers the class on its own.
 */
final class UrlBuilderLineItemsTest extends TestCase
{
    public function testTheBuilderRendersAnAnchorTag(): void
    {
        $builder = new UrlBuilder('/banking/transaction_inquiry.php');
        $builder->addParam('trans_no', 123);
        $builder->setText('View Transaction');

        $this->assertSame(
            '<a href="/banking/transaction_inquiry.php?trans_no=123">View Transaction</a>',
            (string) $builder
        );
    }

    public function testTheStringConversionKeepsTheClassAndTarget(): void
    {
        $builder = new UrlBuilder('/inquiry.php');
        $builder->addClass('btn');
        $builder->setTarget('_blank');
        $builder->setText('Go');

        $this->assertSame(
            '<a href="/inquiry.php" class="btn" target="_blank">Go</a>',
            (string) $builder
        );
    }

    public function testBooleanParametersBecomeNumbers(): void
    {
        $builder = new UrlBuilder('/inquiry.php');
        $builder->addParam('yes', true);
        $builder->addParam('no', false);

        $this->assertSame('/inquiry.php?yes=1&no=0', $builder->getUrl());
    }

    public function testSeveralParametersCanBeAddedAtOnce(): void
    {
        $builder = new UrlBuilder('/inquiry.php');
        $builder->addParams(['trans_no' => 123, 'trans_type' => 1]);

        $this->assertSame('/inquiry.php?trans_no=123&trans_type=1', $builder->getUrl());
    }

    public function testABuilderWithoutParametersHasNoQueryString(): void
    {
        $builder = new UrlBuilder('/inquiry.php');

        $this->assertSame('/inquiry.php', $builder->getUrl());
    }
}
