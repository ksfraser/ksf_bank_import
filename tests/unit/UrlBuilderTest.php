<?php

/**
 * Unit Tests for UrlBuilder Class
 *
 * Tests the URL builder utility for creating HTML links.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      October 19, 2025
 */

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\UrlBuilder;

/**
 * Test cases for UrlBuilder class
 *
 * Verifies URL construction, parameter handling, and HTML generation.
 */
class UrlBuilderTest extends TestCase
{
    /**
     * Test basic URL construction with no parameters
     *
     * @test
     */
    public function testBasicUrlWithNoParameters(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        
        $this->assertSame(
            '<a href="/test/page.php"></a>',
            $builder->toHtml(),
            'Should generate anchor tag with href'
        );
    }

    /**
     * Test URL with link text
     *
     * @test
     */
    public function testUrlWithLinkText(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->setText('Click Here');
        
        $this->assertSame(
            '<a href="/test/page.php">Click Here</a>',
            $builder->toHtml(),
            'Should include link text inside anchor tag'
        );
    }

    /**
     * Test URL with single parameter
     *
     * @test
     */
    public function testUrlWithSingleParameter(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('id', '123');
        
        $this->assertSame(
            '<a href="/test/page.php?id=123"></a>',
            $builder->toHtml(),
            'Should append parameter with ? separator'
        );
    }

    /**
     * Test URL with multiple parameters
     *
     * @test
     */
    public function testUrlWithMultipleParameters(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('id', '123');
        $builder->addParam('type', 'customer');
        $builder->addParam('status', 'active');
        
        $this->assertSame(
            '<a href="/test/page.php?id=123&amp;type=customer&amp;status=active"></a>',
            $builder->toHtml(),
            'Should append multiple parameters with & separator (HTML-encoded)'
        );
    }

    /**
     * Test URL with parameters needing encoding
     *
     * @test
     */
    public function testUrlWithParametersNeedingEncoding(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('name', 'John Doe');
        $builder->addParam('email', 'test@example.com');
        
        $html = $builder->toHtml();
        
        $this->assertStringContainsString(
            'name=John+Doe',
            $html,
            'Should URL-encode space as +'
        );
        
        $this->assertStringContainsString(
            'email=test%40example.com',
            $html,
            'Should URL-encode @ symbol'
        );
    }

    /**
     * Test fluent interface chaining
     *
     * @test
     */
    public function testFluentInterfaceChaining(): void
    {
        $html = (new UrlBuilder('/test/page.php'))
            ->addParam('id', '123')
            ->addParam('action', 'edit')
            ->setText('Edit Record')
            ->toHtml();
        
        $this->assertSame(
            '<a href="/test/page.php?id=123&amp;action=edit">Edit Record</a>',
            $html,
            'Should support fluent method chaining'
        );
    }

    /**
     * Test URL with CSS class
     *
     * @test
     */
    public function testUrlWithCssClass(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->setText('Link')->addClass('btn btn-primary');
        
        $this->assertSame(
            '<a href="/test/page.php" class="btn btn-primary">Link</a>',
            $builder->toHtml(),
            'Should include class attribute'
        );
    }

    /**
     * Test URL with target attribute
     *
     * @test
     */
    public function testUrlWithTargetAttribute(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->setText('External')->setTarget('_blank');
        
        $this->assertSame(
            '<a href="/test/page.php" target="_blank">External</a>',
            $builder->toHtml(),
            'Should include target attribute'
        );
    }

    /**
     * Test URL with both class and target
     *
     * @test
     */
    public function testUrlWithClassAndTarget(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->setText('Link')
            ->addClass('external-link')
            ->setTarget('_blank');
        
        $this->assertSame(
            '<a href="/test/page.php" class="external-link" target="_blank">Link</a>',
            $builder->toHtml(),
            'Should include both class and target attributes'
        );
    }

    /**
     * Test getUrl() returns URL string without HTML
     *
     * @test
     */
    public function testGetUrlReturnsOnlyUrlString(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('id', '123');
        $builder->setText('Link Text');
        
        $this->assertSame(
            '/test/page.php?id=123',
            $builder->getUrl(),
            'getUrl() should return only URL string without HTML tags'
        );
    }

    /**
     * Test empty parameter value
     *
     * @test
     */
    public function testEmptyParameterValue(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('id', '');
        
        $this->assertSame(
            '<a href="/test/page.php?id="></a>',
            $builder->toHtml(),
            'Should include empty parameter value'
        );
    }

    /**
     * Test zero as parameter value
     *
     * @test
     */
    public function testZeroAsParameterValue(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('page', 0);
        
        $this->assertSame(
            '<a href="/test/page.php?page=0"></a>',
            $builder->toHtml(),
            'Should include zero as valid parameter value'
        );
    }

    /**
     * Test numeric parameter values
     *
     * @test
     */
    public function testNumericParameterValues(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('id', 123);
        $builder->addParam('amount', 99.95);
        
        $html = $builder->toHtml();
        
        $this->assertStringContainsString('id=123', $html);
        $this->assertStringContainsString('amount=99.95', $html);
    }

    /**
     * Test boolean parameter values
     *
     * @test
     */
    public function testBooleanParameterValues(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParam('active', true);
        $builder->addParam('deleted', false);
        
        $html = $builder->toHtml();
        
        $this->assertStringContainsString('active=1', $html);
        $this->assertStringContainsString('deleted=0', $html);
    }

    /**
     * Test special characters in link text
     *
     * @test
     */
    public function testSpecialCharactersInLinkText(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->setText('<View & Edit>');
        
        $this->assertSame(
            '<a href="/test/page.php">&lt;View &amp; Edit&gt;</a>',
            $builder->toHtml(),
            'Should HTML-escape special characters in link text'
        );
    }

    /**
     * Test addParams() method for batch adding
     *
     * @test
     */
    public function testAddParamsBatchMethod(): void
    {
        $builder = new UrlBuilder('/test/page.php');
        $builder->addParams([
            'id' => '123',
            'type' => 'customer',
            'action' => 'view'
        ]);
        
        $this->assertSame(
            '<a href="/test/page.php?id=123&amp;type=customer&amp;action=view"></a>',
            $builder->toHtml(),
            'addParams() should add multiple parameters at once'
        );
    }
}
